<?php
/**
 * SeniorShield - Cadastro e Edição de Indicador de Risco (ISD)
 *
 * Permite cadastrar ou atualizar indicadores, termos-chave e definir pesos (RF36, RF37, RF38).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';

require_admin();

$idIndicador = (int)($_GET['id'] ?? 0);
$isEdicao = ($idIndicador > 0);

$pageTitle = $isEdicao ? 'Editar Indicador' : 'Novo Indicador de Risco';
$paginaAtualAdmin = 'indicadores.php';

$indicatorService = new IndicatorService();

$valores = [
    'nome'           => '',
    'descricao'      => '',
    'palavras_chave' => '',
    'peso'           => 10,
    'ativo'          => 1
];
$erros = [];

if ($isEdicao) {
    $indicador = $indicatorService->obterIndicadorPorId($idIndicador);
    if (!$indicador) {
        flash_set('erro', 'Indicador não encontrado.');
        header('Location: ' . url('admin/indicadores.php'));
        exit;
    }
    $valores = $indicador;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/indicadores.php'));
        exit;
    }

    $valores = [
        'nome'           => (string)($_POST['nome'] ?? ''),
        'descricao'      => (string)($_POST['descricao'] ?? ''),
        'palavras_chave' => (string)($_POST['palavras_chave'] ?? ''),
        'peso'           => (int)($_POST['peso'] ?? 10),
        'ativo'          => !empty($_POST['ativo']) ? 1 : 0
    ];

    $resultado = $indicatorService->salvarIndicador($valores, $isEdicao ? $idIndicador : null);

    if ($resultado['sucesso']) {
        flash_set('sucesso', $isEdicao ? 'Indicador e peso atualizados com sucesso.' : 'Novo indicador cadastrado com sucesso.');
        header('Location: ' . url('admin/indicadores.php'));
        exit;
    } else {
        $erros = $resultado['erros'];
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-container" style="max-width: 800px;">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2 class="admin-card-title">
          <span aria-hidden="true"><?= $isEdicao ? '✏️' : '➕' ?></span>
          <?= $isEdicao ? 'Editar Indicador de Risco #' . $idIndicador : 'Cadastrar Novo Indicador de Risco' ?>
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Configure o peso que será somado ao Índice de Suspeição Digital (ISD) e as palavras-chave que acionam a regra.
        </p>
      </div>

      <a href="<?= url('admin/indicadores.php') ?>" class="btn btn-outline btn-sm">
        &larr; Voltar
      </a>
    </div>

    <?php if (!empty($erros['geral'])): ?>
      <div class="alert alert-erro" role="alert" style="margin-bottom: 20px;">
        <span aria-hidden="true">⚠️</span>
        <div><?= e($erros['geral']) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('admin/indicador_editar.php' . ($isEdicao ? '?id=' . $idIndicador : '')) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="nome" class="form-label">Nome do Indicador: *</label>
        <input
          type="text"
          name="nome"
          id="nome"
          class="form-control <?= !empty($erros['nome']) ? 'is-invalid' : '' ?>"
          value="<?= e($valores['nome']) ?>"
          placeholder="Ex: Urgência extrema, Pedido de Pix, Link suspeito..."
          required>
        <?php if (!empty($erros['nome'])): ?>
          <span class="form-error"><?= e($erros['nome']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="descricao" class="form-label">Descrição Didática:</label>
        <textarea
          name="descricao"
          id="descricao"
          class="form-control"
          rows="3"
          placeholder="Explique com linguagem simples e clara por que este indicador representa um perigo de golpe..."><?= e($valores['descricao']) ?></textarea>
        <span class="form-hint">Este texto será apresentado ao usuário na explicação dos indicadores identificados.</span>
      </div>

      <div class="form-group">
        <label for="palavras_chave" class="form-label">Palavras e Termos Chave (separados por vírgula): *</label>
        <textarea
          name="palavras_chave"
          id="palavras_chave"
          class="form-control <?= !empty($erros['palavras_chave']) ? 'is-invalid' : '' ?>"
          rows="4"
          placeholder="Ex: urgente, imediatamente, agora, bloqueio hoje, última chance"
          required><?= e($valores['palavras_chave']) ?></textarea>
        <?php if (!empty($erros['palavras_chave'])): ?>
          <span class="form-error"><?= e($erros['palavras_chave']) ?></span>
        <?php endif; ?>
        <span class="form-hint">Separe cada termo por vírgula. A busca é independente de letras maiúsculas ou acentos.</span>
      </div>

      <div class="form-group">
        <label for="peso" class="form-label">Peso no ISD (1 a 100 pontos): *</label>
        <div style="display: flex; align-items: center; gap: 16px;">
          <input
            type="number"
            name="peso"
            id="peso"
            class="form-control <?= !empty($erros['peso']) ? 'is-invalid' : '' ?>"
            style="max-width: 140px;"
            value="<?= (int)$valores['peso'] ?>"
            min="1"
            max="100"
            required>
          <span style="font-size: var(--font-size-sm); color: var(--text-muted);">
            Valores recomendados: 10 a 25 pontos por gravidade. A soma dos pesos é limitada a 100.
          </span>
        </div>
        <?php if (!empty($erros['peso'])): ?>
          <span class="form-error"><?= e($erros['peso']) ?></span>
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
            Indicador Ativo (utilizado no cálculo do ISD)
          </span>
        </label>
      </div>

      <div class="form-actions" style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="<?= url('admin/indicadores.php') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          💾 Salvar Indicador
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
