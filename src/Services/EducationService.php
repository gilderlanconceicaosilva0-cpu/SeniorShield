<?php
/**
 * SeniorShield - Serviço de Conteúdos Educativos e Modo Aprendizado
 *
 * Responsável pela gestão e exibição de artigos educativos, simulador de golpes,
 * registro de tentativas e cálculo do desempenho de aprendizado do usuário.
 * Atende aos requisitos RF24-RF35, RF43-RF45, CA09-CA12, CA15 e RNF06.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Helpers/utils.php';

class EducationService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDbConnection();
    }

    // =========================================================================
    // 1. CONTEÚDOS EDUCATIVOS (RF24, RF25, RF26, RF27, CA09, CA15)
    // =========================================================================

    /**
     * Lista conteúdos educativos com filtros de busca, tipo e status.
     *
     * @param bool|null $somenteAtivos
     * @param string $tipo
     * @param string $busca
     * @return array
     */
    public function listarConteudos(?bool $somenteAtivos = true, string $tipo = '', string $busca = ''): array {
        $sql = 'SELECT id_conteudo, titulo, texto, tipo, ativo, data_cadastro
                FROM CONTEUDOS
                WHERE 1 = 1';
        $params = [];

        if ($somenteAtivos !== null) {
            $sql .= ' AND ativo = :ativo';
            $params[':ativo'] = $somenteAtivos ? 1 : 0;
        }

        if ($tipo !== '') {
            $sql .= ' AND tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= ' AND (titulo LIKE :busca_titulo OR texto LIKE :busca_texto)';
            $termo = '%' . $busca . '%';
            $params[':busca_titulo'] = $termo;
            $params[':busca_texto']  = $termo;
        }

        $sql .= ' ORDER BY ativo DESC, data_cadastro DESC, id_conteudo DESC';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $conteudos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($conteudos as &$c) {
                $c['data_formatada'] = format_datetime_br($c['data_cadastro']);
                $c['preview_texto'] = mb_substr(strip_tags($c['texto']), 0, 140) . '...';
            }

            return $conteudos;
        } catch (PDOException $e) {
            error_log('EducationService::listarConteudos erro: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém um conteúdo educativo pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obterConteudoPorId(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id_conteudo, titulo, texto, tipo, ativo, data_cadastro FROM CONTEUDOS WHERE id_conteudo = ? LIMIT 1');
            $stmt->execute([$id]);
            $conteudo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($conteudo) {
                $conteudo['data_formatada'] = format_datetime_br($conteudo['data_cadastro']);
            }

            return $conteudo ?: null;
        } catch (PDOException $e) {
            error_log('EducationService::obterConteudoPorId erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cadastra ou atualiza um conteúdo educativo (RF25, RF26, CA15).
     *
     * @param array $dados
     * @param int|null $id
     * @return array
     */
    public function salvarConteudo(array $dados, ?int $id = null): array {
        $erros = [];

        $titulo = trim((string)($dados['titulo'] ?? ''));
        $texto  = trim((string)($dados['texto'] ?? ''));
        $tipo   = trim((string)($dados['tipo'] ?? 'artigo'));
        $ativo  = !empty($dados['ativo']) ? 1 : 0;

        if (empty($titulo)) {
            $erros['titulo'] = 'O título do conteúdo educativo é obrigatório.';
        } elseif (mb_strlen($titulo) < 3) {
            $erros['titulo'] = 'O título deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($titulo) > 200) {
            $erros['titulo'] = 'O título não pode exceder 200 caracteres.';
        }

        if (empty($texto)) {
            $erros['texto'] = 'O texto ou orientação de segurança é obrigatório.';
        } elseif (mb_strlen($texto) < 10) {
            $erros['texto'] = 'O conteúdo deve ter pelo menos 10 caracteres explicativos.';
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'erros' => $erros, 'id' => $id];
        }

        try {
            if ($id !== null && $id > 0) {
                $stmt = $this->pdo->prepare('UPDATE CONTEUDOS SET titulo = ?, texto = ?, tipo = ?, ativo = ? WHERE id_conteudo = ?');
                $stmt->execute([$titulo, $texto, $tipo, $ativo, $id]);
                return ['sucesso' => true, 'erros' => [], 'id' => $id];
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO CONTEUDOS (titulo, texto, tipo, ativo, data_cadastro) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([$titulo, $texto, $tipo, $ativo]);
                $novoId = (int)$this->pdo->lastInsertId();
                return ['sucesso' => true, 'erros' => [], 'id' => $novoId];
            }
        } catch (PDOException $e) {
            error_log('EducationService::salvarConteudo erro: ' . $e->getMessage());
            return ['sucesso' => false, 'erros' => ['geral' => 'Erro ao salvar o conteúdo no banco de dados.'], 'id' => $id];
        }
    }

    /**
     * Alterna o status ativo/inativo de um conteúdo educativo (RF27).
     *
     * @param int $id
     * @return bool
     */
    public function alternarStatusConteudo(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE CONTEUDOS SET ativo = 1 - ativo WHERE id_conteudo = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('EducationService::alternarStatusConteudo erro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um conteúdo educativo.
     *
     * @param int $id
     * @return bool
     */
    public function excluirConteudo(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM CONTEUDOS WHERE id_conteudo = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('EducationService::excluirConteudo erro: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 2. QUESTÕES DO MODO APRENDIZADO (RF28, RF29, RF43, RF44, RF45, CA10)
    // =========================================================================

    /**
     * Lista todas as questões com suporte a filtros de tipo de golpe e busca.
     *
     * @param bool|null $somenteAtivas
     * @param int|null $idTipoGolpe
     * @param string $busca
     * @return array
     */
    public function listarQuestoes(?bool $somenteAtivas = true, ?int $idTipoGolpe = null, string $busca = ''): array {
        $sql = 'SELECT
                  qa.id_questao,
                  qa.mensagem,
                  qa.resposta_correta,
                  qa.explicacao,
                  qa.id_tipo_golpe,
                  qa.ativo,
                  tg.nome AS nome_tipo_golpe,
                  (SELECT COUNT(*) FROM TENTATIVAS_APRENDIZADO ta WHERE ta.id_questao = qa.id_questao) AS total_tentativas,
                  (SELECT COUNT(*) FROM TENTATIVAS_APRENDIZADO ta WHERE ta.id_questao = qa.id_questao AND ta.acertou = 1) AS total_acertos
                FROM QUESTOES_APRENDIZADO qa
                LEFT JOIN TIPOS_GOLPE tg ON qa.id_tipo_golpe = tg.id_tipo_golpe
                WHERE 1 = 1';
        $params = [];

        if ($somenteAtivas !== null) {
            $sql .= ' AND qa.ativo = :ativo';
            $params[':ativo'] = $somenteAtivas ? 1 : 0;
        }

        if ($idTipoGolpe !== null && $idTipoGolpe > 0) {
            $sql .= ' AND qa.id_tipo_golpe = :id_tipo';
            $params[':id_tipo'] = $idTipoGolpe;
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= ' AND (qa.mensagem LIKE :busca_msg OR qa.explicacao LIKE :busca_exp)';
            $termo = '%' . $busca . '%';
            $params[':busca_msg'] = $termo;
            $params[':busca_exp'] = $termo;
        }

        $sql .= ' ORDER BY qa.ativo DESC, qa.id_questao ASC';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('EducationService::listarQuestoes erro: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém os dados de uma questão pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obterQuestaoPorId(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT qa.id_questao, qa.mensagem, qa.resposta_correta, qa.explicacao, qa.id_tipo_golpe, qa.ativo, tg.nome AS nome_tipo_golpe
                 FROM QUESTOES_APRENDIZADO qa
                 LEFT JOIN TIPOS_GOLPE tg ON qa.id_tipo_golpe = tg.id_tipo_golpe
                 WHERE qa.id_questao = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            error_log('EducationService::obterQuestaoPorId erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cadastra ou atualiza uma questão simulada (RF43, RF44).
     *
     * @param array $dados
     * @param int|null $id
     * @return array
     */
    public function salvarQuestao(array $dados, ?int $id = null): array {
        $erros = [];

        $mensagem        = trim((string)($dados['mensagem'] ?? ''));
        $respostaCorreta = strtolower(trim((string)($dados['resposta_correta'] ?? 'golpe')));
        $explicacao      = trim((string)($dados['explicacao'] ?? ''));
        $idTipoGolpe     = !empty($dados['id_tipo_golpe']) ? (int)$dados['id_tipo_golpe'] : null;
        $ativo           = !empty($dados['ativo']) ? 1 : 0;

        if (empty($mensagem)) {
            $erros['mensagem'] = 'A mensagem da situação simulada é obrigatória.';
        }

        if (!in_array($respostaCorreta, ['golpe', 'seguro'], true)) {
            $erros['resposta_correta'] = 'A resposta correta deve ser "golpe" ou "seguro".';
        }

        if (empty($explicacao)) {
            $erros['explicacao'] = 'A explicação didática dos sinais identificados é obrigatória.';
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'erros' => $erros, 'id' => $id];
        }

        try {
            if ($id !== null && $id > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE QUESTOES_APRENDIZADO
                     SET mensagem = ?, resposta_correta = ?, explicacao = ?, id_tipo_golpe = ?, ativo = ?
                     WHERE id_questao = ?'
                );
                $stmt->execute([$mensagem, $respostaCorreta, $explicacao, $idTipoGolpe, $ativo, $id]);
                return ['sucesso' => true, 'erros' => [], 'id' => $id];
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO QUESTOES_APRENDIZADO (mensagem, resposta_correta, explicacao, id_tipo_golpe, ativo)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$mensagem, $respostaCorreta, $explicacao, $idTipoGolpe, $ativo]);
                $novoId = (int)$this->pdo->lastInsertId();
                return ['sucesso' => true, 'erros' => [], 'id' => $novoId];
            }
        } catch (PDOException $e) {
            error_log('EducationService::salvarQuestao erro: ' . $e->getMessage());
            return ['sucesso' => false, 'erros' => ['geral' => 'Erro ao salvar questão no banco de dados.'], 'id' => $id];
        }
    }

    /**
     * Alterna o status ativo/inativo de uma questão simulada (RF45).
     *
     * @param int $id
     * @return bool
     */
    public function alternarStatusQuestao(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE QUESTOES_APRENDIZADO SET ativo = 1 - ativo WHERE id_questao = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('EducationService::alternarStatusQuestao erro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui uma questão simulada.
     *
     * @param int $id
     * @return bool
     */
    public function excluirQuestao(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM QUESTOES_APRENDIZADO WHERE id_questao = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('EducationService::excluirQuestao erro: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 3. MODO APRENDIZADO & TENTATIVAS (RF28-RF35, CA10, CA11, CA12)
    // =========================================================================

    /**
     * Processa a resposta enviada pelo usuário em uma questão simulada (RF30-RF34, CA11, CA12).
     *
     * @param int $idQuestao
     * @param string $respostaUsuario 'golpe' ou 'seguro'
     * @param int|null $idUsuario
     * @return array
     */
    public function processarResposta(int $idQuestao, string $respostaUsuario, ?int $idUsuario = null): array {
        $respostaUsuario = strtolower(trim($respostaUsuario));

        if (!in_array($respostaUsuario, ['golpe', 'seguro'], true)) {
            return [
                'sucesso' => false,
                'erro'    => 'Resposta inválida. Escolha "É Golpe" ou "É Seguro".'
            ];
        }

        $questao = $this->obterQuestaoPorId($idQuestao);
        if (!$questao || !$questao['ativo']) {
            return [
                'sucesso' => false,
                'erro'    => 'Questão simulada não encontrada ou inativa.'
            ];
        }

        // RF31: Verifica se acertou
        $acertou = ($respostaUsuario === strtolower($questao['resposta_correta'])) ? 1 : 0;

        // RF33, RF34, CA12: Registra a tentativa no banco de dados se houver usuário
        $idTentativa = null;
        if ($idUsuario !== null && $idUsuario > 0) {
            try {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO TENTATIVAS_APRENDIZADO (id_usuario, id_questao, resposta_usuario, acertou, data_tentativa)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $stmt->execute([$idUsuario, $idQuestao, $respostaUsuario, $acertou]);
                $idTentativa = (int)$this->pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log('EducationService::processarResposta erro ao salvar tentativa: ' . $e->getMessage());
            }
        }

        return [
            'sucesso'          => true,
            'id_tentativa'     => $idTentativa,
            'acertou'          => (bool)$acertou,
            'resposta_usuario' => $respostaUsuario,
            'resposta_correta' => $questao['resposta_correta'],
            'explicacao'       => $questao['explicacao'],
            'questao'          => $questao
        ];
    }

    /**
     * Obtém o desempenho detalhado de um usuário no Modo Aprendizado (RF35).
     *
     * @param int $idUsuario
     * @return array
     */
    public function obterDesempenhoUsuario(int $idUsuario): array {
        $desempenho = [
            'total_tentativas' => 0,
            'total_acertos'    => 0,
            'total_erros'      => 0,
            'taxa_acerto'      => 0,
            'questoes_feitas'  => 0,
            'total_questoes'   => 0
        ];

        if ($idUsuario <= 0) {
            return $desempenho;
        }

        try {
            // Contagem geral de tentativas
            $stmt = $this->pdo->prepare(
                'SELECT
                   COUNT(*) AS total_tentativas,
                   SUM(CASE WHEN acertou = 1 THEN 1 ELSE 0 END) AS total_acertos,
                   SUM(CASE WHEN acertou = 0 THEN 1 ELSE 0 END) AS total_erros,
                   COUNT(DISTINCT id_questao) AS questoes_distintas
                 FROM TENTATIVAS_APRENDIZADO
                 WHERE id_usuario = ?'
            );
            $stmt->execute([$idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            // Total de questões ativas disponíveis no sistema
            $stmtTotalQ = $this->pdo->query('SELECT COUNT(*) FROM QUESTOES_APRENDIZADO WHERE ativo = 1');
            $totalQuestoesAtivas = (int)$stmtTotalQ->fetchColumn();

            if ($res) {
                $tot = (int)($res['total_tentativas'] ?? 0);
                $acertos = (int)($res['total_acertos'] ?? 0);

                $desempenho['total_tentativas'] = $tot;
                $desempenho['total_acertos']    = $acertos;
                $desempenho['total_erros']      = (int)($res['total_erros'] ?? 0);
                $desempenho['questoes_feitas']  = (int)($res['questoes_distintas'] ?? 0);
                $desempenho['total_questoes']   = $totalQuestoesAtivas;
                $desempenho['taxa_acerto']      = $tot > 0 ? (int)round(($acertos / $tot) * 100) : 0;
            }

            return $desempenho;
        } catch (PDOException $e) {
            error_log('EducationService::obterDesempenhoUsuario erro: ' . $e->getMessage());
            return $desempenho;
        }
    }
}
