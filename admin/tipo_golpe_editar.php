<?php
/**
 * SeniorShield - Cadastro e Edição de Tipo de Golpe
 *
 * Permite cadastrar ou atualizar categorias de golpes (RF40, RF41).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';

require_admin();

$idTipo = (int)($_GET['id'] ?? 0);
$isEdicao = ($idTipo > 0);

$pageTitle = $isEdicao ? 'Editar Tipo de Golpe' : 'Novo Tipo de Golpe';
$paginaAtualAdmin = 'tipos_golpe.php';

$indicatorService = new IndicatorService();

$valores = [
    'nome'      => '',
    'descricao' => '',
    'ativo'     => 1
];
$erros = [];

if ($isEdicao) {
    $tipoGolpe = $indicatorService->obterTipoGolpePorId($idTipo);
    if (!$tipoGolpe) {
        flash_set('erro', 'Tipo de golpe não encontrado.');
        header('Location: ' . url('admin/tipos_golpe.php'));
        exit;
    }
    $valores = $tipoGolpe;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/tipos_golpe.php'));
        exit;
    }

    $valores = [
        'nome'      => (string)($_POST['nome'] ?? ''),
        'descricao' => (string)($_POST['descricao'] ?? ''),
        'ativo'     => !empty($_POST['ativo']) ? 1 : 0
    ];

    $resultado = $indicatorService->salvarTipoGolpe($valores, $isEdicao ? $idTipo : null);

    if ($resultado['sucesso']) {
        flash_set('sucesso', $isEdicao ? 'Tipo de golpe atualizado com sucesso.' : 'Novo tipo de golpe cadastrado com sucesso.');
        header('Location: ' . url('admin/tipos_golpe.php'));
        exit;
    } else {
        $erros = $resultado['erros'];
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container" style="max-width: 760px;">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true"><?= $isEdicao ? '✏️' : '➕' ?></span>
          <?= $isEdicao ? 'Editar Tipo de Golpe #' . $idTipo : 'Novo Tipo de Golpe' ?>
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Cadastre ou edite as categorias de golpes reconhecidas pela plataforma.
        </p>
      </div>

      <a href="<?= url('admin/tipos_golpe.php') ?>" class="btn btn-outline btn-sm">
        &larr; Voltar
      </a>
    </div>

    <form method="POST" action="<?= url('admin/tipo_golpe_editar.php' . ($isEdicao ? '?id=' . $idTipo : '')) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="nome" class="form-label">Nome da Categoria / Golpe: *</label>
        <input
          type="text"
          name="nome"
          id="nome"
          class="form-control <?= !empty($erros['nome']) ? 'is-invalid' : '' ?>"
          value="<?= e($valores['nome']) ?>"
          placeholder="Ex: Golpe do Falso Parente, Falso Boleto..."
          required>
        <?php if (!empty($erros['nome'])): ?>
          <span class="form-error"><?= e($erros['nome']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="descricao" class="form-label">Descrição das Características:</label>
        <textarea
          name="descricao"
          id="descricao"
          class="form-control"
          rows="4"
          placeholder="Descreva como o golpe acontece, o que os golpistas dizem e quem costuma ser o alvo principal..."><?= e($valores['descricao']) ?></textarea>
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
            Categoria Ativa (disponível para associação em questões simuladas)
          </span>
        </label>
      </div>

      <div class="form-actions" style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="<?= url('admin/tipos_golpe.php') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          💾 Salvar Tipo de Golpe
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
