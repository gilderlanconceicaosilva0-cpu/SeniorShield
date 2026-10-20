-- =====================================================================
-- SeniorShield - Migração Segura de Atualização de Indicadores e Palavras-Chave
-- Arquivo: database/migrations/001_atualizar_indicadores_regras_golpe.sql
--
-- OBJETIVO:
-- 1. Atualizar e expandir as palavras-chave dos indicadores existentes (IDs 1 a 6)
--    incluindo termos críticos de golpe (ex: "você ganhou um prêmio", "taxa de liberação",
--    "pague uma taxa", "ainda hoje", "prazo termina hoje", "não perca essa oportunidade",
--    "devolvido ao remetente").
-- 2. Inserir com segurança os indicadores faltantes (IDs 7 a 10) sem duplicar registros.
-- 3. PRESERVAÇÃO TOTAL: NÃO executa DROP nem DELETE. Não apaga usuários, análises nem histórico.
-- =====================================================================

USE `seniorshield`;

-- 1. Atualiza palavras-chave dos indicadores já cadastrados (IDs 1 a 6)
UPDATE `INDICADORES` SET
  `palavras_chave` = 'urgente, imediatamente, agora, só hoje, so hoje, nao perca, não perca, não perca essa oportunidade, nao perca essa oportunidade, última chance, ultima chance, última oportunidade, ultima oportunidade, prazo final, prazo termina hoje, prazo se esgotando, tempo esgotando, atenção imediata, atencao imediata, ação necessária, acao necessaria, ação necessária hoje, acao necessaria hoje, evite o bloqueio, bloqueio hoje, cancelamento imediato, último aviso, ultimo aviso, responda imediatamente, regularize hoje, ainda hoje, agora mesmo, últimas horas, ultimas horas, tempo limite, evitar bloqueio, válido somente hoje, valido somente hoje, apenas hoje, termine hoje'
WHERE `id_indicador` = 1 OR `nome` = 'Urgência extrema';

UPDATE `INDICADORES` SET
  `palavras_chave` = 'pix, transferência, transferencia, transferir, dinheiro, depósito, deposito, deposite, pagamento, pagar, pague, pague uma taxa, pague taxa, pagar taxa, pagar a taxa, pague a taxa, taxa de liberação, taxa de liberacao, taxa para liberar, taxa para receber, pague para receber, taxa de entrega, taxa de cadastro, envie o valor, envie dinheiro, transfira agora, chave pix, boleto anexo, depósito antecipado, deposito antecipado, pagamento antecipado, faça um pix, faca um pix, faça o pagamento, faca o pagamento, mande um pix, envie um pix, fazer um pix, deposito para liberacao, depósito para liberação, pague uma pequena taxa, pequena taxa de liberação, pequena taxa de liberacao'
WHERE `id_indicador` = 2 OR `nome` = 'Pedido de dinheiro ou transferência';

UPDATE `INDICADORES` SET
  `palavras_chave` = 'bit.ly, tinyurl, is.gd, clique aqui, acesse o link, link abaixo, link desconhecido, confirme no link, acesse para regularizar, atualize seu cadastro, clique para receber, clique para resgatar, acesse para desbloquear, valide sua conta, página de verificação, pagina de verificacao, link para resgate, acesse o link encurtado, link encurtado, link enviado, pelo link enviado, link recebido, clique no link, acesse a pagina, acesse a página, link a seguir, link para desbloquear, regularizar-encomenda'
WHERE `id_indicador` = 3 OR `nome` = 'Link suspeito ou encurtado';

UPDATE `INDICADORES` SET
  `palavras_chave` = 'informe sua senha, envie o código, envie o codigo, código de confirmação, codigo de confirmacao, código sms, codigo sms, digite seu token, senha do cartão, senha do cartao, confirme sua senha, código de autenticação, codigo de autenticacao, código de segurança, codigo de seguranca, código recebido por sms, codigo recebido por sms, número do cartão, numero do cartao, dados bancários, dados bancarios, compartilhe sua senha, compartilhe o código, compartilhe o codigo, compartilhe o código de acesso, compartilhe o codigo de acesso, código de acesso, codigo de acesso, código de verificação, codigo de verificacao, envie o código de acesso, envie o codigo de acesso, informe seu código, informe seu codigo, informe o código, informe o codigo, digite sua senha, forneça sua senha, forneca sua senha, passe sua senha, passe o código, passe o codigo'
WHERE `id_indicador` = 4 OR `nome` = 'Pedido de senha ou código de confirmação';

UPDATE `INDICADORES` SET
  `palavras_chave` = 'parabéns você ganhou, parabens voce ganhou, você ganhou um prêmio, voce ganhou um premio, você ganhou, voce ganhou, ganhou um prêmio, ganhou um premio, prêmio liberado, premio liberado, benefício exclusivo, beneficio exclusivo, resgate agora, sorteio premiado, saldo disponível, saldo disponivel, você foi contemplado, voce foi contemplado, resgatar valor, ganhe dinheiro, ganha dinheiro, ganha 2000, ganhe 2000, ganho fácil, ganho facil, dinheiro fácil, dinheiro facil, lucro garantido, retorno garantido, multiplique seu dinheiro, renda extra garantida, reembolso disponível, reembolso disponivel, saldo a receber, celular grátis, celular gratis, celular gratuito, ganhe um celular, ganhou um celular, foi selecionado, selecionado para receber, receber um prêmio, receber um premio, prêmio surpresa, premio surpresa, prêmio especial, premio especial, prêmio em dinheiro, premio em dinheiro, confirme seu prêmio, confirme seu premio, pague a taxa para receber, taxa para liberar o prêmio, taxa para liberar o premio, selecionado para receber um prêmio, selecionado para receber um premio, você foi selecionado para receber um prêmio, voce foi selecionado para receber um premio, você foi selecionado, voce foi selecionado'
