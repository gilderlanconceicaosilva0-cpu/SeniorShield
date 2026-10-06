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
INSERT INTO `INDICADORES` (`id_indicador`, `nome`, `descricao`, `palavras_chave`, `peso`, `ativo`) VALUES
(1, 'Urgência extrema', 'Pressão psicológica para ação imediata sem tempo para reflexão ou consulta a familiares.', 'urgente,imediatamente,agora,bloqueio hoje,cancelamento imediato,última chance,prazo final,atenção imediata,evite o bloqueio,ação necessária hoje', 15, 1),
(2, 'Pedido de dinheiro ou transferência', 'Solicitação explícita de envio de valores financeiros, depósitos ou pagamentos instantâneos.', 'pix,transferência,transferir,dinheiro,depósito,deposite,pagamento,pagar taxa,envie o valor,chave pix,boleto anexo', 25, 1),
(3, 'Link suspeito ou encurtado', 'Endereços web encurtados ou links desconhecidos para induzir o clique e direcionar a páginas falsas.', 'bit.ly,tinyurl,is.gd,clique aqui,acesse o link,link abaixo,link seguro,confirme no link,acesse para regularizar,atualize seu cadastro', 20, 1),
(4, 'Pedido de senha ou código de confirmação', 'Tentativa de obter senhas, códigos recebidos por SMS ou tokens de segurança pessoal.', 'informe sua senha,envie o código,código de confirmação,código sms,digite seu token,senha do cartão,confirme sua senha,código de autenticação', 25, 1),
(5, 'Oferta mirabolante ou falso prêmio', 'Promessa exagerada de ganho fácil, benefício governamental inesperado ou valores a receber.', 'parabéns você ganhou,prêmio liberado,benefício exclusivo,resgate agora,sorteio premiado,saldo disponível,você foi contemplado,resgatar valor', 15, 1),
(6, 'Ameaça de bloqueio de conta ou serviço', 'Tentativa de assustar o usuário com suspensão de serviços essenciais, contas de banco ou documentos.', 'sua conta foi bloqueada,acesso suspenso,serviço cancelado,suspeita de fraude,regularize sua situação,cpf bloqueado,evite cancelamento', 20, 1);

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
