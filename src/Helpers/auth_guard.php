<?php
/**
 * SeniorShield - Guarda de Autenticação e Controle de Acesso
 *
 * Protege rotas, verifica permissões de usuário/administrador e implementa
 * tokens anti-CSRF para submissão segura de formulários.
 * Atende aos requisitos RF06, RF07, CA13 e RNF06.
 */

require_once __DIR__ . '/utils.php';

/**
 * Verifica se há um usuário autenticado na sessão.
 *
 * @return bool
 */
function is_logged_in(): bool {
    initSecureSession();
    return !empty($_SESSION['usuario_id']);
}

/**
 * Verifica se o usuário autenticado possui o papel de administrador.
 *
 * @return bool
 */
function is_admin(): bool {
    initSecureSession();
    return is_logged_in() && (($_SESSION['tipo_usuario'] ?? '') === 'admin');
}

/**
 * Retorna os dados do usuário autenticado na sessão atual.
 *
 * @return array|null
 */
function get_logged_user(): ?array {
    initSecureSession();
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['usuario_id'],
        'nome'  => $_SESSION['usuario_nome'] ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
        'tipo'  => $_SESSION['tipo_usuario'] ?? 'comum'
    ];
}

/**
 * Garante que apenas usuários autenticados acessem a página.
 * Redireciona visitantes para a página de login.
 *
 * @param string $redirectDestino Página para a qual redirecionar caso não esteja logado
 */
function require_login(string $redirectDestino = 'login.php'): void {
    if (!is_logged_in()) {
        flash_set('aviso', 'Você precisa entrar na sua conta para acessar esta página.');
        header('Location: ' . url($redirectDestino));
        exit;
    }
}

/**
 * Garante que apenas administradores acessem a página (RF07, CA13, RNF06).
 * Usuários comuns ou visitantes são barrados imediatamente com HTTP 403 Forbidden.
 */
function require_admin(): void {
    initSecureSession();

    if (!is_logged_in()) {
        flash_set('aviso', 'Área restrita. Por favor, faça login com uma conta administrativa.');
        header('Location: ' . url('login.php'));
        exit;
    }

    if (!is_admin()) {
        // Bloqueio rigoroso de usuários comuns (RF07, CA13, RNF06)
        http_response_code(403);
        flash_set('erro', 'Acesso negado: Você não possui permissão para acessar a área administrativa.');
        header('Location: ' . url('index.php'));
        exit;
    }
}

/**
 * Gera ou recupera um token CSRF único para o ciclo da sessão.
 *
 * @return string
 */
function csrf_token(): string {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna um campo de formulário HTML oculto contendo o token CSRF.
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida o token CSRF recebido em requisições POST.
 *
 * @param string|null $token Token recebido via formulário
 * @return bool
 */
function csrf_validate(?string $token): bool {
    initSecureSession();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
