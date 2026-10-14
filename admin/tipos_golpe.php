<?php
/**
 * SeniorShield - Gestão de Tipos de Golpes Digitais
 *
 * Permite cadastrar, consultar, atualizar e ativar/desativar categorias de golpes.
 * Atende aos requisitos RF40, RF41, RF42 e RNF06.
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';

require_admin();

$pageTitle = 'Gerenciar Tipos de Golpe';
$paginaAtualAdmin = 'tipos_golpe.php';

$indicatorService = new IndicatorService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/tipos_golpe.php'));
        exit;
    }

    $acao = $_POST['acao'] ?? '';
    $idTipo = (int)($_POST['id_tipo_golpe'] ?? 0);

    if ($acao === 'alternar_status' && $idTipo > 0) {
        $indicatorService->alternarStatusTipoGolpe($idTipo);
        flash_set('sucesso', 'Status da categoria de golpe atualizado.');
    }

    header('Location: ' . url('admin/tipos_golpe.php'));
    exit;
}

$busca = (string)($_GET['busca'] ?? '');
$filtroStatus = isset($_GET['status']) && $_GET['status'] !== '' ? ($_GET['status'] === '1') : null;

$tiposGolpe = $indicatorService->listarTiposGolpe($filtroStatus, $busca);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true">🏷️</span>
          Categorias e Tipos de Golpes
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Categorias de fraudes digitais associadas aos simulados do Modo Aprendizado (RF40-RF42).
        </p>
      </div>

      <a href="<?= url('admin/tipo_golpe_editar.php') ?>" class="btn btn-primary">
        <span aria-hidden="true">➕</span> Novo Tipo de Golpe
      </a>
    </div>

    <!-- Tabela de Categorias -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Nome do Golpe</th>
            <th>Descrição e Padrões Conhecidos</th>
            <th style="width: 140px; text-align: center;">Questões Vinculadas</th>
            <th style="width: 110px; text-align: center;">Status</th>
            <th style="width: 160px; text-align: right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($tiposGolpe)): ?>
            <?php foreach ($tiposGolpe as $tg): ?>
              <tr>
                <td><strong>#<?= (int)$tg['id_tipo_golpe'] ?></strong></td>
                <td>
                  <strong style="color: var(--text-main); font-size: 1.0625rem;">
                    <?= e($tg['nome']) ?>
                  </strong>
                </td>
                <td>
                  <span style="font-size: 0.9375rem; color: var(--text-muted);">
                    <?= e($tg['descricao']) ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <span class="user-badge">
                    <?= (int)$tg['total_questoes'] ?> simulado(s)
                  </span>
                </td>
                <td style="text-align: center;">
                  <?php if ($tg['ativo']): ?>
                    <span class="badge badge-comum">Ativo</span>
                  <?php else: ?>
                    <span class="badge badge-outline" style="color: #64748b;">Inativo</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                    <a href="<?= url('admin/tipo_golpe_editar.php?id=' . (int)$tg['id_tipo_golpe']) ?>"
                       class="btn btn-outline btn-sm">
                      ✏️ Editar
                    </a>

                    <form method="POST" action="<?= url('admin/tipos_golpe.php') ?>" style="display: inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="alternar_status">
                      <input type="hidden" name="id_tipo_golpe" value="<?= (int)$tg['id_tipo_golpe'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm" title="<?= $tg['ativo'] ? 'Desativar categoria' : 'Ativar categoria' ?>">
                        <?= $tg['ativo'] ? '⏸️' : '▶️' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                Nenhuma categoria de golpe cadastrada.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
