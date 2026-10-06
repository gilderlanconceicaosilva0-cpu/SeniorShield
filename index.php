<?php
/**
 * SeniorShield - Página Inicial (Landing Page)
 *
 * Apresenta a plataforma aos usuários com linguagem clara, acolhedora
 * e foco na usabilidade e acessibilidade para o público sênior.
 */

$pageTitle = 'Página Inicial';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Card Obrigatório de Advertência e Privacidade (RF16, RNF09, RNF10) -->
<?php require_once __DIR__ . '/includes/disclaimer_card.php'; ?>

<!-- Seção Hero Principal -->
<section class="hero" aria-labelledby="hero-title">
  <h1 id="hero-title" class="hero-title">
    Proteção Simples e Segura Contra Golpes Digitais
  </h1>
  <p class="hero-subtitle">
    Recebeu uma mensagem estranha no WhatsApp, SMS ou e-mail? O <strong>SeniorShield</strong> analisa o conteúdo e calcula o <strong>Índice de Suspeição Digital (ISD)</strong> para ajudar você a não cair em armadilhas.
  </p>
  <div class="hero-actions">
    <a href="<?= url('analise.php') ?>" class="btn btn-primary btn-lg">
      🔍 Analisar Mensagem Agora
    </a>
    <a href="<?= url('aprendizado.php') ?>" class="btn btn-secondary btn-lg">
      🎯 Praticar no Modo Aprendizado
    </a>
  </div>
</section>

<!-- Pilares Fundamentais do SeniorShield -->
<section class="pillars-section" aria-labelledby="pilares-title">
  <h2 id="pilares-title" class="section-title">Como o SeniorShield Ajuda Você</h2>
  <p class="section-subtitle">Recursos desenvolvidos para tornar a segurança na internet fácil de entender</p>

  <div class="pillars-grid">
    <!-- Pilar 1: Análise de Mensagens -->
    <article class="card">
      <div class="card-icon card-icon-blue" aria-hidden="true">🛡️</div>
      <h3 class="card-title">Análise de Mensagens (ISD)</h3>
      <p class="card-text">
        Cole o texto de uma mensagem suspeita e descubra a pontuação de risco de 0 a 100. O sistema mostra exatamente quais sinais de perigo foram detectados.
      </p>
      <a href="<?= url('analise.php') ?>" class="btn btn-outline">
        Testar Análise
      </a>
    </article>

    <!-- Pilar 2: Modo Aprendizado -->
    <article class="card">
      <div class="card-icon card-icon-green" aria-hidden="true">🎯</div>
      <h3 class="card-title">Modo Aprendizado</h3>
      <p class="card-text">
        Treine seu olhar com simulações do dia a dia. Descubra se uma mensagem é segura ou se é golpe e receba explicações didáticas imediatas.
      </p>
      <a href="<?= url('aprendizado.php') ?>" class="btn btn-outline">
        Começar Treinamento
      </a>
    </article>

    <!-- Pilar 3: Conteúdos Educativos -->
    <article class="card">
      <div class="card-icon card-icon-purple" aria-hidden="true">📚</div>
      <h3 class="card-title">Dicas e Orientações</h3>
      <p class="card-text">
        Acesse artigos curtos e diretos sobre o golpe do novo número, falsos bancos, links perigosos e os cuidados essenciais com seu celular.
      </p>
      <a href="<?= url('conteudos.php') ?>" class="btn btn-outline">
        Ver Conteúdos
      </a>
    </article>
  </div>
</section>

<!-- Seção: Como Funciona em 3 Passos Simples -->
<section class="steps-section" aria-labelledby="passos-title">
  <h2 id="passos-title" class="section-title">Como Usar em 3 Passos Rápidos</h2>
  <p class="section-subtitle">Sem termos difíceis ou configurações complicadas</p>

  <ol class="steps-list">
    <li class="step-item">
      <div class="step-number" aria-hidden="true">1</div>
      <div class="step-content">
        <h4>Copie a Mensagem</h4>
        <p>Recebeu um pedido urgente de dinheiro ou link desconhecido? Copie o texto recebido.</p>
      </div>
    </li>
    <li class="step-item">
      <div class="step-number" aria-hidden="true">2</div>
      <div class="step-content">
        <h4>Cole no SeniorShield</h4>
        <p>Acesse nossa tela de análise e cole a mensagem (lembre-se de nunca incluir senhas ou documentos).</p>
      </div>
    </li>
    <li class="step-item">
      <div class="step-number" aria-hidden="true">3</div>
      <div class="step-content">
        <h4>Veja o Resultado</h4>
        <p>Receba a pontuação do ISD, a classificação de risco e a explicação dos sinais encontrados.</p>
      </div>
    </li>
  </ol>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
