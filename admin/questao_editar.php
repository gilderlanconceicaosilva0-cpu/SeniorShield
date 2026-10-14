<?php
/**
 * SeniorShield - Cadastro e Edição de Questão do Modo Aprendizado
 *
 * Permite cadastrar ou atualizar situações simuladas, gabarito e explicação didática (RF43, RF44).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/EducationService.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';

require_admin();

$idQuestao = (int)($_GET['id'] ?? 0);
$isEdicao = ($idQuestao > 0);

$pageTitle = $isEdicao ? 'Editar Questão Simulada' : 'Nova Questão do Modo Aprendizado';
$paginaAtualAdmin = 'questoes.php';

$educationService = new EducationService();
$indicatorService = new IndicatorService();

$tiposGolpe = $indicatorService->listarTiposGolpe(true);

$valores = [
    'mensagem'         => '',
    'resposta_correta' => 'golpe',
    'explicacao'       => '',
    'id_tipo_golpe'    => '',
    'ativo'            => 1
];
$erros = [];

if ($isEdicao) {
    $questao = $educationService->obterQuestaoPorId($idQuestao);
    if (!$questao) {
        flash_set('erro', 'Questão simulada não encontrada.');
        header('Location: ' . url('admin/questoes.php'));
        exit;
    }
    $valores = $questao;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/questoes.php'));
        exit;
    }

    $valores = [
        'mensagem'         => (string)($_POST['mensagem'] ?? ''),
        'resposta_correta' => (string)($_POST['resposta_correta'] ?? 'golpe'),
        'explicacao'       => (string)($_POST['explicacao'] ?? ''),
        'id_tipo_golpe'    => !empty($_POST['id_tipo_golpe']) ? (int)$_POST['id_tipo_golpe'] : null,
        'ativo'            => !empty($_POST['ativo']) ? 1 : 0
    ];

    $resultado = $educationService->salvarQuestao($valores, $isEdicao ? $idQuestao : null);

    if ($resultado['sucesso']) {
        flash_set('sucesso', $isEdicao ? 'Questão simulada atualizada com sucesso.' : 'Nova questão cadastrada com sucesso.');
        header('Location: ' . url('admin/questoes.php'));
        exit;
    } else {
        $erros = $resultado['erros'];
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container" style="max-width: 820px;">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true"><?= $isEdicao ? '✏️' : '➕' ?></span>
          <?= $isEdicao ? 'Editar Situação Simulada #' . $idQuestao : 'Nova Situação Simulada' ?>
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Crie mensagens que imitem situações reais do cotidiano para capacitar os usuários a reconhecer sinais suspeitos.
        </p>
      </div>

      <a href="<?= url('admin/questoes.php') ?>" class="btn btn-outline btn-sm">
        &larr; Voltar
      </a>
    </div>

    <form method="POST" action="<?= url('admin/questao_editar.php' . ($isEdicao ? '?id=' . $idQuestao : '')) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="mensagem" class="form-label">Mensagem Simulada (Texto que o usuário verá no celular): *</label>
        <textarea
          name="mensagem"
          id="mensagem"
          class="form-control <?= !empty($erros['mensagem']) ? 'is-invalid' : '' ?>"
          rows="4"
          placeholder="Ex: Oi mãe, meu celular quebrou e salvei este número novo. Preciso pagar uma conta com urgência hoje..."
          required><?= e($valores['mensagem']) ?></textarea>
        <?php if (!empty($erros['mensagem'])): ?>
          <span class="form-error"><?= e($erros['mensagem']) ?></span>
        <?php endif; ?>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
          <label for="resposta_correta" class="form-label">Gabarito da Situação: *</label>
          <select name="resposta_correta" id="resposta_correta" class="form-control">
            <option value="golpe" <?= $valores['resposta_correta'] === 'golpe' ? 'selected' : '' ?>>🚨 É Golpe (Tentativa de Fraude)</option>
            <option value="seguro" <?= $valores['resposta_correta'] === 'seguro' ? 'selected' : '' ?>>✅ É Seguro (Mensagem Legítima)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="id_tipo_golpe" class="form-label">Categoria do Golpe (Opcional):</label>
          <select name="id_tipo_golpe" id="id_tipo_golpe" class="form-control">
            <option value="">Nenhum / Mensagem Segura</option>
            <?php foreach ($tiposGolpe as $tg): ?>
              <option value="<?= (int)$tg['id_tipo_golpe'] ?>" <?= (string)$valores['id_tipo_golpe'] === (string)$tg['id_tipo_golpe'] ? 'selected' : '' ?>>
                <?= e($tg['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="explicacao" class="form-label">Explicação Didática (Apresentada após o usuário responder): *</label>
        <textarea
          name="explicacao"
          id="explicacao"
          class="form-control <?= !empty($erros['explicacao']) ? 'is-invalid' : '' ?>"
          rows="5"
          placeholder="Aponte quais sinais denunciam o golpe (ex: alegação de número novo, pedido de pix urgente) ou por que a mensagem é segura..."
          required><?= e($valores['explicacao']) ?></textarea>
        <?php if (!empty($erros['explicacao'])): ?>
          <span class="form-error"><?= e($erros['explicacao']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group" style="margin-top: 20px;">
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
          <input
            type="checkbox"
            name="ativo"
            value="1"
            style="width: 22px; height: 22px;"
            <?= !empty($valores['ativo']) ? 'checked' : '' ?>>
          <span style="font-weight: 700; color: var(--text-main);">
            Questão Ativa no Modo Aprendizado
          </span>
        </label>
      </div>

      <div class="form-actions" style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="<?= url('admin/questoes.php') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          💾 Salvar Questão
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
