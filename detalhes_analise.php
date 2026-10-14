<?php
/**
 * SeniorShield - Detalhes de Análise Específica do Histórico
 *
 * Permite ao usuário visualizar o resultado detalhado de uma análise gravada anteriormente.
 * Atende aos requisitos RF17-RF23, CA08 e RNF06.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Helpers/utils.php';
require_once __DIR__ . '/src/Services/AnalysisService.php';

require_login();

$user = get_logged_user();
$userId = (int)$user['id'];
$idAnalise = (int)($_GET['id'] ?? 0);

if ($idAnalise <= 0) {
    flash_set('erro', 'Análise não informada ou inválida.');
    header('Location: ' . url('historico.php'));
    exit;
}

$analysisService = new AnalysisService();
$analise = $analysisService->obterAnalisePorId($idAnalise, is_admin() ? null : $userId);

if (!$analise) {
    flash_set('erro', 'A análise solicitada não foi encontrada ou você não possui permissão para acessá-la.');
    header('Location: ' . url('historico.php'));
    exit;
}

$pageTitle = 'Detalhes da Análise #' . $analise['id_analise'];
$paginaAtual = 'historico.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="analysis-container">
  <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <a href="<?= url('historico.php') ?>" class="btn btn-outline">
      &larr; Voltar ao Histórico
    </a>
    <span class="history-date">
      📅 Realizada em <?= e($analise['data_formatada']) ?>
    </span>
  </div>

  <section class="isd-result-card" aria-labelledby="detalhes-title">
    <div class="analysis-header" style="margin-bottom: 24px;">
      <h1 id="detalhes-title" style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 12px;">
        <span aria-hidden="true">📋</span>
        Detalhes da Análise #<?= (int)$analise['id_analise'] ?>
      </h1>
      <p>Registro arquivado no seu histórico pessoal do SeniorShield.</p>
    </div>

    <!-- Destaque Principal do ISD -->
    <div class="isd-score-hero score-<?= e($analise['nivel_slug']) ?>">
      <div class="isd-score-number-box">
        <span class="isd-score-value"><?= (int)$analise['pontuacao_isd'] ?></span>
        <span class="isd-score-scale">de 100 pontos</span>
      </div>

      <div class="isd-score-info">
        <span class="isd-classification-tag risk-<?= e($analise['nivel_slug']) ?>">
          Classificação: <?= e($analise['classificacao']) ?>
        </span>

        <!-- Barra Visual de Risco -->
        <div class="isd-meter-container" aria-hidden="true">
          <div class="isd-meter-track">
            <div class="isd-meter-fill fill-<?= e($analise['nivel_slug']) ?>" style="width: <?= max(6, (int)$analise['pontuacao_isd']) ?>%;"></div>
          </div>
          <div class="isd-meter-labels">
            <span>0 (Muito Baixo)</span>
            <span>40 (Atenção)</span>
            <span>60 (Suspeito)</span>
            <span>80 (Alto Risco)</span>
            <span>100 (Crítico)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensagem Analisada -->
    <div style="margin-top: 28px;">
      <h2 style="font-size: var(--font-size-lg); font-weight: 800; color: var(--text-main); margin-bottom: 10px;">
        Texto da Mensagem Analisada:
      </h2>
      <div class="history-message-preview" style="font-style: normal; white-space: pre-wrap; font-size: 1.0625rem;">
        <?= e($analise['mensagem']) ?>
      </div>
    </div>

    <!-- Indicadores Detectados -->
    <div style="margin-top: 28px;">
      <h2 style="font-size: var(--font-size-lg); font-weight: 800; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <span aria-hidden="true">🔍</span>
        Indicadores Identificados nesta Análise (<?= count($analise['indicadores']) ?>)
      </h2>

      <?php if (!empty($analise['indicadores'])): ?>
        <div class="indicators-list">
          <?php foreach ($analise['indicadores'] as $ind): ?>
            <article class="indicator-card <?= $ind['peso'] >= 25 ? 'risk-critical-border' : ($ind['peso'] >= 20 ? 'risk-high-border' : '') ?>">
              <div style="flex: 1;">
                <h3 class="indicator-title">
                  <span aria-hidden="true">⚠️</span>
                  <?= e($ind['nome']) ?>
                </h3>
                <p class="indicator-desc">
                  <?= e($ind['descricao']) ?>
                </p>
              </div>

              <div class="indicator-weight-badge">
                +<?= (int)$ind['peso'] ?> pontos
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-sucesso">
          <span aria-hidden="true">🛡️</span>
          <div>Nenhum indicador de risco cadastrado foi acionado nesta mensagem.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Aviso Obrigatório de Isenção -->
    <div class="alert alert-aviso" style="margin-top: 28px;">
      <span aria-hidden="true">ℹ️</span>
      <div>
        <strong>Aviso de Avaliação de Risco:</strong> <?= ISD_DISCLAIMER_AVALIACAO ?>
      </div>
    </div>

    <div style="margin-top: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <a href="<?= url('historico.php') ?>" class="btn btn-outline">
        &larr; Voltar para a Lista de Análises
      </a>

      <form method="POST" action="<?= url('historico.php') ?>" onsubmit="return confirm('Deseja realmente remover esta análise do seu histórico?');">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id_analise" value="<?= (int)$analise['id_analise'] ?>">
        <button type="submit" class="btn btn-danger btn-sm">
          🗑️ Excluir Análise do Histórico
        </button>
      </form>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
