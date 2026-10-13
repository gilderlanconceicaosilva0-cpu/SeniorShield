<?php
/**
 * SeniorShield - Navegação e Cabeçalho Administrativo
 *
 * Fornece a barra de controle superior interna da área administrativa,
 * alternando entre o painel de métricas e o gerenciamento de usuários.
 */

require_once __DIR__ . '/../../src/Helpers/auth_guard.php';

// Bloqueia acesso direto ao fragmento de inclusão via URL
if (!empty($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

require_admin();

$adminUser = get_logged_user();
$scriptName = basename($_SERVER['PHP_SELF']);
$paginaAtualAdmin = $paginaAtualAdmin ?? $scriptName;
?>

<div class="admin-container" style="margin-bottom: 24px;">
  <!-- Cabeçalho do Painel Administrativo -->
  <header class="admin-header">
    <div class="admin-header-title-group">
      <h1>
        <span aria-hidden="true">⚙️</span>
        Área Administrativa
      </h1>
      <p>
        Painel de controle institucional, métricas e gestão de usuários do SeniorShield.
      </p>
    </div>

    <div class="admin-header-user">
      <span class="user-badge admin-badge" title="Papel administrativo ativo">
        🛡️ Admin: <?= e($adminUser['nome'] ?? 'Administrador') ?>
      </span>
    </div>
  </header>

  <!-- Abas de Navegação Administrativa -->
  <nav class="admin-subnav" aria-label="Navegação Administrativa">
    <a href="<?= url('admin/index.php') ?>"
       class="admin-subnav-link <?= $paginaAtualAdmin === 'index.php' ? 'active' : '' ?>">
      <span aria-hidden="true">📊</span>
      Visão Geral (Painel)
    </a>

    <a href="<?= url('admin/usuarios.php') ?>"
       class="admin-subnav-link <?= in_array($paginaAtualAdmin, ['usuarios.php', 'usuario_editar.php'], true) ? 'active' : '' ?>">
      <span aria-hidden="true">👥</span>
      Gerenciar Usuários
    </a>

    <a href="<?= url('index.php') ?>"
       class="admin-subnav-link"
       title="Acessar o portal público do SeniorShield">
      <span aria-hidden="true">🌐</span>
      Voltar à Plataforma Pública
    </a>
  </nav>
</div>
