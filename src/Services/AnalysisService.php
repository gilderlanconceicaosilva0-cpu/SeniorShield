<?php
/**
 * SeniorShield - Serviço do Motor de Análise e Cálculo do ISD
 *
 * Responsável pelo processamento de mensagens suspeitas, detecção inteligente
 * de indicadores de risco, cálculo do Índice de Suspeição Digital (ISD),
 * persistência relacional das análises e consulta ao histórico do usuário.
 * Atende aos requisitos RF08-RF23, CA04-CA08 e RNF01-RNF16.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Helpers/utils.php';

class AnalysisService {
    private PDO $pdo;

    /**
     * Permite injeção de dependência do PDO (para testes) ou utiliza a conexão padrão.
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDbConnection();
    }

    /**
     * Analisa uma mensagem textual, identifica indicadores ativos e calcula o ISD.
     * Se um ID de usuário for informado, persiste a análise e seus indicadores no banco.
     *
     * @param string $mensagem Conteúdo recebido pelo usuário
     * @param int|null $idUsuario ID do usuário logado (opcional para salvar histórico)
     * @return array Resultado estruturado com pontuação, classificação e detalhes
     */
    public function analisarMensagem(string $mensagem, ?int $idUsuario = null): array {
        $mensagemLimpa = trim($mensagem);

        // 1. Validação de Entrada (RF08, RNF04)
        if (empty($mensagemLimpa)) {
            return [
                'sucesso' => false,
                'erro'    => 'Por favor, insira o texto da mensagem que deseja analisar.'
            ];
        }

        if (mb_strlen($mensagemLimpa) < 3) {
            return [
                'sucesso' => false,
                'erro'    => 'A mensagem informada é muito curta para uma análise confiável (mínimo de 3 caracteres).'
            ];
        }

        if (mb_strlen($mensagemLimpa) > 10000) {
            return [
                'sucesso' => false,
                'erro'    => 'A mensagem excede o limite máximo permitido de 10.000 caracteres.'
            ];
        }

        // 2. Busca todos os indicadores ativos cadastrados no sistema (RF09)
        try {
            $stmt = $this->pdo->query(
                'SELECT id_indicador, nome, descricao, palavras_chave, peso
                 FROM INDICADORES
                 WHERE ativo = 1
                 ORDER BY peso DESC, id_indicador ASC'
            );
            $indicadoresCadastrados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('AnalysisService::analisarMensagem erro ao buscar indicadores: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erro'    => 'Falha temporária ao carregar as regras de segurança. Tente novamente mais tarde.'
            ];
        }

        // 3. Processamento e Detecção de Indicadores Contextuais (RF09, RF10)
        $mensagemNormalizada = $this->normalizarTexto($mensagemLimpa);

        $temCobrancaAtiva = $this->temCobrancaAtiva($mensagemNormalizada);
        $ehSemCobrancaLegitimo = $this->ehContextoSemCobrancaLegitimo($mensagemNormalizada);
        $ehSelecaoEmprego = $this->ehSelecaoEmprego($mensagemNormalizada);

        $indicadoresDetectados = [];
        $somaPesos = 0;

        foreach ($indicadoresCadastrados as $indicador) {
            $idIndicador = (int) $indicador['id_indicador'];
            $tipoIndicador = $this->obterTipoIndicador($indicador);
            $palavrasChave = $this->obterPalavrasChaveCompletas($indicador, $tipoIndicador);
            $termosEncontrados = [];

            foreach ($palavrasChave as $termo) {
                if ($termo === '') {
                    continue;
                }

                $termoNormalizado = $this->normalizarTexto($termo);
                if ($termoNormalizado === '') {
                    continue;
                }

                // Expressão regular com fronteiras seguras e flexibilidade para múltiplos espaços
                $termoRegex = preg_replace('/\s+/', '\s+', preg_quote($termoNormalizado, '/'));
                $padrao = '/(?<![a-z0-9])' . $termoRegex . '(?![a-z0-9])/iu';

                if (preg_match($padrao, $mensagemNormalizada)) {
                    $termosEncontrados[] = $termo;
                }
            }

            if (empty($termosEncontrados)) {
                continue;
            }

            // Deduplica termos encontrados descartando repetições e substrings redundantes
            $termosDeduplicados = $this->deduplicarTermos($termosEncontrados);
            if (empty($termosDeduplicados)) {
                continue;
            }

            // --- REGRAS CONTEXTUAIS AVANÇADAS ---

            // Regra 1: Avisos Preventivos Legítimos de Segurança (Credenciais e Falso Suporte)
            if (in_array($tipoIndicador, ['credenciais', 'falso_suporte'], true)) {
                if ($this->ehContextoPreventivo($mensagemNormalizada, $termosDeduplicados, $idIndicador)) {
                    continue;
                }
            }

            // Regra 1B: Diferenciação entre dados bancários cadastrais e senha/código
            // Não tratar "dados bancários" como se fosse pedido explícito de senha, token ou código SMS
            if ($tipoIndicador === 'credenciais') {
                $termosDeduplicados = array_values(array_filter($termosDeduplicados, function ($t) {
                    $tNorm = $this->normalizarTexto($t);
                    return !in_array($tNorm, ['dados bancarios', 'dados da conta', 'numero da conta', 'agencia'], true);
                }));

                if (empty($termosDeduplicados)) {
                    continue;
                }
            }

            // Regra 1C: Ameaças de devolução de encomenda pertencem à falsa entrega, não bloqueio de conta
            if ($tipoIndicador === 'ameaca') {
                $temContextoEntregaMensagem = (bool) preg_match(
                    '/\b(encomenda|pacote|pedido|entrega|transportadora|correios|alfandega)\b/iu',
                    $mensagemNormalizada
                );
                if ($temContextoEntregaMensagem) {
                    $termosDeduplicados = array_values(array_filter($termosDeduplicados, function ($t) {
                        $tNorm = $this->normalizarTexto($t);
                        return !preg_match('/\b(devolvid[oa]|devolucao|pacote|encomenda)\b/iu', $tNorm);
                    }));

                    if (empty($termosDeduplicados)) {
                        continue;
                    }
                }
            }

            // Regra 2: Contexto Legítimo de Gratuidade / Ausência de Cobrança (Cobrança, Falso Prêmio e Entrega)
            // Se há garantia explícita de gratuidade sem nenhuma cobrança ativa posterior
            if ($ehSemCobrancaLegitimo && !$temCobrancaAtiva) {
                if (in_array($tipoIndicador, ['cobranca', 'falso_premio', 'entrega'], true)) {
                    continue;
                }
            }

            // Regra 2B: Mensagens de Logística/Rastreamento Legítimas sem Ameaça nem Cobrança
            if ($tipoIndicador === 'entrega' && $this->ehLogisticaLegitimaSemAmeaca($mensagemNormalizada, $termosDeduplicados)) {
                continue;
            }
            // Regra 2C: Exige contexto real de entrega para ativar o indicador de entrega
            if ($tipoIndicador === 'entrega') {
                $temContextoEntrega = (bool) preg_match(
                    '/\b(encomenda|pacote|pedido|entrega|transportadora|correios|rastreamento|rastreio|alfandega|alfandegaria|objeto|mercadoria)\b/iu',
                    $mensagemNormalizada
                );

                if (!$temContextoEntrega) {
                    continue;
                }
            }

            // Regra 2D: Validação Contextual de Menção Financeira / Pagamento
            // Evita falso positivo por palavras genéricas ("pagamento", "taxa", "pix") em avisos legítimos ou negações de cobrança
            if ($tipoIndicador === 'cobranca') {
                if ($this->ehMencaoFinanceiraGenericaInocente($mensagemNormalizada, $termosDeduplicados)) {
                    continue;
                }
            }

            // Regra 3: Desambiguação de Vaga de Emprego vs. Falso Prêmio
            // Expressões genéricas de seleção quando em contexto de emprego pertencem à categoria de emprego, não prêmio
            if ($tipoIndicador === 'falso_premio' && $ehSelecaoEmprego) {
                // Filtra termos de seleção genérica do indicador de prêmio
                $termosNaoEmprego = array_filter($termosDeduplicados, function ($t) {
                    $tNorm = $this->normalizarTexto($t);
                    return !in_array($tNorm, ['foi selecionado', 'selecionado para receber', 'selecionado', 'você foi contemplado', 'voce foi contemplado', 'você foi selecionado', 'voce foi selecionado'], true);
                });

                if (empty($termosNaoEmprego)) {
                    continue;
                }
                $termosDeduplicados = array_values($termosNaoEmprego);
            }

            // Regra 4: Validação Contextual de Links Referenciais ("link abaixo", etc.)
            // Evita falsos positivos em mensagens legítimas que apenas mencionam "link abaixo" sem contexto suspeito
            if ($tipoIndicador === 'link') {
                if ($this->ehApenasLinkReferencialInocente($mensagemNormalizada, $termosDeduplicados)) {
                    continue;
                }
            }

            // Cálculo da pontuação do indicador com bônus por sinais independentes adicionais
            $pesoBase = (int) $indicador['peso'];

            // Bônus de 5 pontos por sinal independente adicional, limitado a 10 pontos de bônus
            $bonusSinais = min(10, max(0, (count($termosDeduplicados) - 1) * 5));

            // Limita o peso aplicado deste indicador a no máximo 30 pontos
            $pesoAplicado = min(30, $pesoBase + $bonusSinais);

            $somaPesos += $pesoAplicado;

            $indicadoresDetectados[] = [
                'id_indicador'      => $idIndicador,
                'nome'              => $indicador['nome'],
                'descricao'         => $indicador['descricao'] ?? '',
                'peso'              => $pesoAplicado,
                'termos_detectados' => $termosDeduplicados
            ];
        }

        // 4. Avaliação de Correlação de Risco Relevante entre Indicadores (RF11, RF12)
        $tiposDetectados = array_map(function ($ind) {
            return $this->obterTipoIndicador($ind);
        }, $indicadoresDetectados);

        $bonusCorrelacao = 0;

        // Correlação A: Falso suporte solicitando código SMS / verificação (tentativa de tomada de conta)
        if (in_array('falso_suporte', $tiposDetectados, true) && in_array('credenciais', $tiposDetectados, true)) {
            $bonusCorrelacao += 10;
        }

        // Correlação B: Falsa entrega com solicitação de Pix ou taxa
        if (in_array('entrega', $tiposDetectados, true) && in_array('cobranca', $tiposDetectados, true)) {
            $bonusCorrelacao += 5;
        }

        // Correlação C: Falso prêmio condicionado a pagamento de taxa
        if (in_array('falso_premio', $tiposDetectados, true) && in_array('cobranca', $tiposDetectados, true)) {
            $bonusCorrelacao += 5;
        }

        // 5. Cálculo do ISD e Limitação a 100 (RF11, RF12)
        $pontuacaoIsd = min(ISD_MAX_SCORE, max(0, $somaPesos + $bonusCorrelacao));

        // 6. Classificação Oficial do Risco (RF13)
        $classificacao = getIsdClassification($pontuacaoIsd);
        $nivelSlug = $this->obterNivelSlug($pontuacaoIsd);

        $idAnalise = null;
        $salvoNoBanco = false;

        // 6. Armazenamento Relacional da Análise (RF17-RF22, CA07)
        if ($idUsuario !== null && $idUsuario > 0) {
            try {
                $this->pdo->beginTransaction();

                // RF17, RF18, RF19, RF20, RF21
                $stmtAnalise = $this->pdo->prepare(
                    'INSERT INTO ANALISES (id_usuario, mensagem, pontuacao_isd, classificacao, data_analise)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $stmtAnalise->execute([
                    $idUsuario,
                    $mensagemLimpa,
                    $pontuacaoIsd,
                    $classificacao
                ]);

                $idAnalise = (int)$this->pdo->lastInsertId();

                // RF22: Relaciona cada indicador detectado na análise
                if (!empty($indicadoresDetectados)) {
                    $stmtInd = $this->pdo->prepare(
                        'INSERT INTO ANALISE_INDICADORES (id_analise, id_indicador, peso_aplicado)
                         VALUES (?, ?, ?)'
                    );

                    foreach ($indicadoresDetectados as $ind) {
                        $stmtInd->execute([
                            $idAnalise,
                            $ind['id_indicador'],
                            $ind['peso']
                        ]);
                    }
                }

                $this->pdo->commit();
                $salvoNoBanco = true;
            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                error_log('AnalysisService::analisarMensagem erro ao salvar analise: ' . $e->getMessage());
                // Análise continua válida mesmo se falhar a gravação no histórico
            }
        }

        return [
            'sucesso'                => true,
            'id_analise'             => $idAnalise,
            'mensagem'               => $mensagemLimpa,
            'pontuacao_isd'          => $pontuacaoIsd,
            'classificacao'          => $classificacao,
            'nivel_slug'             => $nivelSlug,
            'indicadores_detectados' => $indicadoresDetectados,
            'total_indicadores'      => count($indicadoresDetectados),
            'salvo_no_historico'     => $salvoNoBanco,
            'disclaimer_avaliacao'   => ISD_DISCLAIMER_AVALIACAO,
            'aviso_privacidade'      => ISD_AVISO_PRIVACIDADE
        ];
    }

    /**
     * Consulta o histórico de análises realizadas por um determinado usuário (RF23, CA08).
     *
     * @param int $idUsuario ID do usuário
     * @param int $limite Quantidade máxima de registros a retornar
     * @param int $offset Deslocamento para paginação
     * @return array Lista de análises com indicadores associados
     */
    public function obterHistoricoUsuario(int $idUsuario, int $limite = 30, int $offset = 0): array {
        if ($idUsuario <= 0) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                   a.id_analise,
                   a.id_usuario,
                   a.mensagem,
                   a.pontuacao_isd,
                   a.classificacao,
                   a.data_analise,
                   COUNT(ai.id_indicador) AS total_indicadores
                 FROM ANALISES a
                 LEFT JOIN ANALISE_INDICADORES ai ON a.id_analise = ai.id_analise
                 WHERE a.id_usuario = ?
                 GROUP BY a.id_analise
                 ORDER BY a.data_analise DESC, a.id_analise DESC
                 LIMIT ? OFFSET ?'
            );

            // Vinculação explícita de inteiros para LIMIT e OFFSET
            $stmt->bindValue(1, $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(2, max(1, $limite), PDO::PARAM_INT);
            $stmt->bindValue(3, max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();

            $analises = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formata e adiciona metadados úteis para a camada visual
            foreach ($analises as &$item) {
                $item['pontuacao_isd'] = (int)$item['pontuacao_isd'];
                $item['total_indicadores'] = (int)$item['total_indicadores'];
                $item['nivel_slug'] = $this->obterNivelSlug($item['pontuacao_isd']);
                $item['data_formatada'] = format_datetime_br($item['data_analise']);
                $item['preview_mensagem'] = $this->resumirTexto($item['mensagem'], 140);
            }

            return $analises;
        } catch (PDOException $e) {
            error_log('AnalysisService::obterHistoricoUsuario erro: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna a quantidade total de análises feitas pelo usuário (para estatísticas e paginação).
     *
     * @param int $idUsuario
     * @return int
     */
    public function obterTotalAnalisesUsuario(int $idUsuario): int {
        if ($idUsuario <= 0) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ANALISES WHERE id_usuario = ?');
            $stmt->execute([$idUsuario]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('AnalysisService::obterTotalAnalisesUsuario erro: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtém os detalhes completos de uma análise específica, incluindo os indicadores vinculados.
     *
     * @param int $idAnalise ID da análise
     * @param int|null $idUsuario ID do usuário proprietário (ou null se admin)
     * @return array|null Dados da análise ou null se não encontrada ou não autorizada
     */
    public function obterAnalisePorId(int $idAnalise, ?int $idUsuario = null): ?array {
        if ($idAnalise <= 0) {
            return null;
        }

        try {
            $sql = 'SELECT a.id_analise, a.id_usuario, a.mensagem, a.pontuacao_isd, a.classificacao, a.data_analise, u.nome AS nome_usuario
                    FROM ANALISES a
                    JOIN USUARIOS u ON a.id_usuario = u.id_usuario
                    WHERE a.id_analise = ?';
            $params = [$idAnalise];

            if ($idUsuario !== null) {
                $sql .= ' AND a.id_usuario = ?';
                $params[] = $idUsuario;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $analise = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$analise) {
                return null;
            }

            // Busca os indicadores vinculados através da tabela intermediária ANALISE_INDICADORES
            $stmtInd = $this->pdo->prepare(
                'SELECT
                   i.id_indicador,
                   i.nome,
                   i.descricao,
                   ai.peso_aplicado AS peso
                 FROM ANALISE_INDICADORES ai
                 JOIN INDICADORES i ON ai.id_indicador = i.id_indicador
                 WHERE ai.id_analise = ?
                 ORDER BY ai.peso_aplicado DESC, i.id_indicador ASC'
            );
            $stmtInd->execute([$idAnalise]);
            $analise['indicadores'] = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

            $analise['pontuacao_isd'] = (int)$analise['pontuacao_isd'];
            $analise['nivel_slug'] = $this->obterNivelSlug($analise['pontuacao_isd']);
            $analise['data_formatada'] = format_datetime_br($analise['data_analise']);

            return $analise;
        } catch (PDOException $e) {
            error_log('AnalysisService::obterAnalisePorId erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Exclui uma análise do histórico do usuário proprietário.
     *
     * @param int $idAnalise ID da análise
     * @param int $idUsuario ID do usuário proprietário (garante que só o dono exclui)
     * @return bool
     */
    public function excluirAnalise(int $idAnalise, int $idUsuario): bool {
        if ($idAnalise <= 0 || $idUsuario <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM ANALISES WHERE id_analise = ? AND id_usuario = ?');
            $stmt->execute([$idAnalise, $idUsuario]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('AnalysisService::excluirAnalise erro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém resumo estatístico das análises de um usuário.
     *
     * @param int $idUsuario
     * @return array
     */
    public function obterEstatisticasUsuario(int $idUsuario): array {
        $stats = [
            'total'         => 0,
            'media_isd'     => 0,
            'risco_baixo'   => 0,
            'atencao'       => 0,
            'suspeito'      => 0,
            'alto_risco'    => 0,
            'risco_critico' => 0
        ];

        if ($idUsuario <= 0) {
            return $stats;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                   COUNT(*) AS total,
                   AVG(pontuacao_isd) AS media_isd,
                   SUM(CASE WHEN pontuacao_isd <= 20 THEN 1 ELSE 0 END) AS risco_baixo,
                   SUM(CASE WHEN pontuacao_isd BETWEEN 21 AND 40 THEN 1 ELSE 0 END) AS atencao,
                   SUM(CASE WHEN pontuacao_isd BETWEEN 41 AND 60 THEN 1 ELSE 0 END) AS suspeito,
                   SUM(CASE WHEN pontuacao_isd BETWEEN 61 AND 80 THEN 1 ELSE 0 END) AS alto_risco,
                   SUM(CASE WHEN pontuacao_isd >= 81 THEN 1 ELSE 0 END) AS risco_critico
                 FROM ANALISES
                 WHERE id_usuario = ?'
            );
            $stmt->execute([$idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $stats['total']         = (int)($res['total'] ?? 0);
                $stats['media_isd']     = (int)round((float)($res['media_isd'] ?? 0));
                $stats['risco_baixo']   = (int)($res['risco_baixo'] ?? 0);
                $stats['atencao']       = (int)($res['atencao'] ?? 0);
                $stats['suspeito']      = (int)($res['suspeito'] ?? 0);
                $stats['alto_risco']    = (int)($res['alto_risco'] ?? 0);
                $stats['risco_critico'] = (int)($res['risco_critico'] ?? 0);
            }

            return $stats;
        } catch (PDOException $e) {
            error_log('AnalysisService::obterEstatisticasUsuario erro: ' . $e->getMessage());
            return $stats;
        }
    }

    /**
     * Normaliza texto convertendo para minúsculas e mapeando acentuações para caracteres básicos.
     *
     * @param string $texto
     * @return string
     */
    public function normalizarTexto(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        $mapaAcentos = [
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'ý'=>'y','ÿ'=>'y',
            'ç'=>'c','ñ'=>'n'
        ];
        $texto = strtr($texto, $mapaAcentos);
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /**
     * Deduplica termos detectados removendo repetições por acento/caixa e
     * descartando termos que sejam estritamente substrings de expressões mais longas
     * e específicas já encontradas (evitando inflação de pontuação por subfrases).
     *
     * @param array $termos
     * @return array
     */
    private function deduplicarTermos(array $termos): array {
        if (empty($termos)) {
            return [];
        }

        // 1. Remove duplicatas normalizadas (acentos/maiúsculas)
        $termosUnicos = [];
        foreach ($termos as $t) {
            $chave = $this->normalizarTexto($t);
            if (!isset($termosUnicos[$chave])) {
                $termosUnicos[$chave] = $t;
            }
        }

        $lista = array_values($termosUnicos);

        // 2. Ordena por comprimento decrescente (mais específicos primeiro)
        usort($lista, function ($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        // 3. Remove termos que são substrings exatas de um termo mais longo já aceito
        $deduplicados = [];
        foreach ($lista as $t) {
            $tNorm = $this->normalizarTexto($t);
            $jaCoberto = false;
            foreach ($deduplicados as $existente) {
                $existenteNorm = $this->normalizarTexto($existente);
                if (mb_strpos($existenteNorm, $tNorm) !== false) {
                    $jaCoberto = true;
                    break;
                }
            }
            if (!$jaCoberto) {
                $deduplicados[] = $t;
            }
        }

        return $deduplicados;
    }

    /**
     * Mapeia o indicador para seu identificador canônico/semântico a partir do nome
     * (ou id_indicador como fallback), desacoplando a lógica de regras de IDs sequenciais fixos.
     *
     * @param array $indicador
     * @return string
     */
    public function obterTipoIndicador(array $indicador): string {
        $nomeNorm = $this->normalizarTexto($indicador['nome'] ?? '');
        $id = (int)($indicador['id_indicador'] ?? 0);

        if (str_contains($nomeNorm, 'urgencia') || $id === 1) {
            return 'urgencia';
        }
        if (str_contains($nomeNorm, 'dinheiro') || str_contains($nomeNorm, 'transferencia') || $id === 2) {
            return 'cobranca';
        }
        if (str_contains($nomeNorm, 'link') || $id === 3) {
            return 'link';
        }
        if (str_contains($nomeNorm, 'senha') || str_contains($nomeNorm, 'codigo') || $id === 4) {
            return 'credenciais';
        }
        if (str_contains($nomeNorm, 'premio') || str_contains($nomeNorm, 'oferta') || $id === 5) {
            return 'falso_premio';
        }
        if (str_contains($nomeNorm, 'bloqueio') || str_contains($nomeNorm, 'ameaca') || $id === 6) {
            return 'ameaca';
        }
        if (str_contains($nomeNorm, 'familiar') || str_contains($nomeNorm, 'parente') || $id === 7) {
            return 'falso_familiar';
        }
        if (str_contains($nomeNorm, 'entrega') || str_contains($nomeNorm, 'encomenda') || $id === 8) {
            return 'entrega';
        }
        if (str_contains($nomeNorm, 'emprego') || str_contains($nomeNorm, 'emprestimo') || str_contains($nomeNorm, 'investimento') || $id === 9) {
            return 'falso_emprego';
        }
        if (str_contains($nomeNorm, 'suporte') || str_contains($nomeNorm, 'remoto') || $id === 10) {
            return 'falso_suporte';
        }

        return 'outro';
    }

    /**
     * Retorna a lista completa de termos de busca de um indicador, combinando as palavras-chave
     * cadastradas no banco com termos sinônimos canônicos essenciais da especificação para resiliência.
     *
     * @param array $indicador
     * @param string $tipoIndicador
     * @return array
     */
    private function obterPalavrasChaveCompletas(array $indicador, string $tipoIndicador): array {
        $termosBanco = array_filter(array_map('trim', explode(',', $indicador['palavras_chave'] ?? '')));

        $termosCanonicos = [];
        switch ($tipoIndicador) {
            case 'urgencia':
                $termosCanonicos = [
                    'proximos 10 minutos', 'proximos minutos', 'prazo termina hoje', 'termina hoje',
                    'nao perca essa oportunidade', 'nao perca a oportunidade', 'ainda hoje', 'regularize agora'
                ];
                break;
            case 'cobranca':
                $termosCanonicos = [
                    'pague uma taxa de liberacao', 'taxa de liberacao', 'pague uma taxa',
                    'pague a taxa', 'faca um pix', 'fazer um pix', 'via pix', 'pix para a transportadora'
                ];
                break;
            case 'falso_premio':
                $termosCanonicos = [
                    'voce foi sorteado e ganhou', 'voce foi sorteado', 'foi sorteado e ganhou',
                    'foi sorteado', 'ganhou r$', 'receber seu premio', 'para receber seu premio',
                    'voce ganhou um premio', 'ganhou um premio', 'receber um premio'
                ];
                break;
            case 'credenciais':
                $termosCanonicos = [
                    'codigo que recebeu por sms', 'codigo que acabou de receber por sms',
                    'codigo de verificacao', 'envie o codigo de verificacao', 'envie o codigo',
                    'sua senha', 'confirme sua senha', 'digite sua senha'
                ];
                break;
            case 'ameaca':
                $termosCanonicos = [
                    'perdera o acesso a sua conta', 'perdera o acesso', 'perda de acesso',
                    'perder o acesso', 'conta sera bloqueada permanentemente', 'bloqueada permanentemente'
                ];
                break;
            case 'entrega':
                $termosCanonicos = [
                    'encomenda esta aguardando liberacao', 'aguardando liberacao', 'para concluir a entrega',
                    'concluir a entrega', 'seu pacote sera devolvido', 'pacote sera devolvido',
                    'retido na alfandega', 'para liberar sua entrega', 'liberar sua entrega'
                ];
                break;
            case 'link':
                $termosCanonicos = [
                    'link de regularizacao', 'link de regularização', 'link enviado nesta mensagem',
                    'acesse o link para regularizar', 'regularize pelo link'
                ];
                break;
            case 'falso_suporte':
                $termosCanonicos = [
                    'somos da equipe de suporte', 'equipe de suporte', 'sou do suporte tecnico',
                    'sou do suporte', 'somos do suporte'
                ];
                break;
        }

        return array_values(array_unique(array_merge($termosBanco, $termosCanonicos)));
    }

    /**
     * Verifica se a mensagem possui cobrança ativa, solicitação de pagamento ou transferência.
     *
     * @param string $mensagemNorm
     * @return bool
     */
    private function temCobrancaAtiva(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(pague|pagar|transfira|transferir|faça um pix|faca um pix|faça o pagamento|faca o pagamento|envie um pix|mande um pix|deposite|deposito antecipado|depósito antecipado|taxa para liberar|taxa de liberação|taxa de liberacao|taxa de entrega|taxa de cadastro|chave pix|envie o valor|pague a taxa|pagar taxa|pagar a taxa|pague uma taxa|pague taxa|pague uma pequena taxa|pequena taxa|pagamento de taxa|receber o dinheiro)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica se a mensagem contém afirmação explícita de ausência de cobrança/gratuidade legítima.
     *
     * @param string $mensagemNorm
     * @return bool
     */
    private function ehContextoSemCobrancaLegitimo(string $mensagemNorm): bool {
        $padraoGratuidade = '/\b('
            . '(a\s+)?entrega\s+(e\s+)?(gratuita|gratis)'
            . '|nao\s+(existe|ha|tem|sera cobrada)\s+(nenhuma|qualquer)?\s*(taxa|cobranca|custo|pagamento|valor)'
            . '|sem\s+(nenhum|qualquer)?\s*(custo|taxa|cobranca|pagamento|valor)'
            . '|totalmente\s+(gratuito|gratis)'
            . '|isento\s+de\s+(taxa|tarifa|cobranca)'
            . '|nao\s+precisa\s+pagar|nao\s+e\s+necessario\s+pagar|sem\s+precisar\s+pagar'
            . '|nao\s+e\s+necessario\s+(realizar|fazer)?\s*(nenhum|qualquer)?\s*(pagamento|cobranca|taxa|custo)'
            . '|sem\s+cobranca\s+de\s+taxa'
            . '|gratuito\s+e\s+sem\s+taxas?'
            . '|retirada\s+(e\s+)?(gratuita|gratis)'
            . ')\b/iu';

        return (bool) preg_match($padraoGratuidade, $mensagemNorm);
    }

    /**
     * Verifica se termos de seleção ocorrem em contexto de vaga de emprego/trabalho.
     *
     * @param string $mensagemNorm
     * @return bool
     */
    private function ehSelecaoEmprego(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(selecionad[oa]|selecao|aprovad[oa])\b.{0,40}\b(vaga|emprego|trabalho|cargo|contratacao|processo seletivo|equipe|salario)\b/iu',
            $mensagemNorm
        ) || (bool) preg_match(
            '/\b(vaga|emprego|trabalho|cargo|contratacao)\b.{0,40}\b(selecionad[oa]|selecao|aprovad[oa])\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Avalia se a menção a senhas, códigos ou suporte técnico está inserida em um
     * contexto estritamente preventivo/educativo e sem qualquer vetor ativo de ataque.
     *
     * @param string $mensagemNorm Texto normalizado
     * @param array $termos Termos detectados para o indicador
     * @param int $idIndicador ID do indicador (4 para senhas/códigos, 10 para falso suporte)
     * @return bool True se for aviso preventivo legítimo
     */
    private function ehContextoPreventivo(string $mensagemNorm, array $termos, int $idIndicador): bool {
        // Padrões de aviso preventivo e recomendação de segurança institucional
        $padroesAviso = [
            // "O banco nunca solicita sua senha ou código..."
            '/\b(o\s+banco|instituicao|central|empresa|policia|nenhum\s+funcionario|nossa\s+equipe)\s+(nunca|jamais|nao)\s+(solicita|solicitara|pede|pedira|vai\s+pedir|exige|exigira|requer|envia|manda)\b/iu',
            // "Nunca informe/compartilhe sua senha..."
            '/\b(nunca|jamais|nao|nem\s+pense\s+em)\s+(informe|compartilhe|forneca|envie|revele|divulgue|digite|passe|entregue|solicita|pede)\b.{0,60}\b(senha|codigo|token|dados|cartao)\b/iu',
            // "Senhas/códigos nunca devem ser compartilhados..."
            '/\b(senhas?|codigos?|tokens?)\b.{0,50}\b(nunca\s+deve\s+ser|jamais\s+deve\s+ser|nao\s+deve\s+ser|e\s+pessoal|sao\s+pessoais|intransferivel)\b/iu',
            // "Em caso de dúvida use o aplicativo oficial..."
            '/\b(em\s+caso\s+de\s+duvida|para\s+sua\s+seguranca|por\s+seguranca|alerta\s+de\s+seguranca)\b.{0,80}\b(use|abra|acesse|procure|consulte)\b.{0,40}\b(aplicativo\s+oficial|app\s+oficial|agencia|canais\s+oficiais)\b/iu'
        ];

        $temAvisoPreventivo = false;
        foreach ($padroesAviso as $padrao) {
            if (preg_match($padrao, $mensagemNorm)) {
                $temAvisoPreventivo = true;
                break;
            }
        }

        if (!$temAvisoPreventivo) {
            return false;
        }

        // Verifica se há ordens ativas para extrair credenciais (sem negação)
        // Ex: "O banco não pede senha, mas informe seu token em bit.ly..."
        $temPedidoAtivoCredencial = (bool) preg_match(
            '/(?<!nunca\s)(?<!jamais\s)(?<!nao\s)\b(informe|digite|compartilhe|envie|forneca|passe|valide|atualize)\b.{0,40}\b(senha|token|codigo sms|codigo de acesso|dados bancarios)\b/iu',
            $mensagemNorm
        );

        // Verifica se há vetores maliciosos ativos na mensagem
        $temLinkSuspeito = (bool) preg_match('/\b(bit\.ly|tinyurl|is\.gd|clique aqui|acesse o link encurtado|regularizar-encomenda)\b/iu', $mensagemNorm);
        $temCobranca = $this->temCobrancaAtiva($mensagemNorm);
        $temInstalacaoOuAcessoRemoto = (bool) preg_match('/\b(instale este aplicativo|instale este app|compartilhe sua tela|acesso remoto|qualquer aplicativo)\b/iu', $mensagemNorm);
        $temAmeacaBloqueio = (bool) preg_match('/\b(sua conta foi bloqueada|conta sera bloqueada|bloqueio hoje|cancelamento imediato|devolvido ao remetente|sera devolvido)\b/iu', $mensagemNorm);

        // Se houver qualquer vetor ativo de ataque, o aviso NÃO neutraliza a mensagem
        if ($temPedidoAtivoCredencial || $temLinkSuspeito || $temCobranca || $temInstalacaoOuAcessoRemoto || $temAmeacaBloqueio) {
            return false;
        }

        return true;
    }

    /**
     * Avalia se a menção a links se resume a locuções referenciais neutras (ex: "link abaixo")
     * em uma mensagem desprovida de cobrança, urgência abusiva, ameaça, pedido de senha ou URLs maliciosas.
     * Atende ao requisito: "Não classificar qualquer ocorrência da expressão 'link abaixo' automaticamente como golpe".
     *
     * @param string $mensagemNorm Texto normalizado
     * @param array $termosDetectados Termos detectados no indicador de link
     * @return bool True se for menção referencial legítima/neutra que não deve ser penalizada
     */
    private function ehApenasLinkReferencialInocente(string $mensagemNorm, array $termosDetectados): bool {
        // Termos que são intrinsecamente suspeitos ou vetores diretos de phishing
        $termosMaliciososOuEncurtadores = [
            'bit.ly', 'tinyurl', 'is.gd', 'regularizar-encomenda',
            'clique para desbloquear', 'valide sua conta', 'pagina de verificacao',
            'atualize seu cadastro', 'clique para receber', 'clique para resgatar'
        ];

        foreach ($termosDetectados as $termo) {
            $tNorm = $this->normalizarTexto($termo);
            if (in_array($tNorm, $termosMaliciososOuEncurtadores, true)) {
                return false; // Contém termo inerentemente malicioso
            }
        }

        // Verifica se há URL explicitamente suspeita no texto (encurtadores, domínio de teste .example, IP numérico)
        if (preg_match('/\b(https?:\/\/)?([a-z0-9\-]+\.)*(bit\.ly|tinyurl\.com|is\.gd|cutt\.ly|rebrand\.ly|[a-z0-9\-]+\.example|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\b/iu', $mensagemNorm)) {
            return false;
        }

        // Se houver cobrança ativa, o link faz parte do vetor financeiro do golpe
        if ($this->temCobrancaAtiva($mensagemNorm)) {
            return false;
        }

        // Se houver ameaça ativa de bloqueio, cancelamento ou devolução
        if ($this->temAmeacaAtiva($mensagemNorm)) {
            return false;
        }

        // Se houver urgência com pressão psicológica
        if ($this->temUrgenciaPressao($mensagemNorm)) {
            return false;
        }

        // Se houver tentativa de extração de credenciais
        if ($this->temPedidoCredencialAtivo($mensagemNorm)) {
            return false;
        }

        // Se houver oferta de falso prêmio condicionada
        if ($this->temFalsoPremioAtivo($mensagemNorm)) {
            return false;
        }

        // Mensagem sem vetores maliciosos onde o termo de link é meramente referencial
        return true;
    }

    /**
     * Avalia se a ocorrência de termos financeiros se resume a menções genéricas inocentes
     * (ex: "opções de pagamento na área do cliente", "não é necessário nenhum pagamento adicional")
     * sem qualquer ordem imperativa de pagamento, sem envio de chave Pix e sem vetores de golpe.
     * Atende ao requisito: "Não atribuir risco automaticamente por palavras genéricas como pagamento, taxa ou Pix sem analisar o contexto."
     *
     * @param string $mensagemNorm Texto normalizado
     * @param array $termosDetectados Termos detectados para o indicador de cobrança
     * @return bool True se a menção financeira for inofensiva e não representar golpe
     */
    private function ehMencaoFinanceiraGenericaInocente(string $mensagemNorm, array $termosDetectados): bool {
        // Se a mensagem afirma expressamente que não é necessário nenhum pagamento/taxa
        if ($this->ehContextoSemCobrancaLegitimo($mensagemNorm) && !$this->temOrdemPagamentoAtiva($mensagemNorm)) {
            return true;
        }

        // Verifica se todos os termos detectados são termos genéricos que não implicam ordem de pagamento
        $termosGenericos = ['pagamento', 'boleto', 'taxa', 'dinheiro', 'pix'];
        $apenasGenericos = true;
        foreach ($termosDetectados as $termo) {
            $tNorm = $this->normalizarTexto($termo);
            if (!in_array($tNorm, $termosGenericos, true)) {
                $apenasGenericos = false;
                break;
            }
        }

        if ($apenasGenericos) {
            // Verifica se a menção a pagamento/boleto orienta consulta aos canais oficiais legítimos
            $temOrientacaoCanalOficial = (bool) preg_match(
                '/\b(consulte|acesse|veja|confira|opcoes de pagamento|formas? de pagamento|comprovante de pagamento|segunda via)\b.{0,80}\b(area do cliente|site oficial|aplicativo oficial|app oficial|portal oficial|agencia)\b/iu',
                $mensagemNorm
            ) || (bool) preg_match(
                '/\b(area do cliente|site oficial|aplicativo oficial|app oficial)\b.{0,80}\b(pagamento|boleto)\b/iu',
                $mensagemNorm
            );

            // Se for orientação a canal oficial e não houver ordem imperativa nem outros vetores de golpe
            if ($temOrientacaoCanalOficial
                && !$this->temOrdemPagamentoAtiva($mensagemNorm)
                && !$this->temAmeacaAtiva($mensagemNorm)
                && !$this->temUrgenciaPressao($mensagemNorm)
                && !$this->temLinkSuspeitoAtivo($mensagemNorm)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se há ordem ativa/imperativa de pagamento ou transferência característica de golpe.
     */
    private function temOrdemPagamentoAtiva(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(pague|pague agora|pague a taxa|pague uma taxa|faca um pix|faça um pix|envie um pix|mande um pix|transfira|envie o valor|chave pix|taxa de liberacao|taxa para liberar|deposito antecipado|pix para a transportadora|pague para receber)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica presença de link ou encurtador suspeito ativo.
     */
    private function temLinkSuspeitoAtivo(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(bit\.ly|tinyurl|is\.gd|cutt\.ly|clique aqui|acesse o link|link abaixo|regularizar-encomenda|[a-z0-9\-]+\.example)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica se a mensagem apresenta ameaças ativas de bloqueio, suspensão, perda ou devolução.
     */
    private function temAmeacaAtiva(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(sua conta foi bloqueada|conta sera bloqueada|bloqueio hoje|cancelamento imediato|cpf bloqueado|evite cancelamento|servico cancelado|devolvido ao remetente|sera devolvido|devolucao ao remetente|evite a devolucao|evite devolucao|caso nao regularize|perdera o acesso|perda de acesso|bloqueada permanentemente)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica se a mensagem utiliza pressão psicológica de urgência extrema de golpe.
     */
    private function temUrgenciaPressao(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(urgente|imediatamente|so hoje|ainda hoje|agora mesmo|prazo final|prazo termina hoje|tempo esgotando|nao perca essa oportunidade|nao perca|ultimas horas|tempo limite|bloqueio hoje|cancelamento imediato|proximos 10 minutos|proximos minutos)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica pedido direto e ativo de senhas, códigos ou tokens (diferenciando de dados bancários/cadastrais).
     */
    private function temPedidoCredencialAtivo(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/(?<!nunca\s)(?<!jamais\s)(?<!nao\s)\b(informe|digite|compartilhe|envie|forneca|passe|valide|atualize)\b.{0,40}\b(senha|token|codigo sms|codigo de acesso|codigo de verificacao)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica se há contexto ativo de falso prêmio.
     */
    private function temFalsoPremioAtivo(string $mensagemNorm): bool {
        return (bool) preg_match(
            '/\b(parabens voce ganhou|voce ganhou um premio|voce ganhou|ganhou um premio|premio liberado|sorteio premiado|voce foi contemplado|foi selecionado para receber|selecionado para receber|receber um premio|voce foi sorteado|foi sorteado|ganhou r\$|receber seu premio)\b/iu',
            $mensagemNorm
        );
    }

    /**
     * Verifica se os termos do indicador de entrega são exclusivamente operacionais
     * em uma mensagem legítima, sem cobrança, sem links suspeitos e sem ameaças de retenção ou devolução.
     *
     * @param string $mensagemNorm Texto normalizado
     * @param array $termosDetectados Termos detectados para o indicador de entrega
     * @return bool True se for logística legítima sem qualquer sinal de golpe
     */
    private function ehLogisticaLegitimaSemAmeaca(string $mensagemNorm, array $termosDetectados): bool {
        // Se houver cobrança ou pedido de valores ativo, não é logística neutra
        if ($this->temCobrancaAtiva($mensagemNorm)) {
            return false;
        }

        // Se houver links suspeitos ou encurtados, não é logística neutra
        $temLinkSuspeito = (bool) preg_match(
            '/\b(bit\.ly|tinyurl|is\.gd|cutt\.ly|regularizar-encomenda|clique aqui|clique no link|acesse o link|link encurtado|link enviado|pelo link|link para)\b/iu',
            $mensagemNorm
        );
        if ($temLinkSuspeito) {
            return false;
        }

        // Se houver ameaça explícita de retenção, bloqueio, apreensão ou devolução
        $temAmeacaRetencao = (bool) preg_match(
            '/\b(retid[oa]|bloquead[oa]|apreendid[oa]|alfandeg|correios.*taxa|taxa.*correios|evite cancelamento|evite devolucao|multa|penalidade|devolvido ao remetente|sera devolvido|devolucao ao remetente|pendencia na alfandega)\b/iu',
            $mensagemNorm
        );
        if ($temAmeacaRetencao) {
            return false;
        }

        // Termos que sozinhos descrevem apenas operações logísticas comuns
        $termosNeutros = [
            'codigo de rastreio',
            'confirme o endereco',
            'atualizacao de endereco',
            'entrega de transportadora',
            'receber o pacote',
            'entrega pendente',
            'rastreamento pendente'
        ];

        // Verifica se TODOS os termos detectados são termos neutros
        foreach ($termosDetectados as $termo) {
            $tNorm = $this->normalizarTexto($termo);
            if (!in_array($tNorm, $termosNeutros, true)) {
                // Contém um termo inerentemente suspeito (ex: 'encomenda retida', 'taxa de liberação')
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna a chave textual de identificação visual (slug) para estilização do risco.
     *
     * @param int $pontuacao
     * @return string
     */
    public function obterNivelSlug(int $pontuacao): string {
        $pontuacao = max(0, min(ISD_MAX_SCORE, $pontuacao));

        if ($pontuacao <= 20) {
            return 'baixo';
        } elseif ($pontuacao <= 40) {
            return 'atencao';
        } elseif ($pontuacao <= 60) {
            return 'suspeito';
        } elseif ($pontuacao <= 80) {
            return 'alto';
        } else {
            return 'critico';
        }
    }

    /**
     * Trunca um texto preservando palavras inteiras para resumos e previews.
     *
     * @param string $texto
     * @param int $comprimento
     * @return string
     */
    private function resumirTexto(string $texto, int $comprimento = 120): string {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));
        if (mb_strlen($texto) <= $comprimento) {
            return $texto;
        }
        return mb_substr($texto, 0, $comprimento) . '...';
    }
}
