<?php
/**
 * SeniorShield - Tela de Análise de Mensagens Suspeitas (ISD)
 *
 * Permite ao usuário colar ou digitar uma mensagem recebida para que o sistema
 * calcule o Índice de Suspeição Digital (ISD) e aponte os indicadores de risco.
 * Atende aos requisitos RF08-RF16, CA04-CA06 e RNF09-RNF14.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Helpers/utils.php';
require_once __DIR__ . '/src/Services/AnalysisService.php';

$pageTitle = 'Análise de Mensagem (ISD)';
$paginaAtual = 'analise.php';

$analysisService = new AnalysisService();
$loggedUser = get_logged_user();
$userId = $loggedUser['id'] ?? null;

$mensagemInput = '';
$resultado = null;
$erro = null;

// Processamento do formulário de análise
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Sessão expirada ou token de segurança inválido. Por favor, tente enviar novamente.');
        header('Location: ' . url('analise.php'));
        exit;
    }

    $mensagemInput = (string)($_POST['mensagem'] ?? '');

    $resultadoAnalise = $analysisService->analisarMensagem($mensagemInput, $userId ? (int)$userId : null);

    if ($resultadoAnalise['sucesso']) {
        $resultado = $resultadoAnalise;
    } else {
        $erro = $resultadoAnalise['erro'] ?? 'Não foi possível analisar a mensagem informada.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="analysis-container">
  <!-- Card Obrigatório de Advertência e Privacidade (RF16, RNF09, RNF10) -->
  <?php require_once __DIR__ . '/includes/disclaimer_card.php'; ?>

  <!-- Card Principal de Formulário -->
  <section class="analysis-card" aria-labelledby="form-analysis-title">
    <div class="analysis-header">
      <h1 id="form-analysis-title">
        <span aria-hidden="true">🔍</span>
        Análise de Mensagem Suspeita
      </h1>
      <p>
        Recebeu uma mensagem estranha no WhatsApp, SMS ou e-mail? Cole o texto no campo abaixo para calcularmos o <strong>Índice de Suspeição Digital (ISD)</strong>.
      </p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-erro" role="alert" style="margin-bottom: 20px;">
        <span aria-hidden="true">⚠️</span>
        <div><?= e($erro) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('analise.php') ?>" novalidate id="analysisForm">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="mensagem" class="form-label">
          Texto da Mensagem Recebida:
        </label>
        <textarea
          name="mensagem"
          id="mensagem"
          class="analysis-textarea"
          placeholder="Cole aqui a mensagem recebida exatamente como enviaram para você..."
          required
          aria-describedby="aviso-privacidade-campo"
          rows="6"><?= e($mensagemInput) ?></textarea>
        
        <span id="aviso-privacidade-campo" class="form-hint" style="color: var(--risk-attention); font-weight: 600;">
          🔒 Lembre-se: remova senhas, números completos de documentos ou códigos confidenciais antes de analisar.
        </span>
      </div>

      <div class="analysis-tools-bar">
        <span class="char-counter" id="charCounter">0 caracteres</span>
        <div style="display: flex; gap: 8px;">
          <button type="button" class="btn btn-outline btn-sm" id="btnExemploGolpe" title="Inserir mensagem de teste com suspeita de golpe">
            Exemplo de Golpe
          </button>
          <button type="button" class="btn btn-outline btn-sm" id="btnExemploSeguro" title="Inserir mensagem de teste legítima">
            Exemplo Seguro
          </button>
          <button type="button" class="btn btn-outline btn-sm" id="btnLimpar" title="Limpar o campo de texto">
            Limpar
          </button>
        </div>
      </div>

      <div class="form-actions" style="margin-top: 10px;">
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="font-size: 1.125rem;">
          <span aria-hidden="true">🛡️</span>
          Calcular Índice de Suspeição Digital (ISD)
        </button>
      </div>
    </form>
  </section>

  <?php if ($resultado): ?>
    <!-- Painel de Resultados do ISD (RF11, RF12, RF13, RF14, RF15) -->
    <section class="isd-result-card" id="resultadoAnalise" aria-labelledby="resultado-title">
      <div class="analysis-header" style="margin-bottom: 20px;">
        <h2 id="resultado-title" style="font-size: var(--font-size-xl); font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
          <span aria-hidden="true">📊</span>
          Resultado da Avaliação de Risco
        </h2>
        <p>Confira abaixo a pontuação calculada e a explicação detalhada de cada indicador identificado.</p>
      </div>

      <!-- Destaque Principal do ISD -->
      <div class="isd-score-hero score-<?= e($resultado['nivel_slug']) ?>">
        <div class="isd-score-number-box">
          <span class="isd-score-value"><?= (int)$resultado['pontuacao_isd'] ?></span>
          <span class="isd-score-scale">de 100 pontos</span>
        </div>

        <div class="isd-score-info">
          <span class="isd-classification-tag risk-<?= e($resultado['nivel_slug']) ?>">
            <span aria-hidden="true">
              <?php
                switch ($resultado['nivel_slug']) {
                    case 'baixo': echo '🟢'; break;
                    case 'atencao': echo '🟡'; break;
                    case 'suspeito': echo '🟠'; break;
                    case 'alto': echo '🔴'; break;
                    case 'critico': echo '🚨'; break;
                }
              ?>
            </span>
            Classificação: <?= e($resultado['classificacao']) ?>
          </span>

          <p style="font-size: var(--font-size-base); color: var(--text-main); margin-top: 6px;">
            <?php if ($resultado['pontuacao_isd'] === 0): ?>
              Nenhum termo ou sinal característico de golpes digitais cadastrados foi detectado nesta mensagem.
            <?php elseif ($resultado['pontuacao_isd'] <= 40): ?>
              Foram identificados alguns termos que demandam atenção redobrada antes de realizar qualquer ação.
            <?php elseif ($resultado['pontuacao_isd'] <= 60): ?>
              A mensagem possui características suspeitas frequentes em abordagens fraudulentas.
            <?php else: ?>
              Atenção máxima: esta mensagem reúne múltiplos sinais de alerta de alto perigo e pressão psicológica.
            <?php endif; ?>
          </p>

          <!-- Barra Visual de Risco (Termômetro) -->
          <div class="isd-meter-container" aria-hidden="true">
            <div class="isd-meter-track">
              <div class="isd-meter-fill fill-<?= e($resultado['nivel_slug']) ?>" style="width: <?= max(6, (int)$resultado['pontuacao_isd']) ?>%;"></div>
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

      <!-- Notificação de Armazenamento no Histórico (RF17, RF23) -->
      <?php if (!empty($resultado['salvo_no_historico'])): ?>
        <div class="alert alert-sucesso" style="margin-bottom: 24px;">
          <span aria-hidden="true">✅</span>
          <div>
            <strong>Análise salva no seu histórico pessoal!</strong> Você pode consultá-la quando quiser na aba
            <a href="<?= url('historico.php') ?>" style="color: inherit; text-decoration: underline; font-weight: 700;">Histórico de Análises</a>.
          </div>
        </div>
      <?php elseif (!$userId): ?>
        <div class="alert alert-info" style="margin-bottom: 24px;">
          <span aria-hidden="true">💡</span>
          <div>
            Quer guardar esta análise? <a href="<?= url('cadastro.php') ?>" style="font-weight: 700; text-decoration: underline; color: inherit;">Crie uma conta gratuita</a> ou <a href="<?= url('login.php') ?>" style="font-weight: 700; text-decoration: underline; color: inherit;">faça login</a> para salvar todo o seu histórico.
          </div>
        </div>
      <?php endif; ?>

      <!-- Detalhamento dos Indicadores Identificados (RF10, RF14) -->
      <div style="margin-top: 28px;">
        <h3 style="font-size: var(--font-size-lg); font-weight: 800; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <span aria-hidden="true">🔍</span>
          Indicadores Identificados na Mensagem (<?= (int)$resultado['total_indicadores'] ?>)
        </h3>

        <?php if (!empty($resultado['indicadores_detectados'])): ?>
          <div class="indicators-list">
            <?php foreach ($resultado['indicadores_detectados'] as $ind): ?>
              <article class="indicator-card <?= $ind['peso'] >= 25 ? 'risk-critical-border' : ($ind['peso'] >= 20 ? 'risk-high-border' : '') ?>">
                <div style="flex: 1;">
                  <h4 class="indicator-title">
                    <span aria-hidden="true">⚠️</span>
                    <?= e($ind['nome']) ?>
                  </h4>
                  <p class="indicator-desc">
                    <?= e($ind['descricao']) ?>
                  </p>
                  <?php if (!empty($ind['termos_detectados'])): ?>
                    <div class="indicator-terms">
                      <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; align-self: center;">Sinais encontrados:</span>
                      <?php foreach ($ind['termos_detectados'] as $termo): ?>
                        <span class="indicator-term-pill">"<?= e($termo) ?>"</span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="indicator-weight-badge" title="Peso somado ao cálculo do ISD">
                  +<?= (int)$ind['peso'] ?> pontos
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-sucesso">
            <span aria-hidden="true">🛡️</span>
            <div>
              <strong>Nenhum sinal suspeito cadastrado foi detectado!</strong> A mensagem não apresentou os termos mais comuns de fraudes digitais conhecidas.
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Recomendações e Orientações Práticas -->
      <div class="guidance-box">
        <h3>
          <span aria-hidden="true">💡</span>
          O que você deve fazer agora?
        </h3>
        <ul class="guidance-list">
          <?php if ($resultado['pontuacao_isd'] > 20): ?>
            <li><strong>Não clique em nenhum link</strong> contido na mensagem suspeita.</li>
            <li><strong>Nunca faça transferências ou pagamentos via Pix</strong> antes de confirmar pessoalmente ou ouvir a voz da pessoa por telefone.</li>
            <li><strong>Bancos nunca pedem senhas</strong>, tokens ou códigos recebidos por SMS.</li>
            <li>Se o contato diz ser seu parente com número novo, <strong>ligue para o número antigo</strong> que você já tinha salvo na agenda.</li>
          <?php else: ?>
            <li>Mesmo com pontuação baixa, mantenha a cautela se pedirem dinheiro ou informações que você não esperava.</li>
            <li>Em caso de dúvida sobre serviços bancários, consulte sempre o aplicativo oficial instalado no seu telefone.</li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Aviso Obrigatório de Avaliação Indicativa (RF15, RNF11) -->
      <div class="alert alert-aviso" style="margin-top: 24px;">
        <span aria-hidden="true">ℹ️</span>
        <div>
          <strong>Isenção e Avaliação de Risco:</strong> <?= e($resultado['disclaimer_avaliacao']) ?>
        </div>
      </div>

      <!-- Botão para Nova Análise -->
      <div style="margin-top: 28px; text-align: center;">
        <a href="<?= url('analise.php') ?>" class="btn btn-secondary btn-lg">
          <span aria-hidden="true">🔄</span> Fazer Nova Análise
        </a>
      </div>
    </section>
  <?php endif; ?>
</div>

<script>
  // Interatividade acessível na tela de análise
  document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('mensagem');
    const counter = document.getElementById('charCounter');
    const btnExemploGolpe = document.getElementById('btnExemploGolpe');
    const btnExemploSeguro = document.getElementById('btnExemploSeguro');
    const btnLimpar = document.getElementById('btnLimpar');

    function updateCounter() {
      const count = textarea.value.length;
      counter.textContent = count + ' caractere' + (count === 1 ? '' : 's');
    }

    if (textarea && counter) {
      textarea.addEventListener('input', updateCounter);
      updateCounter();
    }

    if (btnExemploGolpe && textarea) {
      btnExemploGolpe.addEventListener('click', function () {
        textarea.value = 'URGENTE: Notamos uma tentativa de acesso suspeita na sua conta. Seu acesso foi suspenso. Clique em bit.ly/seguro e informe seu código de confirmação sms e senha para evitar o cancelamento imediato.';
        updateCounter();
        textarea.focus();
      });
    }

    if (btnExemploSeguro && textarea) {
      btnExemploSeguro.addEventListener('click', function () {
        textarea.value = 'Olá, vovô! Tudo bem? Passando para avisar que o almoço de domingo de Páscoa vai ser na casa da tia Maria às 12h. Um grande abraço!';
        updateCounter();
        textarea.focus();
      });
    }

    if (btnLimpar && textarea) {
      btnLimpar.addEventListener('click', function () {
        textarea.value = '';
        updateCounter();
        textarea.focus();
      });
    }

    // Rolagem suave até o resultado quando exibido
    const resultadoEl = document.getElementById('resultadoAnalise');
    if (resultadoEl) {
      resultadoEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
