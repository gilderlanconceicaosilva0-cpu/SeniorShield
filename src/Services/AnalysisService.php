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

        // 3. Processamento e Detecção de Indicadores (RF09, RF10)
        $mensagemNormalizada = $this->normalizarTexto($mensagemLimpa);

        $mensagemPreventiva = preg_match(
            '/\b(nunca|nao|jamais)\s+(informe|compartilhe|forneca|envie|revele|divulgue)\b.{0,80}\b(senha|codigo|token|dados bancarios)\b/iu',
            $mensagemNormalizada
            ) === 1;
            $mensagemSemCobranca = preg_match(
                '/\b(nao preciso pagar|nao precisa pagar|nao e necessario pagar|nao ha cobranca|sem custo|entrega gratuita|entrega gratis)\b/iu',
                $mensagemNormalizada
            ) === 1;

        $cobrancaExplicita = preg_match(
                '/\b(pague|pagar uma taxa|pague a taxa|taxa para liberar|taxa de liberacao|transfira|faca um deposito|faca o pagamento)\b/iu',
                $mensagemNormalizada
        ) === 1;
         $indicadoresDetectados = [];
         $somaPesos = 0;

            foreach ($indicadoresCadastrados as $indicador) {
            $palavrasChave = array_filter(array_map('trim', explode(',', $indicador['palavras_chave'])));
            $termosEncontrados = [];

            foreach ($palavrasChave as $termo) {
                if ($termo === '') {
                    continue;
                }

                $termoNormalizado = $this->normalizarTexto($termo);

                // Expressão regular com fronteiras seguras para letras e números
                $padrao = '/(?<![a-z0-9])' . preg_quote($termoNormalizado, '/') . '(?![a-z0-9])/iu';

                if (preg_match($padrao, $mensagemNormalizada)) {
                    $termosEncontrados[] = $termo;
                }
            }


            $idIndicador = (int) $indicador['id_indicador'];

            // Ignora indicadores de senha e falso suporte em avisos preventivos.
            if (
                in_array($idIndicador, [4, 10], true)
                && $mensagemPreventiva
            ) {
                continue;
}

            // Não considera uma negação de cobrança como pedido de pagamento.
            if (
                $idIndicador === 2
                && $mensagemSemCobranca
                && !$cobrancaExplicita
            ) {
                continue;
            }

            // Evita falso positivo em mensagens de sorteio sem cobrança.
            if (
                (int) $indicador['id_indicador'] === 5
                && $mensagemSemCobranca
                && !$cobrancaExplicita
            ) {
                continue;
            }

            // Evita falso positivo em mensagens de sorteio sem cobrança.
            if (
                $idIndicador === 5
                && $mensagemSemCobranca
                && !$cobrancaExplicita
            ) {
                continue;
            }
            // Se pelo menos um termo chave foi encontrado, o indicador é registrado (RF10)
            if (!empty($termosEncontrados)) {

                    $pesoBase = (int) $indicador['peso'];

                    // Remove duplicatas que diferem apenas por acentos ou maiúsculas.
                    $termosUnicos = [];

                    foreach ($termosEncontrados as $termoEncontrado) {
                        $chave = $this->normalizarTexto($termoEncontrado);
                        $termosUnicos[$chave] = $termoEncontrado;
                    }

                    $termosEncontrados = array_values($termosUnicos);

                    // Bônus de 5 pontos por sinal adicional, limitado a 10.
                    $bonusSinais = min(
                        10,
                        max(0, (count($termosEncontrados) - 1) * 5)
                    );

                    // O bônus não pode elevar este indicador acima de 30 pontos.
                    $pesoAplicado = min(30, $pesoBase + $bonusSinais);

                    $somaPesos += $pesoAplicado;


                $indicadoresDetectados[] = [
                    'id_indicador'      => (int)$indicador['id_indicador'],
                    'nome'              => $indicador['nome'],
                    'descricao'         => $indicador['descricao'] ?? '',
                    'peso'              => $pesoAplicado,
                    'termos_detectados' => array_values(array_unique($termosEncontrados))
                ];
            }
        }

        // 4. Cálculo do ISD e Limitação a 100 (RF11, RF12)
        $pontuacaoIsd = min(ISD_MAX_SCORE, max(0, $somaPesos));

        // 5. Classificação Oficial do Risco (RF13)
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
    private function normalizarTexto(string $texto): string {
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
        return strtr($texto, $mapaAcentos);
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
