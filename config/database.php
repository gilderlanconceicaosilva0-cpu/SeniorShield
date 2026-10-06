<?php
/**
 * SeniorShield - Configuração de Conexão com o Banco de Dados (PDO)
 *
 * Suporta:
 * 1. Arquivo de configuração local não versionado (config/database.local.php)
 * 2. Variáveis de ambiente (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT)
 * 3. Valores padrão para desenvolvimento local no ambiente XAMPP padrão (root / sem senha)
 *
 * Cumpre RNF03 (Prepared Statements) e RNF05/RNF07 (Sem credenciais de produção no código e sem vazamento de erros).
 */

// 1. Carrega configuração local se existir (não versionada no Git via .gitignore)
$localConfigFile = __DIR__ . '/database.local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// 2. Define constantes com suporte a variáveis de ambiente e fallback local XAMPP
defined('DB_HOST')    || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_PORT')    || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME')    || define('DB_NAME', getenv('DB_NAME') ?: 'seniorshield');
defined('DB_USER')    || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS')    || define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
defined('DB_CHARSET') || define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

/**
 * Retorna uma instância PDO conectada ao banco de dados MySQL.
 * Utiliza o padrão Singleton (instância estática) para reutilização durante o ciclo da requisição.
 *
 * @throws PDOException Em caso de falha de conexão (com mensagem segura)
 * @return PDO
 */
function getDbConnection(): PDO {
    static $pdoInstance = null;

    if ($pdoInstance !== null) {
        return $pdoInstance;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        // Lança exceções para tratamento seguro
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Retorna arrays associativos por padrão
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Garante o uso de Prepared Statements reais no MySQL (RNF03)
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Define o conjunto de caracteres na inicialização
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET
    ];

    try {
        $pdoInstance = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdoInstance;
    } catch (PDOException $e) {
        // Registra o erro técnico detalhado nos logs do servidor para depuração segura
        error_log('SeniorShield Database Connection Error: ' . $e->getMessage());

        // Mensagem genérica segura sem expor credenciais, senhas ou caminhos de arquivo (RNF07)
        if (defined('APP_ENV') && APP_ENV === 'development') {
            $msg = 'Erro ao conectar ao banco de dados: verifique se o MySQL no XAMPP está iniciado e se a base "' . htmlspecialchars(DB_NAME) . '" foi criada.';
        } else {
            $msg = 'O serviço de banco de dados está temporariamente indisponível. Por favor, tente novamente mais tarde.';
        }

        throw new Exception($msg, (int)$e->getCode());
    }
}
