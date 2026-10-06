<?php
/**
 * SeniorShield - Renderizador de Alertas e Mensagens Flash
 *
 * Exibe notificações contextuais de sucesso, erro, aviso ou informação.
 */

$flashMessages = flash_get();
if (!empty($flashMessages)):
?>
  <div class="alerts-container" aria-live="polite">
    <?php foreach ($flashMessages as $msg): 
      $tipo = $msg['tipo'] ?? 'info';
      $classeAlert = 'alert-' . $tipo;
      $icone = 'ℹ️';

      if ($tipo === 'sucesso') {
        $icone = '✅';
      } elseif ($tipo === 'erro') {
        $icone = '❌';
      } elseif ($tipo === 'aviso') {
        $icone = '⚠️';
      }
    ?>
      <div class="alert <?= e($classeAlert) ?>" role="alert">
        <span class="alert-icon" aria-hidden="true"><?= $icone ?></span>
        <div><?= e($msg['mensagem']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
