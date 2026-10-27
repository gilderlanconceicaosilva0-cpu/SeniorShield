<?php
/**
 * SeniorShield - Serviço de Gestão Administrativa de Usuários
 *
 * Responsável pelas regras de negócio administrativas para consulta,
 * listagem com filtros, atualização de perfil e exclusão segura de usuários.
 * Atende aos requisitos RF06, RF07, CA13, RNF01, RNF03, RNF04 e RNF06.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Helpers/utils.php';

class UserService {
    private PDO $pdo;

    /**
     * Permite injeção de dependência do PDO (para testes) ou utiliza a conexão padrão.
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDbConnection();
    }

    /**
     * Lista usuários cadastrados com suporte a busca e filtros.
     * NUNCA retorna o hash de senha para proteger dados sensíveis.
     *
     * @param array $filtros Array associativo ['busca' => string, 'tipo' => string]
     * @return array Lista de usuários
     */
    public function listar(array $filtros = []): array {
        $busca = trim((string)($filtros['busca'] ?? ''));
        $tipo  = trim((string)($filtros['tipo'] ?? ''));

        $sql = 'SELECT id_usuario, nome, email, tipo_usuario, data_cadastro
                FROM usuarios
                WHERE 1 = 1';
        $params = [];

        if ($busca !== '') {
            $sql .= ' AND (nome LIKE :termo_nome OR email LIKE :termo_email)';
            $termoLike = '%' . $busca . '%';
            $params[':termo_nome']  = $termoLike;
            $params[':termo_email'] = $termoLike;
        }

        if ($tipo === 'comum' || $tipo === 'admin') {
            $sql .= ' AND tipo_usuario = :tipo';
            $params[':tipo'] = $tipo;
        }

        $sql .= ' ORDER BY id_usuario DESC';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('UserService::listar error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém os dados detalhados de um usuário pelo ID, incluindo contadores de atividade.
     * NUNCA retorna a senha_hash.
     *
     * @param int $id ID do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function obterPorId(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                   u.id_usuario,
                   u.nome,
                   u.email,
                   u.tipo_usuario,
                   u.data_cadastro,
                   (SELECT COUNT(*) FROM ANALISES a WHERE a.id_usuario = u.id_usuario) AS total_analises,
                   (SELECT COUNT(*) FROM TENTATIVAS_APRENDIZADO t WHERE t.id_usuario = u.id_usuario) AS total_tentativas
                 FROM usuarios u
                 WHERE u.id_usuario = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            return $usuario ?: null;
        } catch (PDOException $e) {
            error_log('UserService::obterPorId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza os dados de um usuário pelo administrador.
     * Valida nome, unicidade de e-mail, tipo de usuário e regra de último administrador.
     *
     * @param int $id ID do usuário a ser atualizado
     * @param array $dados ['nome' => ..., 'email' => ..., 'tipo_usuario' => ..., 'nova_senha' => ..., 'confirmar_senha' => ...]
     * @param int $idAdminLogado ID do administrador que está executando a alteração
     * @return array ['sucesso' => bool, 'erros' => array]
     */
    public function atualizar(int $id, array $dados, int $idAdminLogado): array {
        $erros = [];

        $nome           = trim((string)($dados['nome'] ?? ''));
        $email          = trim(mb_strtolower((string)($dados['email'] ?? '')));
        $tipo           = trim((string)($dados['tipo_usuario'] ?? ''));
        $novaSenha      = (string)($dados['nova_senha'] ?? '');
        $confirmarSenha = (string)($dados['confirmar_senha'] ?? '');

        // 1. Verifica existência do usuário
        $usuarioAtual = $this->obterPorId($id);
        if (!$usuarioAtual) {
            return [
                'sucesso' => false,
                'erros'   => ['geral' => 'Usuário não encontrado no sistema.']
            ];
        }

        // 2. Validação do Nome (RF02, RNF04)
        if (empty($nome)) {
            $erros['nome'] = 'O nome não pode ficar em branco.';
        } elseif (mb_strlen($nome) < 3) {
            $erros['nome'] = 'O nome deve ter no mínimo 3 caracteres.';
        } elseif (mb_strlen($nome) > 150) {
            $erros['nome'] = 'O nome não pode exceder 150 caracteres.';
        }

        // 3. Validação do E-mail (RF02, RF03, RNF04)
        if (empty($email)) {
            $erros['email'] = 'O e-mail não pode ficar em branco.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um endereço de e-mail válido.';
        } elseif (mb_strlen($email) > 191) {
            $erros['email'] = 'O e-mail informado é excessivamente longo.';
        } else {
            // Verifica se o novo e-mail já pertence a outro usuário
            try {
                $stmtEmail = $this->pdo->prepare(
                    'SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? LIMIT 1'
                );
                $stmtEmail->execute([$email, $id]);
                if ($stmtEmail->fetch()) {
                    $erros['email'] = 'Este endereço de e-mail já está sendo utilizado por outro usuário.';
                }
            } catch (PDOException $e) {
                error_log('UserService::atualizar email check error: ' . $e->getMessage());
                $erros['geral'] = 'Erro ao verificar disponibilidade do e-mail.';
            }
        }

        // 4. Validação do Tipo de Usuário
        if (!in_array($tipo, ['comum', 'admin'], true)) {
            $erros['tipo_usuario'] = 'Perfil de usuário inválido. Escolha "comum" ou "admin".';
        }

        // 5. Regra de Segurança: Prevenção contra a perda total de administradores
        if ($usuarioAtual['tipo_usuario'] === 'admin' && $tipo === 'comum') {
            try {
                $stmtCountAdmins = $this->pdo->query(
                    "SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin'"
                );
                $totalAdmins = (int)$stmtCountAdmins->fetchColumn();

                if ($totalAdmins <= 1) {
                    $erros['tipo_usuario'] = 'Não é permitido alterar este perfil para "comum", pois este é o único administrador ativo no sistema.';
                }
            } catch (PDOException $e) {
                error_log('UserService::atualizar admin count error: ' . $e->getMessage());
            }
        }

        // 6. Validação de Nova Senha (opcional)
        $alterarSenha = false;
        if (!empty($novaSenha) || !empty($confirmarSenha)) {
            if (mb_strlen($novaSenha) < 8) {
                $erros['nova_senha'] = 'A nova senha deve ter no mínimo 8 caracteres.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erros['confirmar_senha'] = 'As senhas informadas não coincidem.';
            } else {
                $alterarSenha = true;
            }
        }

        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'erros'   => $erros
            ];
        }

        // 7. Execução da Atualização via Prepared Statement
        try {
            if ($alterarSenha) {
                $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmtUpdate = $this->pdo->prepare(
                    'UPDATE usuarios
                     SET nome = ?, email = ?, tipo_usuario = ?, senha_hash = ?
                     WHERE id_usuario = ?'
                );
                $stmtUpdate->execute([$nome, $email, $tipo, $novoHash, $id]);
            } else {
                $stmtUpdate = $this->pdo->prepare(
                    'UPDATE usuarios
                     SET nome = ?, email = ?, tipo_usuario = ?
                     WHERE id_usuario = ?'
                );
                $stmtUpdate->execute([$nome, $email, $tipo, $id]);
            }

            // Se o próprio administrador editou seus dados, atualiza a sessão ativa
            if ($id === $idAdminLogado) {
                initSecureSession();
                $_SESSION['usuario_nome']  = $nome;
                $_SESSION['usuario_email'] = $email;
                $_SESSION['tipo_usuario']  = $tipo;
            }

            return [
                'sucesso' => true,
                'erros'   => []
            ];
        } catch (PDOException $e) {
            error_log('UserService::atualizar error: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erros'   => [
                    'geral' => 'Ocorreu um erro ao atualizar os dados do usuário. Tente novamente mais tarde.'
                ]
            ];
        }
    }

    /**
     * Exclui um usuário do sistema de forma segura.
     * Impede autoexclusão e exclusão do último administrador.
     *
     * @param int $id ID do usuário a ser excluído
     * @param int $idAdminLogado ID do administrador autenticado que solicitou a exclusão
     * @return array ['sucesso' => bool, 'erro' => ?string]
     */
    public function excluir(int $id, int $idAdminLogado): array {
        if ($id <= 0) {
            return [
                'sucesso' => false,
                'erro'    => 'Identificador de usuário inválido.'
            ];
        }

        // 1. Impede que o administrador exclua sua própria conta ativa
        if ($id === $idAdminLogado) {
            return [
                'sucesso' => false,
                'erro'    => 'Operação não permitida: Você não pode excluir sua própria conta de administrador enquanto estiver conectado.'
            ];
        }

        // 2. Busca o usuário a ser excluído
        $usuario = $this->obterPorId($id);
        if (!$usuario) {
            return [
                'sucesso' => false,
                'erro'    => 'Usuário não encontrado para exclusão.'
            ];
        }

        // 3. Impede a exclusão caso seja o único administrador
        if ($usuario['tipo_usuario'] === 'admin') {
            try {
                $stmtAdmins = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin' AND id_usuario != ?"
                );
                $stmtAdmins->execute([$id]);
                $outrosAdmins = (int)$stmtAdmins->fetchColumn();

                if ($outrosAdmins === 0) {
                    return [
                        'sucesso' => false,
                        'erro'    => 'Operação não permitida: Este é o único administrador do sistema e não pode ser excluído.'
                    ];
                }
            } catch (PDOException $e) {
                error_log('UserService::excluir admin check error: ' . $e->getMessage());
                return [
                    'sucesso' => false,
                    'erro'    => 'Falha ao verificar administradores ativos.'
                ];
            }
        }

        // 4. Executa a exclusão por Prepared Statement
        // Nota: O schema possui ON DELETE CASCADE em ANALISES e TENTATIVAS_APRENDIZADO
        try {
            $stmtDelete = $this->pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            $stmtDelete->execute([$id]);

            return [
                'sucesso' => true,
                'erro'    => null
            ];
        } catch (PDOException $e) {
            error_log('UserService::excluir error: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erro'    => 'Não foi possível excluir o usuário devido a dependências ou erro no banco de dados.'
            ];
        }
    }

    /**
     * Retorna métricas estatísticas consolidadas para o painel administrativo.
     *
     * @return array
     */
    public function obterMetricasGerais(): array {
        try {
            $totalUsuarios = (int)$this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            $totalComuns   = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'comum'")->fetchColumn();
            $totalAdmins   = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin'")->fetchColumn();
            $totalAnalises = (int)$this->pdo->query('SELECT COUNT(*) FROM ANALISES')->fetchColumn();
            $totalIndicadores = (int)$this->pdo->query('SELECT COUNT(*) FROM INDICADORES')->fetchColumn();
            $totalConteudos   = (int)$this->pdo->query('SELECT COUNT(*) FROM CONTEUDOS')->fetchColumn();
            $totalQuestoes    = (int)$this->pdo->query('SELECT COUNT(*) FROM QUESTOES_APRENDIZADO')->fetchColumn();

            // Últimos 5 usuários cadastrados (sem senha_hash)
            $stmtRecentes = $this->pdo->query(
                'SELECT id_usuario, nome, email, tipo_usuario, data_cadastro
                 FROM usuarios
                 ORDER BY id_usuario DESC
                 LIMIT 5'
            );
            $recentes = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_usuarios'    => $totalUsuarios,
                'total_comuns'      => $totalComuns,
                'total_admins'      => $totalAdmins,
                'total_analises'    => $totalAnalises,
                'total_indicadores' => $totalIndicadores,
                'total_conteudos'   => $totalConteudos,
                'total_questoes'    => $totalQuestoes,
                'usuarios_recentes' => $recentes
            ];
        } catch (PDOException $e) {
            error_log('UserService::obterMetricasGerais error: ' . $e->getMessage());
            return [
                'total_usuarios'    => 0,
                'total_comuns'      => 0,
                'total_admins'      => 0,
                'total_analises'    => 0,
                'total_indicadores' => 0,
                'total_conteudos'   => 0,
                'total_questoes'    => 0,
                'usuarios_recentes' => []
            ];
        }
    }
}
