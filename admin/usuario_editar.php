<?php
/**
 * SeniorShield - Edição e Atualização de Usuário
 *
 * Permite ao administrador consultar e editar dados cadastrais, alterar o perfil
 * de acesso (comum / admin) e opcionalmente redefinir a senha do usuário.
 * Aplica validação estrita no servidor, verificação anti-CSRF e proteção contra
 * a exclusão/rebaixamento do último administrador (RF06, RF07, CA13, RNF01-RNF07).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/UserService.php';

// Bloqueio rigoroso no servidor para usuários não autorizados
require_admin();

$pageTitle = 'Editar Usuário';
$paginaAtualAdmin = 'usuario_editar.php';

$adminLogado = get_logged_user();
$idAdminLogado = (int)($adminLogado['id'] ?? 0);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$userService = new UserService();
$usuario = $userService->obterPorId($id);

if (!$usuario) {
    flash_set('erro', 'O usuário solicitado não foi encontrado no sistema.');
    header('Location: ' . url('admin/usuarios.php'));
    exit;
}

$nome           = $usuario['nome'];
$email          = $usuario['email'];
$tipo           = $usuario['tipo_usuario'];
$erros          = [];
$isProprioAdmin = ($id === $idAdminLogado);

// Processamento do Formulário via POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Preserva os dados preenchidos no formulário para evitar perda de digitação
    $nome           = trim((string)($_POST['nome'] ?? $nome));
    $email          = trim((string)($_POST['email'] ?? $email));
    $tipo           = (string)($_POST['tipo_usuario'] ?? $tipo);
    $novaSenha      = (string)($_POST['nova_senha'] ?? '');
    $confirmarSenha = (string)($_POST['confirmar_senha'] ?? '');

    // Validação obrigatória do Token CSRF (RNF04)
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erros['geral'] = 'Sua sessão de envio expirou por inatividade. Seus dados foram preservados abaixo; por favor, clique em Salvar Alterações novamente.';
    } else {
        $resultado = $userService->atualizar($id, [
            'nome'            => $nome,
            'email'           => $email,
            'tipo_usuario'    => $tipo,
            'nova_senha'      => $novaSenha,
            'confirmar_senha' => $confirmarSenha
        ], $idAdminLogado);

        if ($resultado['sucesso']) {
            flash_set('sucesso', 'Cadastro do usuário "' . $nome . '" atualizado com sucesso!');
            header('Location: ' . url('admin/usuarios.php'));
            exit;
        } else {
            $erros = $resultado['erros'];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-card" style="max-width: 760px; margin: 0 auto;">
  <div class="admin-card-header">
    <div>
      <h2 class="admin-card-title">
        <span aria-hidden="true">✏️</span>
        Editar Usuário #<?= $id ?>
      </h2>
      <p style="color: var(--text-muted); font-size: var(--font-size-sm); margin-top: 4px;">
        Cadastrado em: <?= e(format_datetime_br($usuario['data_cadastro'])) ?>
        <?php if ($isProprioAdmin): ?>
          <span class="badge badge-admin" style="margin-left: 6px;">Esta é a sua conta conectada</span>
        <?php endif; ?>
      </p>
    </div>

    <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline btn-sm">
      &larr; Voltar para a Lista
    </a>
  </div>

  <!-- Resumo de Atividades do Usuário -->
  <div class="detail-list" style="margin-bottom: 28px;">
    <div class="detail-item">
      <div class="detail-label">Análises de Mensagens</div>
      <div class="detail-value">🔍 <?= (int)$usuario['total_analises'] ?> realizada(s)</div>
    </div>
    <div class="detail-item">
      <div class="detail-label">Modo Aprendizado</div>
      <div class="detail-value">🎯 <?= (int)$usuario['total_tentativas'] ?> tentativa(s)</div>
    </div>
  </div>

  <?php if (!empty($erros['geral'])): ?>
    <div class="alert alert-erro" role="alert">
      <span class="alert-icon" aria-hidden="true">❌</span>
      <div><?= e($erros['geral']) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= url('admin/usuario_editar.php?id=' . $id) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Campo Nome -->
    <div class="form-group">
      <label for="nome" class="form-label">
        Nome Completo <span class="required" aria-hidden="true">*</span>
      </label>
      <input
        type="text"
        id="nome"
        name="nome"
        class="form-input <?= isset($erros['nome']) ? 'has-error' : '' ?>"
        value="<?= e($nome) ?>"
        required
        aria-describedby="<?= isset($erros['nome']) ? 'erro-nome' : '' ?>"
      >
      <?php if (isset($erros['nome'])): ?>
        <p id="erro-nome" class="form-error-msg" role="alert">
          <?= e($erros['nome']) ?>
        </p>
      <?php endif; ?>
    </div>

    <!-- Campo E-mail -->
    <div class="form-group">
      <label for="email" class="form-label">
        Endereço de E-mail <span class="required" aria-hidden="true">*</span>
      </label>
      <input
        type="email"
        id="email"
        name="email"
        class="form-input <?= isset($erros['email']) ? 'has-error' : '' ?>"
        value="<?= e($email) ?>"
        required
        aria-describedby="<?= isset($erros['email']) ? 'erro-email' : '' ?>"
      >
      <?php if (isset($erros['email'])): ?>
        <p id="erro-email" class="form-error-msg" role="alert">
          <?= e($erros['email']) ?>
        </p>
      <?php endif; ?>
    </div>

    <!-- Campo Perfil / Tipo de Usuário -->
    <div class="form-group">
      <label for="tipo_usuario" class="form-label">
        Perfil de Acesso <span class="required" aria-hidden="true">*</span>
      </label>
      <select
        id="tipo_usuario"
        name="tipo_usuario"
        class="form-input <?= isset($erros['tipo_usuario']) ? 'has-error' : '' ?>"
        aria-describedby="<?= isset($erros['tipo_usuario']) ? 'erro-tipo' : 'ajuda-tipo' ?>"
      >
        <option value="comum" <?= $tipo === 'comum' ? 'selected' : '' ?>>
          👤 Usuário Comum (Acesso às análises e simulados)
        </option>
        <option value="admin" <?= $tipo === 'admin' ? 'selected' : '' ?>>
          ⚙️ Administrador (Acesso completo ao painel e gestão)
        </option>
      </select>
      <?php if (isset($erros['tipo_usuario'])): ?>
        <p id="erro-tipo" class="form-error-msg" role="alert">
          <?= e($erros['tipo_usuario']) ?>
        </p>
      <?php else: ?>
        <p id="ajuda-tipo" class="form-help">
          Administradores possuem permissão irrestrita para gerenciar contas, indicadores e conteúdos.
        </p>
      <?php endif; ?>
    </div>

    <!-- Seção Opcional: Redefinição de Senha -->
    <div style="background: var(--surface-alt); border: 2px dashed var(--border-color); border-radius: var(--radius-md); padding: 22px; margin: 28px 0;">
      <h3 style="font-size: var(--font-size-lg); font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
        🔑 Redefinir Senha do Usuário (Opcional)
      </h3>
      <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-bottom: 18px;">
        Deixe os campos abaixo em branco caso deseje manter a senha atual inalterada.
      </p>

      <div class="form-group">
        <label for="nova_senha" class="form-label">Nova Senha</label>
        <input
          type="password"
          id="nova_senha"
          name="nova_senha"
          class="form-input <?= isset($erros['nova_senha']) ? 'has-error' : '' ?>"
          placeholder="Mínimo de 8 caracteres"
          autocomplete="new-password"
          aria-describedby="<?= isset($erros['nova_senha']) ? 'erro-senha' : '' ?>"
        >
        <?php if (isset($erros['nova_senha'])): ?>
          <p id="erro-senha" class="form-error-msg" role="alert">
            <?= e($erros['nova_senha']) ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
        <input
          type="password"
          id="confirmar_senha"
          name="confirmar_senha"
          class="form-input <?= isset($erros['confirmar_senha']) ? 'has-error' : '' ?>"
          placeholder="Digite a mesma senha novamente"
          autocomplete="new-password"
          aria-describedby="<?= isset($erros['confirmar_senha']) ? 'erro-confirmar' : '' ?>"
        >
        <?php if (isset($erros['confirmar_senha'])): ?>
          <p id="erro-confirmar" class="form-error-msg" role="alert">
            <?= e($erros['confirmar_senha']) ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions" style="display: flex; gap: 14px; justify-content: flex-end; margin-top: 24px; flex-wrap: wrap;">
      <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary">
        💾 Salvar Alterações
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
