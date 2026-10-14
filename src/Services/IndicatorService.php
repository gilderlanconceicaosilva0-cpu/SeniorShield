<?php
/**
 * SeniorShield - Serviço de Gestão de Indicadores e Tipos de Golpe
 *
 * Responsável pelas regras administrativas de cadastro, consulta, atualização,
 * ajuste de pesos e ativação/desativação de indicadores de risco e categorias de golpe.
 * Atende aos requisitos RF36-RF39, RF40-RF42, CA14 e RNF03-RNF07.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Helpers/utils.php';

class IndicatorService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDbConnection();
    }

    // =========================================================================
    // 1. GERENCIAMENTO DE INDICADORES (RF36, RF37, RF38, RF39, CA14)
    // =========================================================================

    /**
     * Lista todos os indicadores cadastrados com opções de filtro.
     *
     * @param bool|null $somenteAtivos Se true, filtra apenas ativos; se false, inativos; null para todos
     * @param string $busca Termo para filtrar por nome ou palavras-chave
     * @return array
     */
    public function listarIndicadores(?bool $somenteAtivos = null, string $busca = ''): array {
        $sql = 'SELECT
                  i.id_indicador,
                  i.nome,
                  i.descricao,
                  i.palavras_chave,
                  i.peso,
                  i.ativo,
                  (SELECT COUNT(*) FROM ANALISE_INDICADORES ai WHERE ai.id_indicador = i.id_indicador) AS total_ocorrencias
                FROM INDICADORES i
                WHERE 1 = 1';
        $params = [];

        if ($somenteAtivos !== null) {
            $sql .= ' AND i.ativo = :ativo';
            $params[':ativo'] = $somenteAtivos ? 1 : 0;
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= ' AND (i.nome LIKE :busca_nome OR i.palavras_chave LIKE :busca_kw OR i.descricao LIKE :busca_desc)';
            $termo = '%' . $busca . '%';
            $params[':busca_nome'] = $termo;
            $params[':busca_kw']   = $termo;
            $params[':busca_desc'] = $termo;
        }

        $sql .= ' ORDER BY i.ativo DESC, i.peso DESC, i.id_indicador ASC';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('IndicatorService::listarIndicadores erro: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém os dados completos de um indicador pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obterIndicadorPorId(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id_indicador, nome, descricao, palavras_chave, peso, ativo
                 FROM INDICADORES
                 WHERE id_indicador = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            error_log('IndicatorService::obterIndicadorPorId erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cadastra ou atualiza um indicador no banco de dados (RF36, RF37, RF38).
     *
     * @param array $dados Dados submetidos ['nome', 'descricao', 'palavras_chave', 'peso', 'ativo']
     * @param int|null $id ID do indicador se for edição, ou null se for novo cadastro
     * @return array ['sucesso' => bool, 'erros' => array, 'id' => ?int]
     */
    public function salvarIndicador(array $dados, ?int $id = null): array {
        $erros = [];

        $nome          = trim((string)($dados['nome'] ?? ''));
        $descricao     = trim((string)($dados['descricao'] ?? ''));
        $palavrasChave = trim((string)($dados['palavras_chave'] ?? ''));
        $peso          = filter_var($dados['peso'] ?? 10, FILTER_VALIDATE_INT);
        $ativo         = !empty($dados['ativo']) ? 1 : 0;

        // 1. Validação do Nome
        if (empty($nome)) {
            $erros['nome'] = 'O nome do indicador é obrigatório.';
        } elseif (mb_strlen($nome) < 3) {
            $erros['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($nome) > 100) {
            $erros['nome'] = 'O nome não pode exceder 100 caracteres.';
        }

        // 2. Validação das Palavras-Chave
        if (empty($palavrasChave)) {
            $erros['palavras_chave'] = 'Informe ao menos uma palavra ou frase suspeita separada por vírgula.';
        }

        // 3. Validação do Peso (RF38)
        if ($peso === false || $peso < 1 || $peso > 100) {
            $erros['peso'] = 'O peso do indicador deve ser um número inteiro entre 1 e 100.';
        }

        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'erros'   => $erros,
                'id'      => $id
            ];
        }

        // Limpa e normaliza a lista de palavras-chave separadas por vírgula
        $listaKw = array_filter(array_map('trim', explode(',', $palavrasChave)));
        $palavrasChaveLimpo = implode(', ', $listaKw);

        try {
            if ($id !== null && $id > 0) {
                // Atualização (RF37, RF38)
                $stmt = $this->pdo->prepare(
                    'UPDATE INDICADORES
                     SET nome = ?, descricao = ?, palavras_chave = ?, peso = ?, ativo = ?
                     WHERE id_indicador = ?'
                );
                $stmt->execute([$nome, $descricao, $palavrasChaveLimpo, $peso, $ativo, $id]);

                return [
                    'sucesso' => true,
                    'erros'   => [],
                    'id'      => $id
                ];
            } else {
                // Inserção de Novo Indicador (RF36)
                $stmt = $this->pdo->prepare(
                    'INSERT INTO INDICADORES (nome, descricao, palavras_chave, peso, ativo)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$nome, $descricao, $palavrasChaveLimpo, $peso, $ativo]);
                $novoId = (int)$this->pdo->lastInsertId();

                return [
                    'sucesso' => true,
                    'erros'   => [],
                    'id'      => $novoId
                ];
            }
        } catch (PDOException $e) {
            error_log('IndicatorService::salvarIndicador erro: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erros'   => ['geral' => 'Erro ao gravar dados do indicador no banco de dados.'],
                'id'      => $id
            ];
        }
    }

    /**
     * Alterna o status ativo/inativo de um indicador (RF39).
     *
     * @param int $id
     * @return bool
     */
    public function alternarStatusIndicador(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE INDICADORES SET ativo = 1 - ativo WHERE id_indicador = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('IndicatorService::alternarStatusIndicador erro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um indicador se não possuir histórico associado em análises.
     *
     * @param int $id
     * @return array ['sucesso' => bool, 'mensagem' => string]
     */
    public function excluirIndicador(int $id): array {
        if ($id <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Indicador inválido.'];
        }

        try {
            // Verifica se está vinculado a análises anteriores
            $stmtCheck = $this->pdo->prepare('SELECT COUNT(*) FROM ANALISE_INDICADORES WHERE id_indicador = ?');
            $stmtCheck->execute([$id]);
            $count = (int)$stmtCheck->fetchColumn();

            if ($count > 0) {
                // Para manter a integridade referencial histórica, desativa em vez de apagar
                $this->pdo->prepare('UPDATE INDICADORES SET ativo = 0 WHERE id_indicador = ?')->execute([$id]);
                return [
                    'sucesso'  => true,
                    'mensagem' => "O indicador possui {$count} ocorrências no histórico e foi desativado para preservar os relatórios anteriores."
                ];
            }

            $stmtDel = $this->pdo->prepare('DELETE FROM INDICADORES WHERE id_indicador = ?');
            $stmtDel->execute([$id]);

            return [
                'sucesso'  => true,
                'mensagem' => 'Indicador excluído com sucesso.'
            ];
        } catch (PDOException $e) {
            error_log('IndicatorService::excluirIndicador erro: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Erro interno ao tentar remover o indicador.'];
        }
    }

    // =========================================================================
    // 2. GERENCIAMENTO DE TIPOS DE GOLPE (RF40, RF41, RF42)
    // =========================================================================

    /**
     * Lista todos os tipos de golpe cadastrados.
     *
     * @param bool|null $somenteAtivos
     * @param string $busca
     * @return array
     */
    public function listarTiposGolpe(?bool $somenteAtivos = null, string $busca = ''): array {
        $sql = 'SELECT
                  tg.id_tipo_golpe,
                  tg.nome,
                  tg.descricao,
                  tg.ativo,
                  (SELECT COUNT(*) FROM QUESTOES_APRENDIZADO qa WHERE qa.id_tipo_golpe = tg.id_tipo_golpe) AS total_questoes
                FROM TIPOS_GOLPE tg
                WHERE 1 = 1';
        $params = [];

        if ($somenteAtivos !== null) {
            $sql .= ' AND tg.ativo = :ativo';
            $params[':ativo'] = $somenteAtivos ? 1 : 0;
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= ' AND (tg.nome LIKE :busca_nome OR tg.descricao LIKE :busca_desc)';
            $termo = '%' . $busca . '%';
            $params[':busca_nome'] = $termo;
            $params[':busca_desc'] = $termo;
        }

        $sql .= ' ORDER BY tg.ativo DESC, tg.id_tipo_golpe ASC';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('IndicatorService::listarTiposGolpe erro: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém um tipo de golpe pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obterTipoGolpePorId(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id_tipo_golpe, nome, descricao, ativo FROM TIPOS_GOLPE WHERE id_tipo_golpe = ? LIMIT 1');
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            error_log('IndicatorService::obterTipoGolpePorId erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cadastra ou atualiza um tipo de golpe (RF40, RF41).
     *
     * @param array $dados
     * @param int|null $id
     * @return array
     */
    public function salvarTipoGolpe(array $dados, ?int $id = null): array {
        $erros = [];

        $nome      = trim((string)($dados['nome'] ?? ''));
        $descricao = trim((string)($dados['descricao'] ?? ''));
        $ativo     = !empty($dados['ativo']) ? 1 : 0;

        if (empty($nome)) {
            $erros['nome'] = 'O nome da categoria do golpe é obrigatório.';
        } elseif (mb_strlen($nome) < 3) {
            $erros['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($nome) > 100) {
            $erros['nome'] = 'O nome não pode exceder 100 caracteres.';
        }

        if (!empty($erros)) {
            return ['sucesso' => false, 'erros' => $erros, 'id' => $id];
        }

        try {
            if ($id !== null && $id > 0) {
                // Atualização (RF41)
                $stmt = $this->pdo->prepare('UPDATE TIPOS_GOLPE SET nome = ?, descricao = ?, ativo = ? WHERE id_tipo_golpe = ?');
                $stmt->execute([$nome, $descricao, $ativo, $id]);
                return ['sucesso' => true, 'erros' => [], 'id' => $id];
            } else {
                // Inserção (RF40)
                $stmt = $this->pdo->prepare('INSERT INTO TIPOS_GOLPE (nome, descricao, ativo) VALUES (?, ?, ?)');
                $stmt->execute([$nome, $descricao, $ativo]);
                $novoId = (int)$this->pdo->lastInsertId();
                return ['sucesso' => true, 'erros' => [], 'id' => $novoId];
            }
        } catch (PDOException $e) {
            error_log('IndicatorService::salvarTipoGolpe erro: ' . $e->getMessage());
            return ['sucesso' => false, 'erros' => ['geral' => 'Erro ao salvar o tipo de golpe no banco.'], 'id' => $id];
        }
    }

    /**
     * Alterna status ativo/inativo de um tipo de golpe (RF42).
     *
     * @param int $id
     * @return bool
     */
    public function alternarStatusTipoGolpe(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE TIPOS_GOLPE SET ativo = 1 - ativo WHERE id_tipo_golpe = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('IndicatorService::alternarStatusTipoGolpe erro: ' . $e->getMessage());
            return false;
        }
    }
}
