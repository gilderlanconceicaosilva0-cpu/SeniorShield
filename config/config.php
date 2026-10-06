<?php
/**
 * SeniorShield - Configurações Gerais da Aplicação
 *
 * Centraliza definições de ambiente, constantes do Índice de Suspeição Digital (ISD),
 * parâmetros de segurança de sessão e avisos obrigatórios de privacidade.
 * Baseado estritamente em spec.md.
 */

// Evita redefinições caso o arquivo seja incluído múltiplas vezes
if (defined('SENIORSHIELD_CONFIG_LOADED')) {
    return;
}
define('SENIORSHIELD_CONFIG_LOADED', true);

// ---------------------------------------------------------------------
// 1. Informações Básicas da Aplicação
// ---------------------------------------------------------------------
defined('APP_NAME')        || define('APP_NAME', 'SeniorShield');
defined('APP_VERSION')     || define('APP_VERSION', '1.0.0');
defined('APP_DESCRIPTION') || define('APP_DESCRIPTION', 'Plataforma de segurança digital para apoio na identificação de possíveis golpes.');

// Ambiente da aplicação: 'development' ou 'production'
defined('APP_ENV') || define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Configuração de exibição de erros (RNF07)
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// ---------------------------------------------------------------------
// 2. URL Base do Sistema
// ---------------------------------------------------------------------
if (!defined('BASE_URL')) {
    // Permite sobrescrita por variável de ambiente
    $envBaseUrl = getenv('APP_URL');
    if ($envBaseUrl) {
        define('BASE_URL', rtrim($envBaseUrl, '/'));
    } else {
        // Detecção automática para ambiente local no Apache/XAMPP
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir  = str_replace('\\', '/', dirname($script));
        
        // Remove subpastas como /admin se estiver dentro dela
        $cleanDir = preg_replace('#/(admin|src|config|includes|assets)(/.*)?$#i', '', $baseDir);
        $cleanDir = ($cleanDir === '/' || $cleanDir === '.') ? '' : rtrim($cleanDir, '/');
        
        define('BASE_URL', $protocol . $host . $cleanDir);
    }
}

// ---------------------------------------------------------------------
// 3. Parâmetros do Índice de Suspeição Digital (ISD)
// RF11, RF12, RF13, Seção 4
// ---------------------------------------------------------------------
define('ISD_MAX_SCORE', 100);

// Faixas de risco do ISD
define('ISD_FAIXA_RISCO_MUITO_BAIXO', 'Risco muito baixo'); // 0 a 20
define('ISD_FAIXA_ATENCAO',           'Atenção');           // 21 a 40
define('ISD_FAIXA_SUSPEITO',          'Suspeito');          // 41 a 60
define('ISD_FAIXA_ALTO_RISCO',        'Alto risco');        // 61 a 80
define('ISD_FAIXA_RISCO_CRITICO',     'Risco crítico');     // 81 a 100

/**
 * Retorna a classificação textual do ISD com base na pontuação obtida.
 *
 * @param int $pontuacao Pontuação de 0 a 100
 * @return string Nome da classificação oficial
 */
function getIsdClassification(int $pontuacao): string {
    $pontuacao = max(0, min(ISD_MAX_SCORE, $pontuacao));

    if ($pontuacao <= 20) {
        return ISD_FAIXA_RISCO_MUITO_BAIXO;
    } elseif ($pontuacao <= 40) {
        return ISD_FAIXA_ATENCAO;
    } elseif ($pontuacao <= 60) {
        return ISD_FAIXA_SUSPEITO;
    } elseif ($pontuacao <= 80) {
        return ISD_FAIXA_ALTO_RISCO;
    } else {
        return ISD_FAIXA_RISCO_CRITICO;
    }
}

// ---------------------------------------------------------------------
// 4. Textos Obrigatórios de Privacidade e Isenção (Disclaimers)
// RF15, RF16, RNF09, RNF10, RNF11, Seção 10
// ---------------------------------------------------------------------
define('ISD_DISCLAIMER_AVALIACAO',
    'Atenção: O resultado apresentado pelo SeniorShield representa uma avaliação indicativa de risco e não uma confirmação absoluta ou definitiva de fraude.'
);

define('ISD_AVISO_PRIVACIDADE',
    'Aviso de Privacidade: Nunca insira senhas, códigos de confirmação (SMS/WhatsApp), dados de cartões ou documentos pessoais na mensagem enviada para análise.'
);

// ---------------------------------------------------------------------
// 5. Inicialização Segura de Sessão (RNF06)
// ---------------------------------------------------------------------
/**
 * Inicia a sessão PHP de forma segura, prevenindo fixação e sequestro de sessão.
 */
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de cookies seguros antes do session_start
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        
        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }

        session_start();
    }
}
