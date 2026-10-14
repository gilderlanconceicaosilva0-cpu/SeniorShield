<?php
/**
 * SeniorShield - Histórico de Análises de Mensagens
 *
 * Permite ao usuário autenticado consultar todas as análises realizadas,
 * rever as pontuações do ISD, classificações e indicadores detectados.
 * Atende aos requisitos RF17-RF23, CA07, CA08 e RNF06.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Helpers/utils.php';
require_once __DIR__ . '/src/Services/AnalysisService.php';

require_login();

$user = get_logged_user();
$userId = (int)$user['id'];
$analysisService = new AnalysisService();

// Processamento de exclusão individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('historico.php'));
        exit;
    }

    $idAnaliseExcluir = (int)($_POST['id_analise'] ?? 0);
    if ($idAnaliseExcluir > 0) {
        $excluiu = $analysisService->excluirAnalise($idAnaliseExcluir, $userId);
        if ($excluiu) {
            flash_set('sucesso', 'Análise removida do seu histórico com sucesso.');
        } else {
            flash_set('erro', 'Não foi possível excluir a análise ou permissão negada.');
        }
    }

    header('Location: ' . url('historico.php'));
    exit;
}

$pageTitle = 'Meu Histórico de Análises';
$paginaAtual = 'historico.php';

$estatisticas = $analysisService->obterEstatisticasUsuario($userId);
$analises = $analysisService->obterHistoricoUsuario($userId, 50);

require_once __DIR__ . '/includes/header.php';
?>

<div class="history-container">
  <!-- Cabeçalho da Página -->
  <header style="margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
        <span aria-hidden="true">📋</span>
        Meu Histórico de Análises
      </h1>
      <p style="font-size: var(--font-size-base); color: var(--text-muted);">
        Acompanhe todas as verificações de risco realizadas na sua conta, <?= e($user['nome']) ?>.
      </p>
    </div>

    <div>
      <a href="<?= url('analise.php') ?>" class="btn btn-primary">
        <span aria-hidden="true">🔍</span> Nova Análise
      </a>
    </div>
  </header>

  <!-- Métricas Rápidas do Usuário -->
  <?php if ($estatisticas['total'] > 0): ?>
    <section class="stats-grid" style="margin-bottom: 32px;" aria-labelledby="historico-stats-title">
      <h2 id="historico-stats-title" class="sr-only" style="position: absolute; width: 1px; height: 1px; overflow: hidden;">
        Estatísticas do Meu Histórico
      </h2>

      <article class="stat-card">
        <div class="stat-icon stat-icon-blue" aria-hidden="true">📊</div>
        <div class="stat-details">
          <span class="stat-value"><?= (int)$estatisticas['total'] ?></span>
          <span class="stat-label">Análises Realizadas</span>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-icon stat-icon-green" aria-hidden="true">🛡️</div>
        <div class="stat-details">
          <span class="stat-value"><?= (int)$estatisticas['media_isd'] ?></span>
          <span class="stat-label">Média do ISD (0-100)</span>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-icon stat-icon-amber" aria-hidden="true">⚠️</div>
        <div class="stat-details">
          <span class="stat-value"><?= (int)($estatisticas['alto_risco'] + $estatisticas['risco_critico']) ?></span>
          <span class="stat-label">Alertas Graves / Críticos</span>
        </div>
      </article>
    </section>
  <?php endif; ?>

  <!-- Lista de Análises Gravadas (RF23, CA08) -->
  <section aria-labelledby="lista-analises-title">
    <h2 id="lista-analises-title" style="font-size: var(--font-size-xl); font-weight: 800; color: var(--text-main); margin-bottom: 16px;">
      Análises Salvas (<?= count($analises) ?>)
    </h2>

    <?php if (!empty($analises)): ?>
      <div class="history-list">
        <?php foreach ($analises as $item): ?>
          <article class="history-card-item">
            <div class="history-card-top">
              <span class="history-date">
                📅 <?= e($item['data_formatada']) ?>
              </span>

              <span class="isd-classification-tag risk-<?= e($item['nivel_slug']) ?>" style="margin: 0; font-size: 0.9375rem; padding: 4px 12px;">
                <span aria-hidden="true">
                  <?php
                    switch ($item['nivel_slug']) {
                        case 'baixo': echo '🟢'; break;
                        case 'atencao': echo '🟡'; break;
                        case 'suspeito': echo '🟠'; break;
                        case 'alto': echo '🔴'; break;
                        case 'critico': echo '🚨'; break;
                    }
                  ?>
                </span>
                ISD: <?= (int)$item['pontuacao_isd'] ?>/100 (<?= e($item['classificacao']) ?>)
              </span>
            </div>

            <div class="history-message-preview">
              "<?= e($item['preview_mensagem']) ?>"
            </div>

            <div class="history-card-footer">
              <span style="font-size: var(--font-size-sm); color: var(--text-muted); font-weight: 600;">
                <span aria-hidden="true">🔍</span>
                <?= (int)$item['total_indicadores'] ?> indicador(es) detectado(s)
              </span>

              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= url('detalhes_analise.php?id=' . (int)$item['id_analise']) ?>" class="btn btn-outline btn-sm">
                  Ver Detalhes &rarr;
                </a>

                <form method="POST" action="<?= url('historico.php') ?>" onsubmit="return confirm('Tem certeza que deseja apagar esta análise do histórico?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="id_analise" value="<?= (int)$item['id_analise'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="color: var(--risk-high); border-color: #fca5a5;" title="Remover do histórico">
                    🗑️ Excluir
                  </button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Estado Vazio Amigável para Idosos -->
      <div class="analysis-card" style="text-align: center; padding: 48px 24px;">
        <div style="font-size: 3.5rem; margin-bottom: 12px;" aria-hidden="true">📭</div>
        <h3 style="font-size: var(--font-size-xl); font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
          Nenhuma análise realizada ainda
        </h3>
        <p style="font-size: var(--font-size-base); color: var(--text-muted); max-width: 520px; margin: 0 auto 24px;">
          Quando você receber uma mensagem duvidosa no WhatsApp, SMS ou e-mail, utilize o SeniorShield para calcular o risco antes de responder.
        </p>
        <a href="<?= url('analise.php') ?>" class="btn btn-primary btn-lg">
          <span aria-hidden="true">🔍</span> Analisar Minha Primeira Mensagem
        </a>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
