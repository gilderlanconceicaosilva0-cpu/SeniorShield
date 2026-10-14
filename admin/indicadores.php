<?php
/**
 * SeniorShield - Gestão Administrativa de Indicadores de Risco (ISD)
 *
 * Permite consultar, filtrar, ativar/desativar e cadastrar indicadores e seus pesos.
 * Atende aos requisitos RF36-RF39, CA14, RNF06 e RNF07.
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';

require_admin();

$pageTitle = 'Gerenciar Indicadores (ISD)';
$paginaAtualAdmin = 'indicadores.php';

$indicatorService = new IndicatorService();

// Ações rápidas POST (Alternar status ou excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/indicadores.php'));
        exit;
    }

    $acao = $_POST['acao'] ?? '';
    $idIndicador = (int)($_POST['id_indicador'] ?? 0);

    if ($acao === 'alternar_status' && $idIndicador > 0) {
        $indicatorService->alternarStatusIndicador($idIndicador);
        flash_set('sucesso', 'Status do indicador atualizado com sucesso.');
    } elseif ($acao === 'excluir' && $idIndicador > 0) {
        $res = $indicatorService->excluirIndicador($idIndicador);
        flash_set($res['sucesso'] ? 'sucesso' : 'erro', $res['mensagem']);
    }

    header('Location: ' . url('admin/indicadores.php'));
    exit;
}

$busca = (string)($_GET['busca'] ?? '');
$filtroStatus = isset($_GET['status']) && $_GET['status'] !== '' ? ($_GET['status'] === '1') : null;

$indicadores = $indicatorService->listarIndicadores($filtroStatus, $busca);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true">⚖️</span>
          Regras e Indicadores de Risco do ISD
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Configure palavras-chave, frases suspeitas e pesos numéricos aplicados no cálculo do Índice de Suspeição Digital (RF36-RF39).
        </p>
      </div>

      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= url('admin/indicador_editar.php') ?>" class="btn btn-primary">
          <span aria-hidden="true">➕</span> Novo Indicador
        </a>
      </div>
    </div>

    <!-- Filtros de Busca -->
    <form method="GET" action="<?= url('admin/indicadores.php') ?>" class="filter-form" style="margin-bottom: 24px;">
      <div class="filter-group">
        <label for="busca" class="form-label">Buscar por Nome ou Termo:</label>
        <input
          type="text"
          name="busca"
          id="busca"
          class="form-control"
          placeholder="Ex: pix, urgente, bit.ly..."
          value="<?= e($busca) ?>">
      </div>

      <div class="filter-group">
        <label for="status" class="form-label">Status:</label>
        <select name="status" id="status" class="form-control">
          <option value="">Todos os status</option>
          <option value="1" <?= $filtroStatus === true ? 'selected' : '' ?>>Apenas Ativos</option>
          <option value="0" <?= $filtroStatus === false ? 'selected' : '' ?>>Apenas Inativos</option>
        </select>
      </div>

      <div class="filter-group-action" style="align-self: flex-end; display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <?php if ($busca !== '' || $filtroStatus !== null): ?>
          <a href="<?= url('admin/indicadores.php') ?>" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Tabela de Indicadores -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Indicador / Regra</th>
            <th>Palavras e Termos Chave</th>
            <th style="width: 100px; text-align: center;">Peso (ISD)</th>
            <th style="width: 110px; text-align: center;">Status</th>
            <th style="width: 170px; text-align: right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($indicadores)): ?>
            <?php foreach ($indicadores as $ind): ?>
              <tr>
                <td><strong>#<?= (int)$ind['id_indicador'] ?></strong></td>
                <td>
                  <div style="font-weight: 800; color: var(--text-main); font-size: 1.0625rem;">
                    <?= e($ind['nome']) ?>
                  </div>
                  <?php if (!empty($ind['descricao'])): ?>
                    <div style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 2px;">
                      <?= e($ind['descricao']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="max-width: 320px; font-size: 0.875rem; color: var(--text-muted); word-break: break-word;">
                    <?= e($ind['palavras_chave']) ?>
                  </div>
                </td>
                <td style="text-align: center;">
                  <span class="user-badge" style="font-weight: 800; font-size: 1rem; color: var(--primary);">
                    +<?= (int)$ind['peso'] ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <?php if ($ind['ativo']): ?>
                    <span class="badge badge-comum">Ativo</span>
                  <?php else: ?>
                    <span class="badge badge-outline" style="color: #64748b;">Inativo</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                    <a href="<?= url('admin/indicador_editar.php?id=' . (int)$ind['id_indicador']) ?>"
                       class="btn btn-outline btn-sm"
                       title="Editar indicador e ajustar peso">
                      ✏️ Editar
                    </a>

                    <form method="POST" action="<?= url('admin/indicadores.php') ?>" style="display: inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="alternar_status">
                      <input type="hidden" name="id_indicador" value="<?= (int)$ind['id_indicador'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm" title="<?= $ind['ativo'] ? 'Desativar este indicador' : 'Ativar este indicador' ?>">
                        <?= $ind['ativo'] ? '⏸️' : '▶️' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                Nenhum indicador encontrado com os filtros selecionados.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
