<?php
/**
 * SeniorShield - Login de Usuários
 *
 * Permite a autenticação segura de usuários comuns e administradores.
 * Valida credenciais com password_verify, regenera o ID de sessão e protege contra CSRF.
 * Atende aos requisitos RF05-RF06, CA02, CA03, RNF01, RNF03 e RNF07.
 */

require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Services/AuthService.php';

// Redireciona usuários já autenticados para a página inicial
if (is_logged_in()) {
    header('Location: ' . url('index.php'));
    exit;
}

$email = '';
$erro  = '';

// Processamento do Formulário via POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Validação do Token CSRF
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erro = 'Sua sessão de login expirou. Por favor, tente novamente.';
    } else {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        try {
            $authService = new AuthService();
            $resultado   = $authService->autenticar($email, $senha);

            if ($resultado['sucesso']) {
                $nomeUsuario = $resultado['usuario']['nome'] ?? 'usuário';
                flash_set('sucesso', 'Olá, ' . $nomeUsuario . '! Você entrou na sua conta com sucesso.');

                // Redireciona de acordo com o tipo de usuário ou para a página inicial
                if ($resultado['usuario']['tipo'] === 'admin') {
                    header('Location: ' . url('index.php'));
                } else {
                    header('Location: ' . url('index.php'));
                }
                exit;
            } else {
                $erro = $resultado['erro'];
            }
        } catch (Exception $e) {
            error_log('SeniorShield - Erro no login: ' . $e->getMessage());
            $erro = 'Não foi possível conectar ao serviço no momento. Tente novamente mais tarde.';
        }
    }
}

$pageTitle = 'Entrar na Conta';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon" aria-hidden="true">🔐</div>
      <h1 class="auth-title">Entrar no SeniorShield</h1>
      <p class="auth-subtitle">
        Informe seu e-mail e senha para acessar sua conta com segurança.
      </p>
    </div>

    <?php if (!empty($erro)): ?>
      <div class="alert alert-erro" role="alert">
        <span class="alert-icon" aria-hidden="true">❌</span>
        <div><?= e($erro) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('login.php') ?>" novalidate>
      <?= csrf_field() ?>

      <!-- Campo E-mail -->
      <div class="form-group">
        <label for="email" class="form-label">
          Seu E-mail <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-input <?= !empty($erro) ? 'has-error' : '' ?>"
          value="<?= e($email) ?>"
          placeholder="exemplo@email.com"
          autocomplete="email"
          required
          aria-describedby="ajuda-email"
        >
        <p id="ajuda-email" class="form-help">O mesmo e-mail cadastrado anteriormente.</p>
      </div>

      <!-- Campo Senha -->
      <div class="form-group">
        <label for="senha" class="form-label">
          Sua Senha <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="password"
          id="senha"
          name="senha"
          class="form-input <?= !empty($erro) ? 'has-error' : '' ?>"
          placeholder="Digite sua senha"
          autocomplete="current-password"
          required
        >
      </div>

      <div class="form-actions" style="margin-top: 28px;">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Entrar na Minha Conta
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Ainda não possui uma conta? <a href="<?= url('cadastro.php') ?>">Cadastre-se gratuitamente</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
