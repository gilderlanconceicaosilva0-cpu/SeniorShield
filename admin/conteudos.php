<?php
/**
 * SeniorShield - Gestão Administrativa de Conteúdos Educativos
 *
 * Permite cadastrar, consultar, atualizar e desativar conteúdos educativos (RF25, RF26, RF27, CA15).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/EducationService.php';

require_admin();

$pageTitle = 'Gerenciar Conteúdos Educativos';
$paginaAtualAdmin = 'conteudos.php';

$educationService = new EducationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/conteudos.php'));
        exit;
    }

    $acao = $_POST['acao'] ?? '';
    $idConteudo = (int)($_POST['id_conteudo'] ?? 0);

    if ($acao === 'alternar_status' && $idConteudo > 0) {
        $educationService->alternarStatusConteudo($idConteudo);
        flash_set('sucesso', 'Status do conteúdo educativo atualizado.');
    } elseif ($acao === 'excluir' && $idConteudo > 0) {
        $educationService->excluirConteudo($idConteudo);
        flash_set('sucesso', 'Conteúdo educativo removido.');
    }

    header('Location: ' . url('admin/conteudos.php'));
    exit;
}

$busca = (string)($_GET['busca'] ?? '');
$tipo = (string)($_GET['tipo'] ?? '');
$filtroStatus = isset($_GET['status']) && $_GET['status'] !== '' ? ($_GET['status'] === '1') : null;

$conteudos = $educationService->listarConteudos($filtroStatus, $tipo, $busca);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true">📚</span>
          Conteúdos Educativos e Orientações
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Gerencie artigos, guias didáticos e alertas preventivos exibidos aos cidadãos e usuários sêniores (RF25-RF27).
        </p>
      </div>

      <a href="<?= url('admin/conteudo_editar.php') ?>" class="btn btn-primary">
        <span aria-hidden="true">➕</span> Novo Conteúdo
      </a>
    </div>

    <!-- Tabela de Conteúdos -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Título da Orientação</th>
            <th style="width: 130px;">Categoria</th>
            <th style="width: 140px;">Publicado em</th>
            <th style="width: 110px; text-align: center;">Status</th>
            <th style="width: 170px; text-align: right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($conteudos)): ?>
            <?php foreach ($conteudos as $c): ?>
              <tr>
                <td><strong>#<?= (int)$c['id_conteudo'] ?></strong></td>
                <td>
                  <strong style="color: var(--text-main); font-size: 1.0625rem;">
                    <?= e($c['titulo']) ?>
                  </strong>
                  <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 4px;">
                    <?= e($c['preview_texto']) ?>
                  </div>
                </td>
                <td>
                  <span class="user-badge" style="text-transform: capitalize;">
                    <?= e($c['tipo']) ?>
                  </span>
                </td>
                <td>
                  <span style="font-size: 0.875rem; color: var(--text-muted);">
                    <?= e($c['data_formatada']) ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <?php if ($c['ativo']): ?>
                    <span class="badge badge-comum">Ativo</span>
                  <?php else: ?>
                    <span class="badge badge-outline" style="color: #64748b;">Inativo</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                    <a href="<?= url('admin/conteudo_editar.php?id=' . (int)$c['id_conteudo']) ?>"
                       class="btn btn-outline btn-sm">
                      ✏️ Editar
                    </a>

                    <form method="POST" action="<?= url('admin/conteudos.php') ?>" style="display: inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="alternar_status">
                      <input type="hidden" name="id_conteudo" value="<?= (int)$c['id_conteudo'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm" title="<?= $c['ativo'] ? 'Desativar artigo' : 'Ativar artigo' ?>">
                        <?= $c['ativo'] ? '⏸️' : '▶️' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                Nenhum conteúdo educativo cadastrado.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
