<?php
/**
 * Leitura simples do .env na raiz do projeto, sem bibliotecas externas.
 * Variaveis reais do servidor tem prioridade sobre o arquivo.
 */
function seniorShieldEnv(string $key, ?string $default = null): ?string
{
    $runtime = getenv($key);
    if ($runtime !== false) {
        return $runtime;
    }
    static $values = null;
    if ($values === null) {
        $values = [];
        $filename = dirname(__DIR__) . '/.env';
        if (is_file($filename) && is_readable($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === '') {
                        continue;
                    }
                    if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
                        $value = substr($value, 1, -1);
                    }
                    $values[$name] = $value;
                }
            }
        }
    }
    return array_key_exists($key, $values) ? $values[$key] : $default;
}
