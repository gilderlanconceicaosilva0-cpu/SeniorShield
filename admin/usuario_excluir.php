<?php
/**
 * SeniorShield - Processamento Seguro de Exclusão de Usuário
 *
 * Recebe exclusivamente requisições POST com validação obrigatória
 * de token CSRF e confirmação de permissões administrativas (RF07, CA13, RNF03, RNF04, RNF06).
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/UserService.php';

// Bloqueio rigoroso no servidor para usuários não autorizados
require_admin();

// Rejeita acessos diretos via GET para prevenir exclusões acidentais ou por prefetch
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    flash_set('aviso', 'A exclusão de usuários deve ser realizada através do formulário de confirmação.');
    header('Location: ' . url('admin/usuarios.php'));
    exit;
}

// Validação obrigatória do Token CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    flash_set('erro', 'Sua sessão de segurança expirou. Por favor, tente novamente.');
    header('Location: ' . url('admin/usuarios.php'));
    exit;
}

$adminLogado   = get_logged_user();
$idAdminLogado = (int)($adminLogado['id'] ?? 0);
$idUsuario     = (int)($_POST['id_usuario'] ?? 0);

if ($idUsuario <= 0) {
    flash_set('erro', 'Identificador de usuário inválido para exclusão.');
    header('Location: ' . url('admin/usuarios.php'));
    exit;
}

$userService = new UserService();
$resultado   = $userService->excluir($idUsuario, $idAdminLogado);

if ($resultado['sucesso']) {
    flash_set('sucesso', 'O usuário foi excluído do sistema com sucesso.');
} else {
    flash_set('erro', $resultado['erro'] ?? 'Não foi possível excluir o usuário.');
}

header('Location: ' . url('admin/usuarios.php'));
exit;
