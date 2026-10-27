<?php
/**
 * SeniorShield - Utilitário Seguro de Linha de Comando (CLI) para Gestão de Papéis
 *
 * Permite ao desenvolvedor ou avaliador promover um usuário já cadastrado
 * ao papel de administrador de forma segura, sem credenciais inseguras ou senhas fixas.
 *
 * Uso no terminal:
 *   php bin/promover_admin.php <email> [admin|comum]
 *
 * Exemplo:
 *   php bin/promover_admin.php gilderlanconceio@gmail.com admin
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Este script só pode ser executado via linha de comando (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

$email = $argv[1] ?? '';
$novoPerfil = strtolower($argv[2] ?? 'admin');

if (empty($email)) {
    echo "=================================================================\n";
    echo " SeniorShield - Gerenciamento Seguro de Permissões (CLI)\n";
    echo "=================================================================\n\n";
    echo "Uso:\n";
    echo "  php bin/promover_admin.php <email_do_usuario> [admin|comum]\n\n";
    echo "Exemplo:\n";
    echo "  php bin/promover_admin.php usuario@exemplo.com admin\n\n";
    echo "Para criar um novo usuário, cadastre-o primeiro pela interface web em cadastro.php.\n";
    exit(1);
}

if (!in_array($novoPerfil, ['admin', 'comum'], true)) {
    echo "[ERRO] Perfil inválido: '{$novoPerfil}'. Use 'admin' ou 'comum'.\n";
    exit(1);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT id_usuario, nome, email, tipo_usuario FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([trim(mb_strtolower($email))]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "[ERRO] Nenhum usuário encontrado com o e-mail: '{$email}'.\n";
        echo "Cadastre a conta primeiro através da tela de cadastro (cadastro.php).\n";
        exit(1);
    }

    if ($usuario['tipo_usuario'] === $novoPerfil) {
        echo "[INFO] O usuário '{$usuario['nome']}' ({$usuario['email']}) já possui o perfil: '{$novoPerfil}'.\n";
        exit(0);
    }

    // Proteção: Impede o rebaixamento do único administrador ativo
    if ($usuario['tipo_usuario'] === 'admin' && $novoPerfil === 'comum') {
        $stmtCountAdmins = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin'");
        $totalAdmins = (int)$stmtCountAdmins->fetchColumn();

        if ($totalAdmins <= 1) {
            echo "[ERRO] Operação negada: O usuário '{$usuario['nome']}' é o ÚNICO administrador ativo no sistema.\n";
            echo "Promova outro usuário a administrador antes de rebaixar esta conta para evitar a perda de acesso administrativo.\n";
            exit(1);
        }
    }

    $stmtUpdate = $pdo->prepare('UPDATE usuarios SET tipo_usuario = ? WHERE id_usuario = ?');
    $stmtUpdate->execute([$novoPerfil, $usuario['id_usuario']]);

    echo "=================================================================\n";
    echo "[SUCESSO] Perfil atualizado com sucesso!\n";
    echo "Usuário: {$usuario['nome']} (#{$usuario['id_usuario']})\n";
    echo "E-mail:  {$usuario['email']}\n";
    echo "Perfil:  {$usuario['tipo_usuario']} -> {$novoPerfil}\n";
    echo "=================================================================\n";
    echo "Agora você pode acessar a área administrativa em: /admin/index.php (ou a subpasta onde o sistema foi instalado)\n";
} catch (Exception $e) {
    echo "[ERRO FATAL] " . $e->getMessage() . "\n";
    exit(1);
}
