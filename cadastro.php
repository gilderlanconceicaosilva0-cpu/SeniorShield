<?php
/**
 * SeniorShield - Cadastro de Novo Usuário
 *
 * Permite que novos usuários criem uma conta informando nome, e-mail e senha.
 * Aplica hash seguro, validações no servidor e proteção anti-CSRF.
 * Atende aos requisitos RF01-RF04, CA01, CA16 e RNF01-RNF04.
 */

require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Services/AuthService.php';

// Redireciona usuários já autenticados
if (is_logged_in()) {
    header('Location: ' . url('index.php'));
    exit;
}

$nome           = '';
$email          = '';
$erros          = [];

// Processamento do Formulário via POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Validação do Token CSRF
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sua sessão de envio expirou. Por favor, tente novamente.';
    } else {
        $nome           = $_POST['nome'] ?? '';
        $email          = $_POST['email'] ?? '';
        $senha          = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        try {
            $authService = new AuthService();
            $resultado   = $authService->cadastrar($nome, $email, $senha, $confirmarSenha);

            if ($resultado['sucesso']) {
                flash_set('sucesso', 'Sua conta foi criada com sucesso! Agora você já pode entrar com seu e-mail e senha.');
                header('Location: ' . url('login.php'));
                exit;
            } else {
                $erros = $resultado['erros'];
            }
        } catch (Exception $e) {
            error_log('SeniorShield - Erro no cadastro: ' . $e->getMessage());
            $erros['geral'] = 'Não foi possível conectar ao serviço no momento. Tente novamente mais tarde.';
        }
    }
}

$pageTitle = 'Criar Conta';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon" aria-hidden="true">👤</div>
      <h1 class="auth-title">Criar Sua Conta</h1>
      <p class="auth-subtitle">
        Cadastre-se para analisar mensagens suspeitas e acompanhar seu histórico de proteção.
      </p>
    </div>

    <?php if (!empty($erros['geral'])): ?>
      <div class="alert alert-erro" role="alert">
        <span class="alert-icon" aria-hidden="true">❌</span>
        <div><?= e($erros['geral']) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('cadastro.php') ?>" novalidate>
      <?= csrf_field() ?>

      <!-- Campo Nome Completo -->
      <div class="form-group">
        <label for="nome" class="form-label">
          Seu Nome Completo <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="text"
          id="nome"
          name="nome"
          class="form-input <?= isset($erros['nome']) ? 'has-error' : '' ?>"
          value="<?= e($nome) ?>"
          placeholder="Exemplo: Maria de Souza"
          autocomplete="name"
          required
          aria-describedby="<?= isset($erros['nome']) ? 'erro-nome' : 'ajuda-nome' ?>"
        >
        <?php if (isset($erros['nome'])): ?>
          <p id="erro-nome" class="form-error-msg" role="alert">
            <?= e($erros['nome']) ?>
          </p>
        <?php else: ?>
          <p id="ajuda-nome" class="form-help">Como você gostaria de ser chamado.</p>
        <?php endif; ?>
      </div>

      <!-- Campo E-mail -->
      <div class="form-group">
        <label for="email" class="form-label">
          Seu E-mail <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-input <?= isset($erros['email']) ? 'has-error' : '' ?>"
          value="<?= e($email) ?>"
          placeholder="exemplo@email.com"
          autocomplete="email"
          required
          aria-describedby="<?= isset($erros['email']) ? 'erro-email' : 'ajuda-email' ?>"
        >
        <?php if (isset($erros['email'])): ?>
          <p id="erro-email" class="form-error-msg" role="alert">
            <?= e($erros['email']) ?>
          </p>
        <?php else: ?>
          <p id="ajuda-email" class="form-help">Você usará este e-mail para entrar no sistema.</p>
        <?php endif; ?>
      </div>

      <!-- Campo Senha -->
      <div class="form-group">
        <label for="senha" class="form-label">
          Crie uma Senha <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="password"
          id="senha"
          name="senha"
          class="form-input <?= isset($erros['senha']) ? 'has-error' : '' ?>"
          placeholder="Mínimo de 8 caracteres"
          autocomplete="new-password"
          required
          aria-describedby="<?= isset($erros['senha']) ? 'erro-senha' : 'ajuda-senha' ?>"
        >
        <?php if (isset($erros['senha'])): ?>
          <p id="erro-senha" class="form-error-msg" role="alert">
            <?= e($erros['senha']) ?>
          </p>
        <?php else: ?>
          <p id="ajuda-senha" class="form-help">Escolha uma senha com no mínimo 8 letras ou números.</p>
        <?php endif; ?>
      </div>

      <!-- Campo Confirmar Senha -->
      <div class="form-group">
        <label for="confirmar_senha" class="form-label">
          Confirme a Senha <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="password"
          id="confirmar_senha"
          name="confirmar_senha"
          class="form-input <?= isset($erros['confirmar_senha']) ? 'has-error' : '' ?>"
          placeholder="Digite a mesma senha novamente"
          autocomplete="new-password"
          required
          aria-describedby="<?= isset($erros['confirmar_senha']) ? 'erro-confirmar-senha' : '' ?>"
        >
        <?php if (isset($erros['confirmar_senha'])): ?>
          <p id="erro-confirmar-senha" class="form-error-msg" role="alert">
            <?= e($erros['confirmar_senha']) ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="form-actions" style="margin-top: 28px;">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Criar Minha Conta
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Já possui uma conta? <a href="<?= url('login.php') ?>">Clique aqui para entrar</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
