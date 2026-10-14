<?php
/**
 * SeniorShield - Portal Educativo (Dicas e Guias de Segurança)
 *
 * Apresenta artigos, orientações práticas e alertas de golpes digitais.
 * Atende aos requisitos RF24, CA09 e RNF12-RNF14.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Helpers/utils.php';
require_once __DIR__ . '/src/Services/EducationService.php';

$pageTitle = 'Dicas e Guias Educativos';
$paginaAtual = 'conteudos.php';

$educationService = new EducationService();

$filtroTipo = (string)($_GET['tipo'] ?? '');
$busca = (string)($_GET['busca'] ?? '');

$conteudos = $educationService->listarConteudos(true, $filtroTipo, $busca);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 32px; margin-bottom: 60px;">
  <!-- Cabeçalho Principal -->
  <header style="margin-bottom: 32px; text-align: center;">
    <h1 style="font-size: var(--font-size-3xl); font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
      <span aria-hidden="true">📚</span>
      Aprenda a se Proteger de Golpes Digitais
    </h1>
    <p style="font-size: var(--font-size-lg); color: var(--text-muted); max-width: 720px; margin: 0 auto;">
      Orientações diretas, fáceis de ler e sem termos complicados para você usar seu celular e internet com tranquilidade.
    </p>
  </header>

  <!-- Filtros Rápidos por Categoria e Busca -->
  <div style="background: var(--surface-color); border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; margin-bottom: 28px;">
    <form method="GET" action="<?= url('conteudos.php') ?>" class="filter-form" style="margin: 0;">
      <div class="filter-group" style="flex: 2;">
        <label for="busca" class="form-label">Buscar Assunto:</label>
        <input
          type="text"
          name="busca"
          id="busca"
          class="form-control"
          placeholder="Ex: banco, senha, pix, whatsapp..."
          value="<?= e($busca) ?>">
      </div>

      <div class="filter-group" style="flex: 1;">
        <label for="tipo" class="form-label">Categoria:</label>
        <select name="tipo" id="tipo" class="form-control">
          <option value="">Todas as Categorias</option>
          <option value="dica" <?= $filtroTipo === 'dica' ? 'selected' : '' ?>>Dicas Rápidas</option>
          <option value="alerta" <?= $filtroTipo === 'alerta' ? 'selected' : '' ?>>Alertas Urgentes</option>
          <option value="guia" <?= $filtroTipo === 'guia' ? 'selected' : '' ?>>Guias Práticos</option>
        </select>
      </div>

      <div class="filter-group-action" style="align-self: flex-end; display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if ($busca !== '' || $filtroTipo !== ''): ?>
          <a href="<?= url('conteudos.php') ?>" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Grade de Artigos e Guias (RF24, CA09) -->
  <?php if (!empty($conteudos)): ?>
    <section class="edu-grid" aria-label="Lista de conteúdos educativos">
      <?php foreach ($conteudos as $artigo): ?>
        <article class="edu-card">
          <div class="edu-card-top">
            <span class="edu-badge-<?= e($artigo['tipo']) ?>">
              <?php
                switch ($artigo['tipo']) {
                    case 'alerta': echo '⚠️ Alerta'; break;
                    case 'guia': echo '📖 Guia'; break;
                    default: echo '💡 Dica'; break;
                }
              ?>
            </span>
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;">
              <?= e($artigo['data_formatada']) ?>
            </span>
          </div>

          <h2 class="edu-card-title">
            <?= e($artigo['titulo']) ?>
          </h2>

          <div class="edu-card-text">
            <?= nl2br(e($artigo['texto'])) ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php else: ?>
    <div class="analysis-card" style="text-align: center; padding: 48px 24px; margin-top: 24px;">
      <div style="font-size: 3rem; margin-bottom: 12px;" aria-hidden="true">🔍</div>
      <h3 style="font-size: var(--font-size-xl); font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
        Nenhum conteúdo encontrado
      </h3>
      <p style="font-size: var(--font-size-base); color: var(--text-muted); margin-bottom: 20px;">
        Não encontramos nenhum guia ou dica com os termos informados.
      </p>
      <a href="<?= url('conteudos.php') ?>" class="btn btn-primary">
        Ver Todos os Conteúdos
      </a>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
