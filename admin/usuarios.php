<?php
/**
 * SeniorShield - Gestão de Usuários Cadastrados
 *
 * Lista todos os usuários cadastrados com suporte a busca textual por nome/e-mail
 * e filtro por perfil (comum/admin). Permite consultar detalhes, editar dados
 * e excluir cadastros de forma segura com proteção anti-CSRF.
 * Atende aos requisitos RF06, RF07, CA13, RNF01, RNF03, RNF04 e RNF06.
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/UserService.php';

// Bloqueio rigoroso no servidor para usuários não autorizados
require_admin();

$pageTitle = 'Gerenciamento de Usuários';
$paginaAtualAdmin = 'usuarios.php';

$adminLogado = get_logged_user();
$idAdminLogado = (int)($adminLogado['id'] ?? 0);

$busca = trim((string)($_GET['busca'] ?? ''));
$tipo  = trim((string)($_GET['tipo'] ?? ''));

$userService = new UserService();
$usuarios = $userService->listar([
    'busca' => $busca,
    'tipo'  => $tipo
]);

$totalListados = count($usuarios);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h2 class="admin-card-title">
        <span aria-hidden="true">👥</span>
        Usuários Cadastrados
      </h2>
      <p style="color: var(--text-muted); font-size: var(--font-size-sm); margin-top: 4px;">
        Total encontrado: <strong><?= $totalListados ?></strong> usuário(s)
      </p>
    </div>
  </div>

  <!-- Barra de Filtros e Busca -->
  <div class="admin-toolbar">
    <form method="GET" action="<?= url('admin/usuarios.php') ?>" class="filter-form" role="search">
      <!-- Busca Textual -->
      <div class="filter-group">
        <label for="filtro-busca" class="filter-label">Buscar por Nome ou E-mail:</label>
        <input
          type="text"
          id="filtro-busca"
          name="busca"
          class="form-input"
          value="<?= e($busca) ?>"
          placeholder="Exemplo: Maria ou maria@email.com"
          style="min-height: 44px;"
        >
      </div>

      <!-- Filtro por Perfil -->
      <div class="filter-group" style="max-width: 260px;">
        <label for="filtro-tipo" class="filter-label">Perfil de Acesso:</label>
        <select id="filtro-tipo" name="tipo" class="form-input" style="min-height: 44px;">
          <option value="">Todos os Perfis</option>
          <option value="comum" <?= $tipo === 'comum' ? 'selected' : '' ?>>Usuário Comum</option>
          <option value="admin" <?= $tipo === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>

      <!-- Ações do Filtro -->
      <div class="filter-group-action">
        <button type="submit" class="btn btn-primary btn-sm" style="min-height: 44px;">
          🔍 Filtrar
        </button>

        <?php if ($busca !== '' || $tipo !== ''): ?>
          <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline btn-sm" style="min-height: 44px;">
            Limpar Filtros
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php if (empty($usuarios)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">🔍</div>
      <h3 class="empty-state-title">Nenhum usuário localizado</h3>
      <p class="empty-state-text">
        Não foram encontrados usuários com os critérios de busca informados.
      </p>
      <?php if ($busca !== '' || $tipo !== ''): ?>
        <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline">
          Ver Todos os Usuários
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th scope="col" style="width: 70px;">ID</th>
            <th scope="col">Nome Completo</th>
            <th scope="col">Endereço de E-mail</th>
            <th scope="col">Perfil</th>
            <th scope="col">Data de Cadastro</th>
            <th scope="col" style="text-align: right; min-width: 240px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u):
            $idU = (int)$u['id_usuario'];
            $isProprioUsuario = ($idU === $idAdminLogado);
          ?>
            <tr>
              <td><strong>#<?= $idU ?></strong></td>
              <td>
                <strong><?= e($u['nome']) ?></strong>
                <?php if ($isProprioUsuario): ?>
                  <span class="badge badge-outline" style="margin-left: 6px; font-size: 0.75rem;">Você</span>
                <?php endif; ?>
              </td>
              <td><?= e($u['email']) ?></td>
              <td>
                <?php if ($u['tipo_usuario'] === 'admin'): ?>
                  <span class="badge badge-admin">⚙️ Administrador</span>
                <?php else: ?>
                  <span class="badge badge-comum">👤 Usuário Comum</span>
                <?php endif; ?>
              </td>
              <td><?= e(format_datetime_br($u['data_cadastro'])) ?></td>
              <td style="text-align: right;">
                <div class="table-actions" style="justify-content: flex-end;">
                  <!-- Botão Visualizar Detalhes -->
                  <button
                    type="button"
                    class="btn btn-outline btn-sm btn-abrir-detalhes"
                    data-id="<?= $idU ?>"
                    data-nome="<?= e($u['nome']) ?>"
                    data-email="<?= e($u['email']) ?>"
                    data-tipo="<?= e($u['tipo_usuario']) ?>"
                    data-data="<?= e(format_datetime_br($u['data_cadastro'])) ?>"
                    data-is-proprio="<?= $isProprioUsuario ? '1' : '0' ?>"
                    title="Consultar dados do usuário #<?= $idU ?>"
                  >
                    👁️ Ver
                  </button>

                  <!-- Botão Editar -->
                  <a href="<?= url('admin/usuario_editar.php?id=' . $idU) ?>"
                     class="btn btn-primary btn-sm"
                     title="Editar dados e perfil do usuário #<?= $idU ?>">
                    ✏️ Editar
                  </a>

                  <!-- Botão Excluir -->
                  <?php if ($isProprioUsuario): ?>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      disabled
                      aria-disabled="true"
                      title="Você não pode excluir sua própria conta conectada"
                    >
                      ❌ Excluir
                    </button>
                  <?php else: ?>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm btn-abrir-excluir"
                      data-id="<?= $idU ?>"
                      data-nome="<?= e($u['nome']) ?>"
                      data-email="<?= e($u['email']) ?>"
                      title="Excluir cadastro do usuário #<?= $idU ?>"
                    >
                      ❌ Excluir
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Acessível: Consulta de Detalhes do Usuário -->
<div id="modal-detalhes" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-detalhes-titulo">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 id="modal-detalhes-titulo" class="modal-title">
        <span aria-hidden="true">👤</span> Detalhes do Usuário
      </h3>
      <button type="button" class="modal-close-btn btn-fechar-modal" aria-label="Fechar janela">
        &times;
      </button>
    </div>

    <div class="modal-body">
      <div class="detail-list">
        <div class="detail-item">
          <div class="detail-label">Identificador (ID)</div>
          <div class="detail-value" id="detalhe-id">-</div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Perfil de Acesso</div>
          <div class="detail-value" id="detalhe-tipo">-</div>
        </div>

        <div class="detail-item" style="grid-column: 1 / -1;">
          <div class="detail-label">Nome Completo</div>
          <div class="detail-value" id="detalhe-nome">-</div>
        </div>

        <div class="detail-item" style="grid-column: 1 / -1;">
          <div class="detail-label">Endereço de E-mail</div>
          <div class="detail-value" id="detalhe-email">-</div>
        </div>

        <div class="detail-item" style="grid-column: 1 / -1;">
          <div class="detail-label">Data de Cadastro</div>
          <div class="detail-value" id="detalhe-data">-</div>
        </div>
      </div>

      <div style="margin-top: 18px; padding: 12px 16px; background: #eff6ff; border-radius: var(--radius-sm); border: 1px solid #bfdbfe; font-size: 0.9375rem; color: #1e40af;">
        🔒 <strong>Segurança e Privacidade:</strong> As senhas são protegidas por hash criptográfico seguro (RNF01, RNF02) e nunca são expostas na interface.
      </div>
    </div>

    <div class="modal-footer">
      <a href="#" id="detalhe-link-editar" class="btn btn-primary btn-sm">
        ✏️ Editar Este Usuário
      </a>
      <button type="button" class="btn btn-outline btn-sm btn-fechar-modal">
        Fechar
      </button>
    </div>
  </div>
</div>

<!-- Modal Acessível: Confirmação de Exclusão -->
<div id="modal-excluir" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-excluir-titulo">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 id="modal-excluir-titulo" class="modal-title" style="color: #dc2626;">
        <span aria-hidden="true">⚠️</span> Confirmar Exclusão de Conta
      </h3>
      <button type="button" class="modal-close-btn btn-fechar-modal" aria-label="Fechar janela">
        &times;
      </button>
    </div>

    <form method="POST" action="<?= url('admin/usuario_excluir.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id_usuario" id="excluir-id" value="">

      <div class="modal-body">
        <p style="margin-bottom: 12px;">
          Você está prestes a excluir definitivamente a conta do usuário:
        </p>

        <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 16px;">
          <p style="font-weight: 800; color: #991b1b; font-size: 1.05rem;" id="excluir-nome">-</p>
          <p style="color: #7f1d1d; font-size: 0.9375rem;" id="excluir-email">-</p>
        </div>

        <p style="color: var(--text-muted); font-size: 0.9375rem;">
          Esta ação <strong>não poderá ser revertida</strong>. Todas as análises registradas e tentativas de aprendizado associadas a esta conta serão removidas do banco de dados.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-sm btn-fechar-modal">
          Cancelar
        </button>
        <button type="submit" class="btn btn-danger btn-sm">
          Sim, Excluir Usuário
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Gerenciador Interativo Acessível dos Modais
document.addEventListener('DOMContentLoaded', function () {
  const modalDetalhes = document.getElementById('modal-detalhes');
  const modalExcluir  = document.getElementById('modal-excluir');

  // Abre Modal de Detalhes
  document.querySelectorAll('.btn-abrir-detalhes').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id       = this.dataset.id;
      const nome     = this.dataset.nome;
      const email    = this.dataset.email;
      const tipo     = this.dataset.tipo;
      const data     = this.dataset.data;
      const isProprio= this.dataset.isProprio === '1';

      document.getElementById('detalhe-id').textContent = '#' + id;
      document.getElementById('detalhe-nome').textContent = nome;
      document.getElementById('detalhe-email').textContent = email;
      document.getElementById('detalhe-data').textContent = data;

      const tipoEl = document.getElementById('detalhe-tipo');
      if (tipo === 'admin') {
        tipoEl.innerHTML = '<span class="badge badge-admin">⚙️ Administrador</span>';
      } else {
        tipoEl.innerHTML = '<span class="badge badge-comum">👤 Usuário Comum</span>';
      }

      if (isProprio) {
        tipoEl.innerHTML += ' <span class="badge badge-outline">Sua Conta Atual</span>';
      }

      document.getElementById('detalhe-link-editar').href = '<?= url("admin/usuario_editar.php?id=") ?>' + id;

      modalDetalhes.classList.add('is-open');
    });
  });

  // Abre Modal de Exclusão
  document.querySelectorAll('.btn-abrir-excluir').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id    = this.dataset.id;
      const nome  = this.dataset.nome;
      const email = this.dataset.email;

      document.getElementById('excluir-id').value = id;
      document.getElementById('excluir-nome').textContent = nome;
      document.getElementById('excluir-email').textContent = email;

      modalExcluir.classList.add('is-open');
    });
  });

  // Fecha qualquer modal
  function fecharModais() {
    modalDetalhes.classList.remove('is-open');
    modalExcluir.classList.remove('is-open');
  }

  document.querySelectorAll('.btn-fechar-modal').forEach(function (btn) {
    btn.addEventListener('click', fecharModais);
  });

  // Fechar ao clicar no fundo
  [modalDetalhes, modalExcluir].forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        fecharModais();
      }
    });
  });

  // Fechar com a tecla ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      fecharModais();
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
