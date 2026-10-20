-- =====================================================================
-- SeniorShield - Dados Iniciais do Sistema (Seed)
-- Popula dados fundamentais para operação do motor do ISD e aprendizado.
-- NOTA IMPORTANTE: Nenhum usuário (comum ou administrador) é criado aqui.
-- =====================================================================

USE `seniorshield`;

-- ---------------------------------------------------------------------
-- 1. TIPOS_GOLPE
-- Categorias de golpes digitais comuns contra cidadãos e pessoas idosas
-- ---------------------------------------------------------------------
INSERT INTO `TIPOS_GOLPE` (`id_tipo_golpe`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Golpe do Falso Parente / Novo Número', 'Golpista se passa por um filho, neto ou parente alegando ter trocado de número de celular e solicita transferências ou pagamentos urgentes via Pix.', 1),
(2, 'Phishing / Roubo de Dados e Senhas', 'Mensagens fraudulentas com links falsos que imitam bancos, lojas ou serviços públicos para roubar dados de login, cartões ou senhas.', 1),
(3, 'Falso Suporte Bancário ou Segurança', 'Mensagem alarmista informando que a conta bancária foi bloqueada, invadida ou que precisa de atualização imediata de token/chave.', 1),
(4, 'Falso Prêmio ou Sorteio', 'Mensagem prometendo dinheiro, prêmios, herança ou benefício social não solicitado, exigindo taxas ou códigos para liberação.', 1),
(5, 'Falsa Cobrança / Ameaça Judicial', 'Notificação falsa alegando dívida urgente, protesto em cartório ou cancelamento de CPF caso não seja feito pagamento imediato.', 1);

-- ---------------------------------------------------------------------
-- 2. INDICADORES
-- Indicadores de risco utilizados pelo algoritmo do ISD
-- Os pesos são configurados com base nos exemplos da Seção 4 da spec.md
-- ---------------------------------------------------------------------

INSERT INTO `INDICADORES`
(`id_indicador`, `nome`, `descricao`, `palavras_chave`, `peso`, `ativo`)
VALUES
(
  1,
  'Urgência extrema',
  'Pressão psicológica para forçar uma ação imediata.',
  'urgente, imediatamente, agora, só hoje, so hoje, nao perca, não perca, não perca essa oportunidade, nao perca essa oportunidade, última chance, ultima chance, última oportunidade, ultima oportunidade, prazo final, prazo termina hoje, prazo se esgotando, tempo esgotando, atenção imediata, atencao imediata, ação necessária, acao necessaria, ação necessária hoje, acao necessaria hoje, evite o bloqueio, bloqueio hoje, cancelamento imediato, último aviso, ultimo aviso, responda imediatamente, regularize hoje, ainda hoje, agora mesmo, últimas horas, ultimas horas, tempo limite, evitar bloqueio, válido somente hoje, valido somente hoje, apenas hoje, termine hoje',
  15,
  1
),
(
  2,
  'Pedido de dinheiro ou transferência',
  'Solicitação suspeita de pagamentos, transferências ou depósitos.',
  'pix, transferência, transferencia, transferir, dinheiro, depósito, deposito, deposite, pagamento, pagar, pague, pague uma taxa, pague taxa, pagar taxa, pagar a taxa, pague a taxa, taxa de liberação, taxa de liberacao, taxa para liberar, taxa para receber, pague para receber, taxa de entrega, taxa de cadastro, envie o valor, envie dinheiro, transfira agora, chave pix, boleto anexo, depósito antecipado, deposito antecipado, pagamento antecipado, faça um pix, faca um pix, faça o pagamento, faca o pagamento, mande um pix, envie um pix, fazer um pix, deposito para liberacao, depósito para liberação, pague uma pequena taxa, pequena taxa de liberação, pequena taxa de liberacao',
  25,
  1
),
(
  3,
  'Link suspeito ou encurtado',
  'Tentativa de direcionar o usuário a links ou páginas potencialmente fraudulentas.',
  'bit.ly, tinyurl, is.gd, clique aqui, acesse o link, link abaixo, link desconhecido, confirme no link, acesse para regularizar, atualize seu cadastro, clique para receber, clique para resgatar, acesse para desbloquear, valide sua conta, página de verificação, pagina de verificacao, link para resgate, acesse o link encurtado, link encurtado, link enviado, pelo link enviado, link recebido, clique no link, acesse a pagina, acesse a página, link a seguir, link para desbloquear, regularizar-encomenda',
  20,
  1
),
(
  4,
  'Pedido de senha ou código de confirmação',
  'Tentativa de obter credenciais, códigos de autenticação ou dados pessoais.',
  'informe sua senha, envie o código, envie o codigo, código de confirmação, codigo de confirmacao, código sms, codigo sms, digite seu token, senha do cartão, senha do cartao, confirme sua senha, código de autenticação, codigo de autenticacao, código de segurança, codigo de seguranca, código recebido por sms, codigo recebido por sms, número do cartão, numero do cartao, dados bancários, dados bancarios, compartilhe sua senha, compartilhe o código, compartilhe o codigo, compartilhe o código de acesso, compartilhe o codigo de acesso, código de acesso, codigo de acesso, código de verificação, codigo de verificacao, envie o código de acesso, envie o codigo de acesso, informe seu código, informe seu codigo, informe o código, informe o codigo, digite sua senha, forneça sua senha, forneca sua senha, passe sua senha, passe o código, passe o codigo',
  25,
  1
),
(
  5,
  'Oferta mirabolante ou falso prêmio',
  'Promessas exageradas de prêmios, benefícios ou ganhos financeiros.',
  'parabéns você ganhou, parabens voce ganhou, você ganhou um prêmio, voce ganhou um premio, você ganhou, voce ganhou, ganhou um prêmio, ganhou um premio, prêmio liberado, premio liberado, benefício exclusivo, beneficio exclusivo, resgate agora, sorteio premiado, saldo disponível, saldo disponivel, você foi contemplado, voce foi contemplado, resgatar valor, ganhe dinheiro, ganha dinheiro, ganha 2000, ganhe 2000, ganho fácil, ganho facil, dinheiro fácil, dinheiro facil, lucro garantido, retorno garantido, multiplique seu dinheiro, renda extra garantida, reembolso disponível, reembolso disponivel, saldo a receber, celular grátis, celular gratis, celular gratuito, ganhe um celular, ganhou um celular, foi selecionado, selecionado para receber, receber um prêmio, receber um premio, prêmio surpresa, premio surpresa, prêmio especial, premio especial, prêmio em dinheiro, premio em dinheiro, confirme seu prêmio, confirme seu premio, pague a taxa para receber, taxa para liberar o prêmio, taxa para liberar o premio, selecionado para receber um prêmio, selecionado para receber um premio, você foi selecionado para receber um prêmio, voce foi selecionado para receber um premio, você foi selecionado, voce foi selecionado',
  15,
  1
),
(
  6,
  'Ameaça de bloqueio de conta ou serviço',
  'Uso de ameaças sobre contas, documentos ou serviços para pressionar o usuário.',
  'sua conta foi bloqueada, acesso suspenso, serviço cancelado, servico cancelado, suspeita de fraude, regularize sua situação, regularize sua situacao, cpf bloqueado, evite cancelamento, conta será bloqueada, conta sera bloqueada, conta em análise, conta em analise, benefício bloqueado, beneficio bloqueado, cadastro suspenso, compra não reconhecida, compra nao reconhecida, fatura em atraso, dívida pendente, divida pendente, bloqueio da conta, bloqueio do cartao, bloqueio do cartão, evitar o bloqueio, conta suspensa, bloqueio imediato, seu pedido será devolvido, seu pedido sera devolvido, devolvido ao remetente, será devolvido ao remetente, sera devolvido ao remetente, devolução ao remetente, devolucao ao remetente, será devolvido, sera devolvido, evite a devolução, evite a devolucao, evite devolucao, caso não regularize, caso nao regularize',
  20,
  1
),
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
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    palavras_chave = VALUES(palavras_chave),
    peso = VALUES(peso),
    ativo = VALUES(ativo);


-- ---------------------------------------------------------------------
-- 3. CONTEUDOS
-- Textos e orientações educativas sobre segurança digital (RF24)
-- ---------------------------------------------------------------------
INSERT INTO `CONTEUDOS` (`id_conteudo`, `titulo`, `texto`, `tipo`, `ativo`) VALUES
(1, 'O que é o Índice de Suspeição Digital (ISD)?', 'O ISD é uma pontuação calculada pelo SeniorShield que varia de 0 a 100. Quanto mais sinais típicos de golpes forem detectados na mensagem recebida (como pedidos de dinheiro, urgência excessiva, links suspeitos e pedidos de senha), maior será o risco. Lembre-se: o resultado é um alerta preventivo e serve para ajudá-lo a tomar cuidado antes de tomar qualquer decisão.', 'guia', 1),
(2, 'A Regra de Ouro: Bancos nunca pedem sua senha por mensagem', 'Nenhuma instituição financeira confiável ou funcionário legítimo de banco solicitará que você envie senhas, códigos recebidos por SMS ou fotos de cartões de crédito pelo WhatsApp, SMS ou telefone. Se alguém pedir isso dizendo ser do seu banco, desligue ou não responda e entre em contato direto pelo telefone que está no verso do seu cartão.', 'alerta', 1),
(3, 'Golpe do Novo Número no WhatsApp: O que fazer?', 'Se receber uma mensagem de alguém dizendo ser seu filho, neto ou parente com um número novo e pedindo dinheiro urgente porque o telefone quebrou: pare, respire e ligue imediatamente para o número antigo dessa pessoa. Não faça nenhum Pix ou transferência antes de ouvir a voz da pessoa por telefone ou confirmar pessoalmente.', 'dica', 1),
(4, 'Cuidado com links curtos e chamativos', 'Mensagens que dizem "Clique aqui para desbloquear sua conta" ou usam endereços estranhos e encurtados (como bit.ly) são muito perigosas. Elas levam para páginas falsas que imitam bancos e órgãos públicos. Sempre prefira abrir o aplicativo oficial da empresa ou banco diretamente no seu celular.', 'dica', 1);

-- ---------------------------------------------------------------------
-- 4. QUESTOES_APRENDIZADO
-- Situações simuladas para o Modo Aprendizado (RF28 a RF32)
-- ---------------------------------------------------------------------
INSERT INTO `QUESTOES_APRENDIZADO` (`id_questao`, `mensagem`, `resposta_correta`, `explicacao`, `id_tipo_golpe`, `ativo`) VALUES
(1, 'Oi mãe, salvei este número novo porque meu celular antigo estragou. Estou no banco e preciso pagar uma conta com urgência hoje, mas o meu aplicativo travou. Você consegue me transferir R$ 680 via Pix para a chave 11988887777 agora? Mais tarde te devolvo.', 'golpe', 'Esta situação apresenta os sinais mais clássicos do "Golpe do Falso Parente": alegação de número novo, urgência, pedido de transferência imediata e chave Pix desconhecida. Sempre ligue no número antigo do parente antes de qualquer ação.', 1, 1),
(2, 'Prezado(a), sua fatura do cartão com vencimento em 10/10 já está fechada no valor de R$ 342,00. Para consultar os detalhes e emitir a segunda via, acesse o aplicativo oficial do seu banco no seu celular.', 'seguro', 'A mensagem é apenas informativa: não solicita senhas, não envia links estranhos, não cria pânico imediato e orienta o cliente a usar o aplicativo oficial.', NULL, 1),
(3, 'URGENTE: Notamos uma transação suspeita na sua conta. Seu acesso foi preventivamente suspenso. Clique em bit.ly/regularizar-seguro-banco e informe sua senha e token para desbloquear imediatamente e evitar cancelamento.', 'golpe', 'Contém 4 sinais graves de perigo: pressão de urgência extrema, ameaça de bloqueio, link encurtado suspeito e pedido direto de senha e token pessoal.', 3, 1),
(4, 'PARABÉNS! Seu número de telefone foi sorteado para receber um prêmio de R$ 10.000,00 da promoção de final de ano. Para confirmar a liberação do depósito, envie de volta o código de 6 dígitos que enviamos via SMS.', 'golpe', 'Prêmios fáceis e inesperados condicionados ao reenvio de códigos recebidos por SMS são tentativas de roubo de conta (como invasão do seu WhatsApp ou aplicativo bancário). Nunca compartilhe códigos recebidos por SMS.', 4, 1);
