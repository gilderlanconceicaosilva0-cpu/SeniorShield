<?php
/**
 * SeniorShield - Encerramento de Sessão (Logout)
 *
 * Limpa todos os dados de sessão do usuário, invalida cookies e redireciona para o início.
 */

require_once __DIR__ . '/src/Helpers/auth_guard.php';
require_once __DIR__ . '/src/Services/AuthService.php';

$authService = new AuthService();
$authService->logout();

// Mensagem informativa e redirecionamento
flash_set('info', 'Você saiu da sua conta com segurança. Até logo!');
header('Location: ' . url('index.php'));
exit;
