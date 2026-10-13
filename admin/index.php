<?php
/**
 * SeniorShield - Painel Principal da Área Administrativa (Dashboard)
 *
 * Apresenta a visão geral do sistema, métricas consolidadas,
 * atalhos de gerenciamento e lista dos usuários recentemente cadastrados.
 * Restrito estritamente a administradores autenticados (RF07, CA13, RNF06).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/UserService.php';

// Bloqueio rigoroso no servidor para usuários não autorizados
require_admin();

$pageTitle = 'Painel Administrativo';
$paginaAtualAdmin = 'index.php';

$userService = new UserService();
$metricas = $userService->obterMetricasGerais();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<!-- Grade de Estatísticas / Métricas Gerais -->
<section class="stats-grid" aria-labelledby="metricas-titulo">
  <h2 id="metricas-titulo" class="sr-only" style="position: absolute; width: 1px; height: 1px; overflow: hidden;">
    Métricas do Sistema
  </h2>

  <!-- Card: Total de Usuários -->
  <article class="stat-card">
    <div class="stat-icon stat-icon-blue" aria-hidden="true">👥</div>
    <div class="stat-details">
      <span class="stat-value"><?= (int)$metricas['total_usuarios'] ?></span>
      <span class="stat-label">Usuários Cadastrados</span>
      <small style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 2px;">
        <?= (int)$metricas['total_comuns'] ?> comuns | <?= (int)$metricas['total_admins'] ?> admin(s)
      </small>
    </div>
  </article>

  <!-- Card: Análises Realizadas -->
  <article class="stat-card">
    <div class="stat-icon stat-icon-green" aria-hidden="true">🔍</div>
    <div class="stat-details">
      <span class="stat-value"><?= (int)$metricas['total_analises'] ?></span>
      <span class="stat-label">Análises Realizadas</span>
      <small style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 2px;">
        Índice de Suspeição Digital
      </small>
    </div>
  </article>

  <!-- Card: Indicadores de Risco -->
  <article class="stat-card">
    <div class="stat-icon stat-icon-amber" aria-hidden="true">⚠️</div>
    <div class="stat-details">
      <span class="stat-value"><?= (int)$metricas['total_indicadores'] ?></span>
      <span class="stat-label">Indicadores Ativos</span>
      <small style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 2px;">
        Regras de detecção do ISD
      </small>
    </div>
  </article>

  <!-- Card: Conteúdos Educativos & Questões -->
  <article class="stat-card">
    <div class="stat-icon stat-icon-purple" aria-hidden="true">📚</div>
    <div class="stat-details">
      <span class="stat-value"><?= (int)($metricas['total_conteudos'] + $metricas['total_questoes']) ?></span>
      <span class="stat-label">Recursos de Aprendizado</span>
      <small style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 2px;">
        <?= (int)$metricas['total_conteudos'] ?> dicas e <?= (int)$metricas['total_questoes'] ?> simulados
      </small>
    </div>
  </article>
</section>

<!-- Bloco de Módulos e Ações Administrativas -->
<section style="margin-top: 28px;" aria-labelledby="modulos-titulo">
  <div class="admin-card-header">
    <h2 id="modulos-titulo" class="admin-card-title">
      <span aria-hidden="true">🛠️</span>
      Módulos Administrativos
    </h2>
    <span class="badge badge-outline">Escopo do Sistema</span>
  </div>

  <div class="modules-grid">
    <!-- Módulo 1: Gerenciamento de Usuários (Implementado) -->
    <article class="module-card featured">
      <div class="module-card-header">
        <h3 class="module-card-title">
          <span aria-hidden="true">👥</span>
          Gerenciamento de Usuários
        </h3>
        <span class="badge badge-comum">Disponível</span>
      </div>
      <p class="module-card-text">
        Consulte, filtre e gerencie as contas cadastradas. Edite permissões entre usuário comum e administrador ou exclua cadastros com segurança.
      </p>
      <div style="margin-top: auto; padding-top: 14px;">
        <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-primary btn-block">
          Acessar Gerenciamento de Usuários &rarr;
        </a>
      </div>
    </article>

    <!-- Módulo 2: Indicadores & Pesos ISD (Planejado) -->
    <article class="module-card">
      <div class="module-card-header">
        <h3 class="module-card-title">
          <span aria-hidden="true">⚖️</span>
          Indicadores & Pesos ISD
        </h3>
        <span class="badge badge-outline">Fase 2 (spec.md)</span>
      </div>
      <p class="module-card-text">
        Configuração das palavras-chave, frases suspeitas e pesos numéricos aplicados no cálculo do Índice de Suspeição Digital (RF36-RF39).
      </p>
      <div style="margin-top: auto; padding-top: 14px;">
        <button type="button" class="btn btn-outline btn-block" disabled title="Módulo previsto na próxima etapa da especificação">
          Em Desenvolvimento
        </button>
      </div>
    </article>

    <!-- Módulo 3: Conteúdos Educativos & Modo Aprendizado (Planejado) -->
    <article class="module-card">
      <div class="module-card-header">
        <h3 class="module-card-title">
          <span aria-hidden="true">📖</span>
          Conteúdos & Simulações
        </h3>
        <span class="badge badge-outline">Fase 2 (spec.md)</span>
      </div>
      <p class="module-card-text">
        Gestão das dicas didáticas de prevenção contra golpes e das questões simuladas interativas do Modo Aprendizado (RF25-RF27, RF43-RF45).
      </p>
      <div style="margin-top: auto; padding-top: 14px;">
        <button type="button" class="btn btn-outline btn-block" disabled title="Módulo previsto na próxima etapa da especificação">
          Em Desenvolvimento
        </button>
      </div>
    </article>
  </div>
</section>

<!-- Bloco: Usuários Recentes Cadastrados -->
<section style="margin-top: 32px;" aria-labelledby="recentes-titulo">
  <div class="admin-card">
    <div class="admin-card-header">
      <h2 id="recentes-titulo" class="admin-card-title">
        <span aria-hidden="true">🕒</span>
        Últimos Usuários Cadastrados
      </h2>
      <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline btn-sm">
        Ver Todos os Usuários (<?= (int)$metricas['total_usuarios'] ?>) &rarr;
      </a>
    </div>

    <?php if (empty($metricas['usuarios_recentes'])): ?>
      <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">👥</div>
        <h3 class="empty-state-title">Nenhum usuário cadastrado</h3>
        <p class="empty-state-text">Quando novos usuários se cadastrarem no SeniorShield, eles serão listados aqui.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th scope="col" style="width: 70px;">ID</th>
              <th scope="col">Nome</th>
              <th scope="col">E-mail</th>
              <th scope="col">Perfil</th>
              <th scope="col">Data de Cadastro</th>
              <th scope="col" style="text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($metricas['usuarios_recentes'] as $usuario): ?>
              <tr>
                <td><strong>#<?= (int)$usuario['id_usuario'] ?></strong></td>
                <td>
                  <strong><?= e($usuario['nome']) ?></strong>
                </td>
                <td><?= e($usuario['email']) ?></td>
                <td>
                  <?php if ($usuario['tipo_usuario'] === 'admin'): ?>
                    <span class="badge badge-admin">⚙️ Administrador</span>
                  <?php else: ?>
                    <span class="badge badge-comum">👤 Usuário Comum</span>
                  <?php endif; ?>
                </td>
                <td><?= e(format_datetime_br($usuario['data_cadastro'])) ?></td>
                <td style="text-align: right;">
                  <a href="<?= url('admin/usuario_editar.php?id=' . (int)$usuario['id_usuario']) ?>"
                     class="btn btn-outline btn-sm"
                     title="Consultar ou editar usuário #<?= (int)$usuario['id_usuario'] ?>">
                    ✏️ Editar / Ver
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
