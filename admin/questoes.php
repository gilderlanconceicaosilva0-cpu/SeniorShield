<?php
/**
 * SeniorShield - Gestão de Questões do Modo Aprendizado
 *
 * Permite cadastrar, consultar, atualizar e ativar/desativar questões simuladas (RF43, RF44, RF45).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/EducationService.php';

require_admin();

$pageTitle = 'Gerenciar Questões do Modo Aprendizado';
$paginaAtualAdmin = 'questoes.php';

$educationService = new EducationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/questoes.php'));
        exit;
    }

    $acao = $_POST['acao'] ?? '';
    $idQuestao = (int)($_POST['id_questao'] ?? 0);

    if ($acao === 'alternar_status' && $idQuestao > 0) {
        $educationService->alternarStatusQuestao($idQuestao);
        flash_set('sucesso', 'Status da questão simulada atualizado.');
    } elseif ($acao === 'excluir' && $idQuestao > 0) {
        $educationService->excluirQuestao($idQuestao);
        flash_set('sucesso', 'Questão simulada removida.');
    }

    header('Location: ' . url('admin/questoes.php'));
    exit;
}

$busca = (string)($_GET['busca'] ?? '');
$filtroStatus = isset($_GET['status']) && $_GET['status'] !== '' ? ($_GET['status'] === '1') : null;

$questoes = $educationService->listarQuestoes($filtroStatus, null, $busca);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true">🎯</span>
          Situações Simuladas do Modo Aprendizado
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Casos práticos e mensagens falsas/legítimas para treinamento interativo dos usuários (RF43-RF45).
        </p>
      </div>

      <a href="<?= url('admin/questao_editar.php') ?>" class="btn btn-primary">
        <span aria-hidden="true">➕</span> Nova Questão
      </a>
    </div>

    <!-- Tabela de Questões -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Mensagem Simulada</th>
            <th style="width: 140px; text-align: center;">Gabarito</th>
            <th style="width: 160px;">Categoria</th>
            <th style="width: 110px; text-align: center;">Status</th>
            <th style="width: 170px; text-align: right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($questoes)): ?>
            <?php foreach ($questoes as $q): ?>
              <tr>
                <td><strong>#<?= (int)$q['id_questao'] ?></strong></td>
                <td>
                  <div style="color: var(--text-main); font-size: 0.9375rem; line-height: 1.5; font-style: italic; max-width: 440px;">
                    "<?= e(mb_substr($q['mensagem'], 0, 110)) ?>..."
                  </div>
                  <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px;">
                    <strong>Explicação:</strong> <?= e(mb_substr($q['explicacao'], 0, 90)) ?>...
                  </div>
                </td>
                <td style="text-align: center;">
                  <?php if ($q['resposta_correta'] === 'golpe'): ?>
                    <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #f87171;">
                      🚨 É GOLPE
                    </span>
                  <?php else: ?>
                    <span class="badge" style="background: #f0fdf4; color: #16a34a; border: 1px solid #86efac;">
                      ✅ É SEGURO
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span style="font-size: 0.875rem; color: var(--text-muted);">
                    <?= e($q['nome_tipo_golpe'] ?? 'Geral / Legítima') ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <?php if ($q['ativo']): ?>
                    <span class="badge badge-comum">Ativa</span>
                  <?php else: ?>
                    <span class="badge badge-outline" style="color: #64748b;">Inativa</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                    <a href="<?= url('admin/questao_editar.php?id=' . (int)$q['id_questao']) ?>"
                       class="btn btn-outline btn-sm">
                      ✏️ Editar
                    </a>

                    <form method="POST" action="<?= url('admin/questoes.php') ?>" style="display: inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="alternar_status">
                      <input type="hidden" name="id_questao" value="<?= (int)$q['id_questao'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm" title="<?= $q['ativo'] ? 'Desativar questão' : 'Ativar questão' ?>">
                        <?= $q['ativo'] ? '⏸️' : '▶️' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                Nenhuma questão simulada cadastrada.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
