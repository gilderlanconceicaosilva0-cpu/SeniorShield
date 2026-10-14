<?php
/**
 * SeniorShield - Cadastro e Edição de Conteúdo Educativo
 *
 * Permite cadastrar ou atualizar artigos e dicas de segurança (RF25, RF26, CA15).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/EducationService.php';

require_admin();

$idConteudo = (int)($_GET['id'] ?? 0);
$isEdicao = ($idConteudo > 0);

$pageTitle = $isEdicao ? 'Editar Conteúdo Educativo' : 'Novo Conteúdo Educativo';
$paginaAtualAdmin = 'conteudos.php';

$educationService = new EducationService();

$valores = [
    'titulo' => '',
    'texto'  => '',
    'tipo'   => 'dica',
    'ativo'  => 1
];
$erros = [];

if ($isEdicao) {
    $conteudo = $educationService->obterConteudoPorId($idConteudo);
    if (!$conteudo) {
        flash_set('erro', 'Conteúdo educativo não encontrado.');
        header('Location: ' . url('admin/conteudos.php'));
        exit;
    }
    $valores = $conteudo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        flash_set('erro', 'Token de segurança inválido. Tente novamente.');
        header('Location: ' . url('admin/conteudos.php'));
        exit;
    }

    $valores = [
        'titulo' => (string)($_POST['titulo'] ?? ''),
        'texto'  => (string)($_POST['texto'] ?? ''),
        'tipo'   => (string)($_POST['tipo'] ?? 'dica'),
        'ativo'  => !empty($_POST['ativo']) ? 1 : 0
    ];

    $resultado = $educationService->salvarConteudo($valores, $isEdicao ? $idConteudo : null);

    if ($resultado['sucesso']) {
        flash_set('sucesso', $isEdicao ? 'Conteúdo educativo atualizado com sucesso.' : 'Novo conteúdo cadastrado com sucesso.');
        header('Location: ' . url('admin/conteudos.php'));
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
          <?= $isEdicao ? 'Editar Conteúdo Educativo #' . $idConteudo : 'Novo Conteúdo Educativo' ?>
        </h2>
        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
          Redija textos claros, objetivos e de fácil entendimento sobre prevenção a fraudes.
        </p>
      </div>

      <a href="<?= url('admin/conteudos.php') ?>" class="btn btn-outline btn-sm">
        &larr; Voltar
      </a>
    </div>

    <form method="POST" action="<?= url('admin/conteudo_editar.php' . ($isEdicao ? '?id=' . $idConteudo : '')) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="titulo" class="form-label">Título da Orientação: *</label>
        <input
          type="text"
          name="titulo"
          id="titulo"
          class="form-control <?= !empty($erros['titulo']) ? 'is-invalid' : '' ?>"
          value="<?= e($valores['titulo']) ?>"
          placeholder="Ex: Como reconhecer um falso gerente de banco no WhatsApp..."
          required>
        <?php if (!empty($erros['titulo'])): ?>
          <span class="form-error"><?= e($erros['titulo']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="tipo" class="form-label">Formato do Conteúdo: *</label>
        <select name="tipo" id="tipo" class="form-control">
          <option value="dica" <?= $valores['tipo'] === 'dica' ? 'selected' : '' ?>>💡 Dica Rápida</option>
          <option value="alerta" <?= $valores['tipo'] === 'alerta' ? 'selected' : '' ?>>⚠️ Alerta Urgente</option>
          <option value="guia" <?= $valores['tipo'] === 'guia' ? 'selected' : '' ?>>📖 Guia Prático</option>
          <option value="artigo" <?= $valores['tipo'] === 'artigo' ? 'selected' : '' ?>>📰 Artigo Informativo</option>
        </select>
      </div>

      <div class="form-group">
        <label for="texto" class="form-label">Texto da Orientação: *</label>
        <textarea
          name="texto"
          id="texto"
          class="form-control <?= !empty($erros['texto']) ? 'is-invalid' : '' ?>"
          rows="8"
          placeholder="Escreva a orientação didática, exemplos e o que o cidadão deve fazer para se proteger..."
          required><?= e($valores['texto']) ?></textarea>
        <?php if (!empty($erros['texto'])): ?>
          <span class="form-error"><?= e($erros['texto']) ?></span>
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
            Conteúdo Ativo (visível para o público no portal educativo)
          </span>
        </label>
      </div>

      <div class="form-actions" style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="<?= url('admin/conteudos.php') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          💾 Salvar Conteúdo
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
