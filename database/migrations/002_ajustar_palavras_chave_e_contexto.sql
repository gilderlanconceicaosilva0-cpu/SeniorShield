-- =====================================================================
-- SeniorShield - Migração Segura: Ajuste Fino de Indicadores e Palavras-Chave
-- Arquivo: database/migrations/002_ajustar_palavras_chave_e_contexto.sql
--
-- OBJETIVO:
-- 1. Diferenciar dados bancários de senhas/tokens no Indicador 4 (Pedido de senha ou código).
-- 2. Diferenciar ameaça de devolução de encomenda (Indicador 8) de bloqueio de conta (Indicador 6).
-- 3. Incluir termos de sorteio condicionado a pagamento no Indicador 5 (Falso prêmio).
-- 4. Incluir termos de falsa equipe de suporte no Indicador 10 (Falso suporte).
-- 5. Incluir termos de liberação de encomenda com Pix no Indicador 8 (Falsa entrega).
--
-- SEGURANÇA:
-- Não executa DROP, não apaga usuários, não apaga análises existentes nem histórico.
-- Utiliza apenas instruções UPDATE pontuais sobre a tabela INDICADORES.
-- =====================================================================

USE `seniorshield`;

-- 1. Indicador 3: Link suspeito ou encurtado
-- Adiciona termos como link de regularização e link enviado nesta mensagem
UPDATE `INDICADORES` SET
  `palavras_chave` = 'bit.ly, tinyurl, is.gd, clique aqui, acesse o link, link abaixo, link desconhecido, confirme no link, acesse para regularizar, atualize seu cadastro, clique para receber, clique para resgatar, acesse para desbloquear, valide sua conta, página de verificação, pagina de verificacao, link para resgate, acesse o link encurtado, link encurtado, link enviado, pelo link enviado, link recebido, clique no link, acesse a pagina, acesse a página, link a seguir, link para desbloquear, regularizar-encomenda, link de regularização, link de regularizacao, link enviado nesta mensagem'
WHERE `id_indicador` = 3 OR `nome` = 'Link suspeito ou encurtado';

-- 2. Indicador 4: Pedido de senha ou código de confirmação
-- Remove termos cadastrais genéricos ("dados bancários") e inclui termos de código SMS/verificação
UPDATE `INDICADORES` SET
  `palavras_chave` = 'informe sua senha, envie o código, envie o codigo, código de confirmação, codigo de confirmacao, código sms, codigo sms, digite seu token, senha do cartão, senha do cartao, confirme sua senha, código de autenticação, codigo de autenticacao, código de segurança, codigo de seguranca, código recebido por sms, codigo recebido por sms, número do cartão, numero do cartao, compartilhe sua senha, compartilhe o código, compartilhe o codigo, compartilhe o código de acesso, compartilhe o codigo de acesso, código de acesso, codigo de acesso, código de verificação, codigo de verificacao, envie o código de acesso, envie o codigo de acesso, informe seu código, informe seu codigo, informe o código, informe o codigo, digite sua senha, forneça sua senha, forneca sua senha, passe sua senha, passe o código, passe o codigo, código que recebeu por sms, codigo que recebeu por sms, código que acabou de receber por sms, codigo que acabou de receber por sms, envie o código de verificação, envie o codigo de verificacao, sua senha'
WHERE `id_indicador` = 4 OR `nome` = 'Pedido de senha ou código de confirmação';

-- 2. Indicador 5: Oferta mirabolante ou falso prêmio
-- Adiciona termos de premiação por sorteio e condicionamento a recebimento
UPDATE `INDICADORES` SET
  `palavras_chave` = 'parabéns você ganhou, parabens voce ganhou, você ganhou um prêmio, voce ganhou um premio, você ganhou, voce ganhou, ganhou um prêmio, ganhou um premio, prêmio liberado, premio liberado, benefício exclusivo, beneficio exclusivo, resgate agora, sorteio premiado, saldo disponível, saldo disponivel, você foi contemplado, voce foi contemplado, resgatar valor, ganhe dinheiro, ganha dinheiro, ganha 2000, ganhe 2000, ganho fácil, ganho facil, dinheiro fácil, dinheiro facil, lucro garantido, retorno garantido, multiplique seu dinheiro, renda extra garantida, reembolso disponível, reembolso disponivel, saldo a receber, celular grátis, celular gratis, celular gratuito, ganhe um celular, ganhou um celular, foi selecionado, selecionado para receber, receber um prêmio, receber um premio, prêmio surpresa, premio surpresa, prêmio especial, premio especial, prêmio em dinheiro, premio em dinheiro, confirme seu prêmio, confirme seu premio, pague a taxa para receber, taxa para liberar o prêmio, taxa para liberar o premio, selecionado para receber um prêmio, selecionado para receber um premio, você foi selecionado para receber um prêmio, voce foi selecionado para receber um premio, você foi selecionado, voce foi selecionado, você foi sorteado e ganhou, voce foi sorteado e ganhou, você foi sorteado, voce foi sorteado, foi sorteado e ganhou, foi sorteado, ganhou r$, receber seu prêmio, receber seu premio, para receber seu prêmio, para receber seu premio'