WHERE `id_indicador` = 5 OR `nome` = 'Oferta mirabolante ou falso prêmio';

UPDATE `INDICADORES` SET
  `palavras_chave` = 'sua conta foi bloqueada, acesso suspenso, serviço cancelado, servico cancelado, suspeita de fraude, regularize sua situação, regularize sua situacao, cpf bloqueado, evite cancelamento, conta será bloqueada, conta sera bloqueada, conta em análise, conta em analise, benefício bloqueado, beneficio bloqueado, cadastro suspenso, compra não reconhecida, compra nao reconhecida, fatura em atraso, dívida pendente, divida pendente, bloqueio da conta, bloqueio do cartao, bloqueio do cartão, evitar o bloqueio, conta suspensa, bloqueio imediato, seu pedido será devolvido, seu pedido sera devolvido, devolvido ao remetente, será devolvido ao remetente, sera devolvido ao remetente, devolução ao remetente, devolucao ao remetente, será devolvido, sera devolvido, evite a devolução, evite a devolucao, evite devolucao, caso não regularize, caso nao regularize'
WHERE `id_indicador` = 6 OR `nome` = 'Ameaça de bloqueio de conta ou serviço';

-- 2. Insere indicadores adicionais (IDs 7 a 10) caso ainda não existam no banco
INSERT INTO `INDICADORES` (`id_indicador`, `nome`, `descricao`, `palavras_chave`, `peso`, `ativo`) VALUES
(
  7,
  'Falso familiar ou pedido de ajuda',
  'Mensagens que podem imitar conhecidos e pedir dinheiro com urgência.',
  'troquei de número, troquei de numero, meu celular quebrou, novo número, novo numero, salva meu contato, faz um pix para mim, faz um pix pra mim, paga essa conta, preciso de dinheiro urgente, depois te devolvo, estou sem acesso à minha conta, estou sem acesso a minha conta, não posso falar agora, nao posso falar agora, estou usando outro número, estou usando outro numero, celular estragou, meu celular estragou, telefone quebrou, meu telefone quebrou, perdi meu celular, salve meu novo número, salve meu novo numero, salva esse número, salva esse numero, outro número, outro numero, salva o meu contato',
  20,
  1
),
(
  8,
  'Falsa entrega ou cobrança de encomenda',
  'Cobranças inesperadas para liberar encomendas, entregas ou reembolsos.',
  'pedido retido, entrega bloqueada, taxa alfandegária, taxa alfandegaria, produto apreendido, confirme o endereço, confirme o endereco, pagamento para liberar encomenda, liberar sua encomenda, taxa para receber encomenda, taxa de entrega pendente, entrega pendente, reembolso pendente, encomenda está retida, encomenda esta retida, encomenda retida, pacote retido, pacote aguardando liberação, pacote aguardando liberacao, taxa de liberação, taxa de liberacao, receber o pacote, liberar o pacote, liberar encomenda, objeto retido, mercadoria retida, taxa dos correios, taxa alfandega, código de rastreio, codigo de rastreio, rastreamento pendente, atualização de endereço, atualizacao de endereco, entrega de transportadora, pendência na alfândega, pendencia na alfandega, liberar a entrega, seu pedido será devolvido, seu pedido sera devolvido, devolvido ao remetente',
  15,
  1
),
(
  9,
  'Falso emprego, empréstimo ou investimento',
  'Promessas suspeitas de emprego, crédito ou retorno financeiro.',
  'vaga garantida, trabalho fácil, trabalho facil, ganhe por dia, renda sem esforço, renda sem esforco, investimento sem risco, lucro diário garantido, lucro diario garantido, retorno acima do mercado, empréstimo aprovado, emprestimo aprovado, crédito liberado, credito liberado, pague a taxa para liberar o empréstimo, pague a taxa para liberar o emprestimo, depósito para contratação, deposito para contratacao, comissão antecipada, comissao antecipada, selecionado para uma vaga, selecionado para vaga, vaga com salário alto, vaga com salario alto, taxa de cadastro, liberar sua contratação, liberar sua contratacao, liberar contratação, liberar contratacao, taxa para contratação, taxa para contratacao, taxa de inscrição, taxa de inscricao, depósito para cadastro, deposito para cadastro, vaga de emprego, salário alto garantido, salario alto garantido',
  20,
  1
),
(
  10,
  'Falso suporte ou acesso remoto',
  'Tentativas suspeitas de obter acesso ao dispositivo ou induzir transferências.',
  'somos do suporte, suporte técnico do seu banco, suporte tecnico do seu banco, suporte do banco, ligação do banco, ligacao do banco, funcionário do banco, funcionario do banco, central de segurança, central de seguranca, confirme uma operação, confirme uma operacao, instale este aplicativo, instale este app, compartilhe sua tela, compartilhe o código de acesso, compartilhe o codigo de acesso, código de acesso, codigo de acesso, acesso remoto, aplicativo de acesso remoto, controle remoto do dispositivo, valide sua identidade, validação por telefone, validacao por telefone, transfira para uma conta segura, conta protegida, procedimento de segurança, procedimento de seguranca, acesso ao seu dispositivo, controle do seu aparelho',
  20,
  1
)
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `descricao` = VALUES(`descricao`),
  `palavras_chave` = VALUES(`palavras_chave`),
  `peso` = VALUES(`peso`),
  `ativo` = VALUES(`ativo`);
