<?php
/**
 * SeniorShield - Rodapé Padrão da Aplicação
 *
 * Contém o disclaimer legal institucional, créditos de acessibilidade e encerramento do documento HTML.
 */
?>
    </div> <!-- Fim do container -->
  </main> <!-- Fim do conteúdo principal -->

  <footer class="site-footer">
    <div class="container">
      <!-- Caixa de Isenção Institucional Obrigatória (RF15, RNF11, Seção 10) -->
      <div class="footer-disclaimer-box">
        <strong>Aviso Legal:</strong> <?= ISD_DISCLAIMER_AVALIACAO ?> O SeniorShield é uma ferramenta educativa e preventiva para apoiar a identificação de comportamentos suspeitos. Em caso de dúvidas sobre transações ou contas bancárias, entre em contato exclusivamente pelos canais oficiais de suas instituições financeiras.
      </div>

      <div class="footer-bottom">
        <div>
          <strong><?= APP_NAME ?></strong> &copy; <?= date('Y') ?> — Segurança Digital com Acessibilidade para Todos.
        </div>
        <ul class="footer-links">
          <li><a href="<?= url('index.php') ?>">Início</a></li>
          <li><a href="<?= url('conteudos.php') ?>">Dicas de Segurança</a></li>
          <li><a href="<?= url('aprendizado.php') ?>">Modo Aprendizado</a></li>
        </ul>
      </div>
    </div>
  </footer>
</body>
</html>
