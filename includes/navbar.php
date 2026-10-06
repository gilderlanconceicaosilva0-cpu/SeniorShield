<?php
/**
 * SeniorShield - Barra de Navegação Superior
 *
 * Responsiva, acessível e adaptável ao estado da sessão (visitante, usuário comum e administrador).
 */

$user = get_logged_user();
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
  <div class="container">
    <nav class="navbar" aria-label="Navegação Principal">
      <!-- Marca / Logotipo -->
      <a href="<?= url('index.php') ?>" class="brand-link" title="Ir para a página inicial">
        <span class="brand-icon" aria-hidden="true">🛡️</span>
        <span><?= APP_NAME ?></span>
      </a>

      <!-- Links de Navegação -->
      <ul class="nav-links">
        <li>
          <a href="<?= url('index.php') ?>" 
             class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>">
            Início
          </a>
        </li>
        <li>
          <a href="<?= url('analise.php') ?>" 
             class="nav-link <?= $paginaAtual === 'analise.php' ? 'active' : '' ?>">
            🔍 Analisar Mensagem
          </a>
        </li>
        <li>
          <a href="<?= url('aprendizado.php') ?>" 
             class="nav-link <?= $paginaAtual === 'aprendizado.php' ? 'active' : '' ?>">
            🎯 Modo Aprendizado
          </a>
        </li>
        <li>
          <a href="<?= url('conteudos.php') ?>" 
             class="nav-link <?= $paginaAtual === 'conteudos.php' ? 'active' : '' ?>">
            📚 Dicas e Guias
          </a>
        </li>

        <?php if ($user): ?>
          <!-- Opções do Usuário Autenticado -->
          <li>
            <a href="<?= url('historico.php') ?>" 
               class="nav-link <?= $paginaAtual === 'historico.php' ? 'active' : '' ?>">
              📋 Histórico
            </a>
          </li>

          <?php if (is_admin()): ?>
            <!-- Acesso ao Painel Administrativo -->
            <li>
              <a href="<?= url('admin/index.php') ?>" class="nav-link user-badge admin-badge">
                ⚙️ Painel Admin
              </a>
            </li>
          <?php endif; ?>

          <li>
            <span class="user-badge" title="Usuário logado">
              👤 <?= e($user['nome']) ?>
            </span>
          </li>
          <li>
            <a href="<?= url('logout.php') ?>" class="nav-link" style="color: #b91c1c;">
              Sair
            </a>
          </li>
        <?php else: ?>
          <!-- Opções para Visitante -->
          <li>
            <a href="<?= url('login.php') ?>" 
               class="nav-link <?= $paginaAtual === 'login.php' ? 'active' : '' ?>">
              Entrar
            </a>
          </li>
          <li>
            <a href="<?= url('cadastro.php') ?>" class="btn btn-primary" style="min-height: 44px; padding: 8px 18px;">
              Criar Conta
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