WHERE `id_indicador` = 5 OR `nome` = 'Oferta mirabolante ou falso prêmio';

-- 3. Indicador 6: Ameaça de bloqueio de conta ou serviço
-- Foca estritamente em contas, acessos e serviços (termos de devolução de pacotes migram para o Indicador 8)
UPDATE `INDICADORES` SET
  `palavras_chave` = 'sua conta foi bloqueada, acesso suspenso, serviço cancelado, servico cancelado, suspeita de fraude, regularize sua situação, regularize sua situacao, cpf bloqueado, evite cancelamento, conta será bloqueada, conta sera bloqueada, conta em análise, conta em analise, benefício bloqueado, beneficio bloqueado, cadastro suspenso, compra não reconhecida, compra nao reconhecida, fatura em atraso, dívida pendente, divida pendente, bloqueio da conta, bloqueio do cartao, bloqueio do cartão, evitar o bloqueio, conta suspensa, bloqueio imediato, perderá o acesso à sua conta, perdera o acesso a sua conta, perderá o acesso, perdera o acesso, perda de acesso, perder o acesso, conta será bloqueada permanentemente, conta sera bloqueada permanentemente, bloqueada permanentemente, bloqueio permanente'
WHERE `id_indicador` = 6 OR `nome` = 'Ameaça de bloqueio de conta ou serviço';

-- 4. Indicador 8: Falsa entrega ou cobrança de encomenda
-- Centraliza ameaças de devolução de mercadoria e termos de aguardando liberação com Pix
UPDATE `INDICADORES` SET
  `palavras_chave` = 'pedido retido, entrega bloqueada, taxa alfandegária, taxa alfandegaria, produto apreendido, confirme o endereço, confirme o endereco, pagamento para liberar encomenda, liberar sua encomenda, taxa para receber encomenda, taxa de entrega pendente, entrega pendente, reembolso pendente, encomenda está retida, encomenda esta retida, encomenda retida, pacote retido, pacote aguardando liberação, pacote aguardando liberacao, taxa de liberação, taxa de liberacao, receber o pacote, liberar o pacote, liberar encomenda, objeto retido, mercadoria retida, taxa dos correios, taxa alfandega, código de rastreio, codigo de rastreio, rastreamento pendente, atualização de endereço, atualizacao de endereco, entrega de transportadora, pendência na alfândega, pendencia na alfandega, liberar a entrega, seu pedido será devolvido, seu pedido sera devolvido, devolvido ao remetente, pacote será devolvido, pacote sera devolvido, seu pacote será devolvido, seu pacote sera devolvido, retido na alfândega, retido na alfandega, para liberar sua entrega, encomenda está aguardando liberação, encomenda esta aguardando liberacao, aguardando liberação, aguardando liberacao, para concluir a entrega, concluir a entrega, pix para a transportadora'
WHERE `id_indicador` = 8 OR `nome` = 'Falsa entrega ou cobrança de encomenda';

-- 5. Indicador 10: Falso suporte ou acesso remoto
-- Adiciona variações de abordagem de equipe e agentes de suporte
UPDATE `INDICADORES` SET
  `palavras_chave` = 'somos do suporte, suporte técnico do seu banco, suporte tecnico do seu banco, suporte do banco, ligação do banco, ligacao do banco, funcionário do banco, funcionario do banco, central de segurança, central de seguranca, confirme uma operação, confirme uma operacao, instale este aplicativo, instale este app, compartilhe sua tela, compartilhe o código de acesso, compartilhe o codigo de acesso, código de acesso, codigo de acesso, acesso remoto, aplicativo de acesso remoto, controle remoto do dispositivo, valide sua identidade, validação por telefone, validacao por telefone, transfira para uma conta segura, conta protegida, procedimento de segurança, procedimento de seguranca, acesso ao seu dispositivo, controle do seu aparelho, somos da equipe de suporte, equipe de suporte, sou do suporte técnico, sou do suporte tecnico, sou do suporte, equipe de seguranca, equipe de segurança'
WHERE `id_indicador` = 10 OR `nome` = 'Falso suporte ou acesso remoto';
