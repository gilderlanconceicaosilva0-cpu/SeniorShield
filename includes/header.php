<?php
/**
 * SeniorShield - Cabeçalho Padrão da Aplicação
 *
 * Configura metadados, links para CSS acessível e inicia a estrutura semântica da página.
 */

require_once __DIR__ . '/../src/Helpers/auth_guard.php';

// Garante sessão segura ativa
initSecureSession();

$tituloPagina = isset($pageTitle) ? e($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' — Proteção Contra Golpes Digitais';
$paginaAtual  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="SeniorShield — Plataforma de segurança digital e análise de mensagens suspeitas voltada para idosos e novatos da internet.">
  <title><?= $tituloPagina ?></title>

  <!-- Tipografia de Alta Legibilidade (Google Fonts - Plus Jakarta Sans) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Folha de Estilos Principal do SeniorShield -->
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
  <!-- Atalho de Acessibilidade para Leitores de Tela e Teclado -->
  <a href="#conteudo-principal" class="skip-link">Pular para o conteúdo principal</a>

  <!-- Barra de Navegação Superior -->
  <?php require_once __DIR__ . '/navbar.php'; ?>

  <!-- Início do Conteúdo Principal -->
  <main id="conteudo-principal" class="main-content">
    <div class="container">
      <!-- Exibição de Alertas e Mensagens Flash -->
      <?php require_once __DIR__ . '/alerts.php'; ?>
