<?php
/**
 * SeniorShield - Serviço de Autenticação e Gestão de Usuários
 *
 * Responsável pelas regras de negócio de cadastro, validação de dados,
 * hash seguro de senhas, autenticação e encerramento de sessão.
 * Atende aos requisitos RF01-RF06, CA01-CA03, CA16 e RNF01-RNF07.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Helpers/utils.php';

class AuthService {
    private PDO $pdo;

    /**
     * Permite injeção de dependência do PDO (útil para testes unitários)
     * ou utiliza a conexão padrão do sistema.
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? getDbConnection();
    }

    /**
     * Realiza o cadastro de um novo usuário comum no sistema.
     *
     * @param string $nome Nome completo
     * @param string $email Endereço de e-mail
     * @param string $senha Senha escolhida
     * @param string $confirmarSenha Confirmação da senha
     * @return array ['sucesso' => bool, 'erros' => array, 'id_usuario' => ?int]
     */
    public function cadastrar(string $nome, string $email, string $senha, string $confirmarSenha = ''): array {
        $erros = [];
        $nome  = trim($nome);
        $email = trim(mb_strtolower($email));

        // 1. Validações do Nome (RF02, RNF04)
        if (empty($nome)) {
            $erros['nome'] = 'Por favor, informe seu nome completo.';
        } elseif (mb_strlen($nome) < 3) {
            $erros['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($nome) > 150) {
            $erros['nome'] = 'O nome não pode ultrapassar 150 caracteres.';
        }

        // 2. Validações do E-mail (RF02, RNF04)
        if (empty($email)) {
            $erros['email'] = 'Por favor, informe um endereço de e-mail.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Por favor, informe um e-mail válido (exemplo: seu.nome@email.com).';
        } elseif (mb_strlen($email) > 191) {
            $erros['email'] = 'O e-mail informado é longo demais.';
        }

        // 3. Validações da Senha (RF02, RNF04)
        if (empty($senha)) {
            $erros['senha'] = 'Por favor, crie uma senha segura.';
        } elseif (mb_strlen($senha) < 8) {
            $erros['senha'] = 'A senha deve conter no mínimo 8 caracteres para sua segurança.';
        }

        // 4. Confirmação de Senha
        if (func_num_args() >= 4 && empty($confirmarSenha)) {
            $erros['confirmar_senha'] = 'Por favor, confirme a sua senha.';
        } elseif (!empty($confirmarSenha) && $senha !== $confirmarSenha) {
            $erros['confirmar_senha'] = 'As senhas informadas não coincidem. Digite novamente.';
        }

        // Se houver erros de formato, interrompe antes de consultar o banco
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'erros'   => $erros
            ];
        }

        try {
            // 5. Verificação de Duplicidade de E-mail (RF03 - Prepared Statement)
            $stmtCheck = $this->pdo->prepare('SELECT id_usuario FROM USUARIOS WHERE email = ? LIMIT 1');
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                return [
                    'sucesso' => false,
                    'erros'   => [
                        'email' => 'Este e-mail já está cadastrado. Se já possui uma conta, faça login.'
                    ]
                ];
            }

            // 6. Armazenamento Seguro por Hash (RF04, RNF01, RNF02, CA16)
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // 7. Inserção do Usuário (Prepared Statement)
            $stmtInsert = $this->pdo->prepare(
                'INSERT INTO USUARIOS (nome, email, senha_hash, tipo_usuario)
                 VALUES (?, ?, ?, "comum")'
            );
            $stmtInsert->execute([$nome, $email, $senhaHash]);
            $novoId = (int)$this->pdo->lastInsertId();

            return [
                'sucesso'    => true,
                'erros'      => [],
                'id_usuario' => $novoId
            ];
        } catch (PDOException $e) {
            error_log('AuthService::cadastrar error: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erros'   => [
                    'geral' => 'Não foi possível concluir seu cadastro no momento. Por favor, tente novamente.'
                ]
            ];
        }
    }

    /**
     * Realiza a autenticação (login) do usuário no sistema.
     *
     * @param string $email E-mail fornecido
     * @param string $senha Senha fornecida
     * @return array ['sucesso' => bool, 'erro' => ?string, 'usuario' => ?array]
     */
    public function autenticar(string $email, string $senha): array {
        $email = trim(mb_strtolower($email));

        if (empty($email) || empty($senha)) {
            return [
                'sucesso' => false,
                'erro'    => 'Por favor, informe seu e-mail e sua senha para entrar.'
            ];
        }

        try {
            // Busca segura por Prepared Statement (RNF03)
            $stmt = $this->pdo->prepare(
                'SELECT id_usuario, nome, email, senha_hash, tipo_usuario
                 FROM USUARIOS
                 WHERE email = ?
                 LIMIT 1'
            );
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            // Mensagem genérica segura (RNF07, CA03): Não revela se o e-mail existe ou não
            $erroCredenciais = 'E-mail ou senha incorretos. Verifique seus dados e tente novamente.';

            if (!$usuario) {
                return [
                    'sucesso' => false,
                    'erro'    => $erroCredenciais
                ];
            }

            // Verificação do Hash de Senha (RNF01, RNF02)
            if (!password_verify($senha, $usuario['senha_hash'])) {
                return [
                    'sucesso' => false,
                    'erro'    => $erroCredenciais
                ];
            }

            // Autenticação bem-sucedida: Regenera o ID de sessão para prevenir fixation attacks
            initSecureSession();
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }

            // Armazena na sessão somente os dados essenciais de identificação (RF06)
            $_SESSION['usuario_id']    = (int)$usuario['id_usuario'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['tipo_usuario']  = $usuario['tipo_usuario'];

            return [
                'sucesso' => true,
                'erro'    => null,
                'usuario' => [
                    'id'    => (int)$usuario['id_usuario'],
                    'nome'  => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo'  => $usuario['tipo_usuario']
                ]
            ];
        } catch (PDOException $e) {
            error_log('AuthService::autenticar error: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erro'    => 'Ocorreu uma falha temporária ao processar o login. Tente novamente mais tarde.'
            ];
        }
    }

    /**
     * Encerra a sessão do usuário de forma segura e limpa cookies.
     */
    public function logout(): void {
        initSecureSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
