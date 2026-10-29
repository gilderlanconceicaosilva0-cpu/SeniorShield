<?php
/**
 * SeniorShield - Funções Utilitárias Gerais
 *
 * Fornece helpers de sanitização, mensagens flash na sessão,
 * formatação de datas e montagem de URLs.
 */

require_once __DIR__ . '/../../config/config.php';

/**
 * Escapa strings contra ataques XSS para exibição segura em HTML.
 *
 * @param string|null $string Texto a ser escapado
 * @return string Texto seguro
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera uma URL completa a partir de um caminho relativo.
 *
 * @param string $path Caminho relativo (ex: 'login.php' ou 'assets/css/style.css')
 * @return string URL absoluta para o recurso
 */
function url(string $path = ''): string {
    // Os links de navegacao devem permanecer no mesmo dominio da pagina.
    // APP_URL no .env pode estar incorreta em hospedagens InfinityFree.
    // Usar caminho absoluto local evita redirecionar para outro dominio.
    $script = str_replace('\\\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = dirname($script);

    // Paginas de administracao estao um nivel abaixo da raiz do projeto.
    if (preg_match('#/admin$#i', $dir)) {
        $dir = substr($dir, 0, -strlen('/admin'));
    }

    $dir = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    $path = ltrim($path, '/');
    return $dir . '/' . $path;
}

/**
 * Define uma mensagem temporária (flash message) na sessão.
 *
 * @param string $tipo 'sucesso', 'erro', 'aviso' ou 'info'
 * @param string $mensagem Conteúdo da mensagem
 */
function flash_set(string $tipo, string $mensagem): void {
    initSecureSession();
    $_SESSION['flash_messages'][] = [
        'tipo'     => $tipo,
        'mensagem' => $mensagem
    ];
}

/**
 * Recupera e limpa todas as mensagens flash da sessão.
 *
 * @return array Lista de mensagens no formato [['tipo' => ..., 'mensagem' => ...]]
 */
function flash_get(): array {
    initSecureSession();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Formata uma data/hora no padrão amigável brasileiro (d/m/Y às H:i).
 *
 * @param string|null $datetime Data no formato MySQL (YYYY-MM-DD HH:MM:SS)
 * @return string Data formatada ou '-' se vazia
 */
function format_datetime_br(?string $datetime): string {
    if (empty($datetime)) {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '-';
    }
    return date('d/m/Y \à\s H:i', $timestamp);
}
