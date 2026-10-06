<?php
/**
 * SeniorShield - Exemplo de Configuração Local de Banco de Dados
 *
 * Para personalizar suas credenciais em um ambiente específico (como servidor de produção
 * ou máquina com senha personalizada no MySQL):
 * 1. Copie este arquivo para "config/database.local.php"
 * 2. Preencha com as suas credenciais reais.
 *
 * O arquivo "database.local.php" já está incluso no .gitignore e NUNCA será versionado.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'seniorshield');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha_secreta');
define('DB_CHARSET', 'utf8mb4');
