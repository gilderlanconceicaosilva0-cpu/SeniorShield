<?php
/**
 * SeniorShield - Modo Aprendizado (Simulador Interativo)
 *
 * Apresenta situações simuladas do cotidiano para que o usuário avalie se é golpe ou seguro,
 * com feedback pedagógico imediato e registro de desempenho.
 * Atende aos requisitos RF28-RF35, CA10-CA12 e RNF12-RNF14.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Helpers/utils.php';
require_once __DIR__ . '/src/Services/EducationService.php';

$pageTitle = 'Modo Aprendizado (Simulador)';
$paginaAtual = 'aprendizado.php';

$educationService = new EducationService();
$loggedUser = get_logged_user();
$userId = $loggedUser['id'] ?? null;

$todasQuestoes = $educationService->listarQuestoes(true);
$totalQuestoes = count($todasQuestoes);

if ($totalQuestoes === 0) {
    flash_set('aviso', 'Não há simulações cadastradas no momento.');
    header('Location: ' . url('index.php'));
    exit;
}

$feedback = null;
$idAtual = (int)($_GET['q'] ?? 0);

// Processamento da resposta enviada pelo usuário (RF30-RF34, CA11, CA12)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Sessão expirada. Tente responder novamente.');
        header('Location: ' . url('aprendizado.php'));
        exit;
    }

    $idQuestaoResp = (int)($_POST['id_questao'] ?? 0);
    $respostaEscolhida = (string)($_POST['resposta'] ?? '');

    $resultadoResposta = $educationService->processarResposta(
        $idQuestaoResp,
        $respostaEscolhida,
        $userId ? (int)$userId : null
    );

    if ($resultadoResposta['sucesso']) {
        $feedback = $resultadoResposta;
        $idAtual = $idQuestaoResp;
    }
}

// Localiza a questão atual na lista
$indiceAtual = 0;
if ($idAtual > 0) {
    foreach ($todasQuestoes as $idx => $q) {
        if ((int)$q['id_questao'] === $idAtual) {
            $indiceAtual = $idx;
            break;
        }
    }
}

$questaoAtual = $todasQuestoes[$indiceAtual];
$proximaQuestao = $todasQuestoes[($indiceAtual + 1) % $totalQuestoes] ?? null;

// Carrega desempenho do usuário se logado (RF35)
$desempenho = $userId ? $educationService->obterDesempenhoUsuario((int)$userId) : null;

require_once __DIR__ . '/includes/header.php';
?>

<div class="simulator-container">
  <!-- Cabeçalho -->
  <header style="margin-bottom: 24px; text-align: center;">
    <h1 style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
      <span aria-hidden="true">🎯</span>
      Modo Aprendizado: Treine seu Olhar
    </h1>
    <p style="font-size: var(--font-size-base); color: var(--text-muted); max-width: 680px; margin: 0 auto;">
      Examine a mensagem simulada abaixo e decida se ela é uma <strong>tentativa de golpe</strong> ou uma <strong>mensagem segura</strong>.
    </p>
  </header>

  <!-- Painel de Desempenho do Usuário Logado (RF35) -->
  <?php if ($desempenho && $desempenho['total_tentativas'] > 0): ?>
    <section class="user-performance-box" aria-labelledby="desempenho-title">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 2.25rem;" aria-hidden="true">🏆</span>
        <div>
          <h2 id="desempenho-title" style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); margin: 0;">
            Seu Desempenho no Aprendizado
          </h2>
          <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
            Continue praticando para manter sua atenção afiada!
          </p>
        </div>
      </div>

      <div style="display: flex; gap: 24px;">
        <div class="performance-metric">
          <div class="performance-metric-val"><?= (int)$desempenho['taxa_acerto'] ?>%</div>
          <div class="performance-metric-label">Taxa de Acerto</div>
        </div>
        <div class="performance-metric">
          <div class="performance-metric-val"><?= (int)$desempenho['total_acertos'] ?> / <?= (int)$desempenho['total_tentativas'] ?></div>
          <div class="performance-metric-label">Acertos no Total</div>
        </div>
      </div>
    </section>
  <?php elseif (!$userId): ?>
    <div class="alert alert-info" style="margin-bottom: 24px;">
      <span aria-hidden="true">💡</span>
      <div>
        <a href="<?= url('login.php') ?>" style="font-weight: 700; text-decoration: underline; color: inherit;">Entre na sua conta</a> para registrar seu progresso e salvar sua taxa de acertos no aprendizado!
      </div>
    </div>
  <?php endif; ?>

  <!-- Dispositivo Simulado com a Mensagem (RF28, RF29, CA10) -->
  <article class="simulated-device" aria-labelledby="questao-indice-title">
    <div class="chat-mock-header">
      <div class="chat-avatar" aria-hidden="true">📱</div>
      <div class="chat-sender-info">
        <h3 id="questao-indice-title">
          Situação <?= $indiceAtual + 1 ?> de <?= $totalQuestoes ?>
          <?php if (!empty($questaoAtual['nome_tipo_golpe'])): ?>
            <span style="font-size: 0.8125rem; font-weight: 400; opacity: 0.85;">
              • <?= e($questaoAtual['nome_tipo_golpe']) ?>
            </span>
          <?php endif; ?>
        </h3>
        <p>Mensagem recebida no celular</p>
      </div>
    </div>

    <div class="chat-body-area">
      <div class="chat-bubble-received">
        <p style="margin: 0; font-size: 1.125rem; line-height: 1.6; color: var(--text-main);">
          <?= nl2br(e($questaoAtual['mensagem'])) ?>
        </p>
        <span class="chat-bubble-time" aria-hidden="true">Recebida hoje às 14:32</span>
      </div>
    </div>

    <!-- Seção de Ações / Escolha do Usuário (RF30) -->
    <div class="sim-actions-area">
      <?php if (!$feedback): ?>
        <p class="sim-question-prompt">
          O que você acha desta mensagem?
        </p>

        <form method="POST" action="<?= url('aprendizado.php?q=' . (int)$questaoAtual['id_questao']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id_questao" value="<?= (int)$questaoAtual['id_questao'] ?>">

          <div class="sim-choices-grid">
            <button
              type="submit"
              name="resposta"
              value="golpe"
              class="btn-choice btn-choice-golpe"
              id="btnEscolhaGolpe">
              <span aria-hidden="true">🚨</span> É Golpe!
            </button>

            <button
              type="submit"
              name="resposta"
              value="seguro"
              class="btn-choice btn-choice-seguro"
              id="btnEscolhaSeguro">
              <span aria-hidden="true">✅</span> É Seguro!
            </button>
          </div>
        </form>
      <?php else: ?>
        <!-- Resultado e Explicação Pedagógica (RF31, RF32, CA11) -->
        <div class="sim-feedback-box <?= $feedback['acertou'] ? 'correct' : 'wrong' ?>">
          <div class="sim-feedback-title">
            <span aria-hidden="true"><?= $feedback['acertou'] ? '🎉' : '⚠️' ?></span>
            <?= $feedback['acertou'] ? 'Parabéns! Você acertou!' : 'Ops! Cuidado com esta situação!' ?>
          </div>

          <p style="font-size: 1.0625rem; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">
            Esta mensagem é: <strong style="text-transform: uppercase;"><?= e($feedback['resposta_correta'] === 'golpe' ? '🚨 UM GOLPE' : '✅ SEGURA') ?></strong>.
            Você respondeu: "<?= e($feedback['resposta_usuario'] === 'golpe' ? 'É Golpe' : 'É Seguro') ?>".
          </p>

          <div class="sim-feedback-explanation">
            <strong>Explicação do SeniorShield:</strong><br>
            <?= nl2br(e($feedback['explicacao'])) ?>
          </div>

          <?php if (!empty($feedback['id_tentativa'])): ?>
            <div style="margin-top: 14px; font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">
              ✓ Sua resposta foi registrada no seu histórico de treinamento.
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <a href="<?= url('aprendizado.php?q=' . (int)$questaoAtual['id_questao']) ?>" class="btn btn-outline">
            Tentar Esta Novamente
          </a>

          <?php if ($proximaQuestao): ?>
            <a href="<?= url('aprendizado.php?q=' . (int)$proximaQuestao['id_questao']) ?>" class="btn btn-primary btn-lg">
              Próxima Situação &rarr;
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <!-- Lista de Navegação Rápida entre Situações -->
  <div style="background: var(--surface-color); border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <span style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main);">
      Escolher outra situação simulada:
    </span>

    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <?php foreach ($todasQuestoes as $idx => $q): ?>
        <a href="<?= url('aprendizado.php?q=' . (int)$q['id_questao']) ?>"
           class="btn btn-sm <?= ($idx === $indiceAtual) ? 'btn-primary' : 'btn-outline' ?>"
           style="min-width: 44px; text-align: center;">
          <?= $idx + 1 ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
