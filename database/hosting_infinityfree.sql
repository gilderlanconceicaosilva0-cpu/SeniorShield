-- SeniorShield: INSTALACAO NOVA NO INFINITYFREE
-- Selecione um banco VAZIO ja criado no painel MySQL Databases antes de importar.
-- Nao tenta criar ou trocar o banco e NAO cria usuario administrador.
-- NAO importe este arquivo num banco que ja tenha dados.

-- =====================================================================
-- SeniorShield - Script de Criação do Banco de Dados (DDL)
-- Compatível com MySQL 5.7+ / MariaDB 10.2+ / XAMPP
-- Codificação: utf8mb4 (suporte a acentuação e caracteres especiais)
-- Baseado estritamente na especificação oficial: spec.md
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tabela: usuarios
-- Armazena os usuários cadastrados (comuns e administradores)
-- RF01, RF02, RF03, RF04, RF05, RF06, RF07 | RNF01, RNF02
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` INT AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `tipo_usuario` ENUM('comum', 'admin') NOT NULL DEFAULT 'comum',
  `data_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Tabela: TIPOS_GOLPE
-- Armazena as categorias e tipos de golpes conhecidos
-- RF40, RF41, RF42
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `TIPOS_GOLPE` (
  `id_tipo_golpe` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_golpe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tabela: INDICADORES
-- Armazena os indicadores de risco e palavras-chave para o cálculo do ISD
-- RF09, RF10, RF11, RF36, RF37, RF38, RF39
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `INDICADORES` (
  `id_indicador` INT AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT NULL,
  `palavras_chave` TEXT NOT NULL,
  `peso` INT NOT NULL DEFAULT 10,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_indicador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Tabela: ANALISES
-- Armazena as mensagens analisadas e os resultados do ISD por usuário
-- RF08, RF12, RF13, RF14, RF17, RF18, RF19, RF20, RF21, RF23
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ANALISES` (
  `id_analise` INT AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `mensagem` TEXT NOT NULL,
  `pontuacao_isd` INT NOT NULL,
  `classificacao` VARCHAR(50) NOT NULL,
  `data_analise` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_analise`),
  KEY `idx_analises_usuario` (`id_usuario`),
  CONSTRAINT `fk_analises_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Tabela: ANALISE_INDICADORES
-- Tabela intermediária de relacionamento N:N entre ANALISES e INDICADORES
-- Registra quais indicadores foram detectados e o peso aplicado em cada análise
-- RF14, RF22, Seção 6.4
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ANALISE_INDICADORES` (
  `id_analise` INT NOT NULL,
  `id_indicador` INT NOT NULL,
  `peso_aplicado` INT NOT NULL,
  PRIMARY KEY (`id_analise`, `id_indicador`),
  KEY `idx_ai_indicador` (`id_indicador`),
  CONSTRAINT `fk_ai_analise`
    FOREIGN KEY (`id_analise`)
    REFERENCES `ANALISES` (`id_analise`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ai_indicador`
    FOREIGN KEY (`id_indicador`)
    REFERENCES `INDICADORES` (`id_indicador`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Tabela: CONTEUDOS
-- Armazena artigos, dicas e orientações educativas de segurança digital
-- RF24, RF25, RF26, RF27
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `CONTEUDOS` (
  `id_conteudo` INT AUTO_INCREMENT,
  `titulo` VARCHAR(200) NOT NULL,
  `texto` TEXT NOT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'artigo',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `data_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_conteudo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Tabela: QUESTOES_APRENDIZADO
-- Armazena situações simuladas para o Modo Aprendizado
-- RF28, RF29, RF30, RF31, RF32, RF43, RF44, RF45
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `QUESTOES_APRENDIZADO` (
  `id_questao` INT AUTO_INCREMENT,
  `mensagem` TEXT NOT NULL,
  `resposta_correta` VARCHAR(50) NOT NULL,
  `explicacao` TEXT NOT NULL,
  `id_tipo_golpe` INT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_questao`),
  KEY `idx_qa_tipo_golpe` (`id_tipo_golpe`),
  CONSTRAINT `fk_qa_tipo_golpe`
    FOREIGN KEY (`id_tipo_golpe`)
    REFERENCES `TIPOS_GOLPE` (`id_tipo_golpe`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Tabela: TENTATIVAS_APRENDIZADO
-- Registra as respostas fornecidas pelos usuários e seu desempenho
-- RF33, RF34, RF35
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `TENTATIVAS_APRENDIZADO` (
  `id_tentativa` INT AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_questao` INT NOT NULL,
  `resposta_usuario` VARCHAR(50) NOT NULL,
  `acertou` TINYINT(1) NOT NULL,
  `data_tentativa` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tentativa`),
  KEY `idx_ta_usuario` (`id_usuario`),
  KEY `idx_ta_questao` (`id_questao`),
  CONSTRAINT `fk_ta_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_questao`
    FOREIGN KEY (`id_questao`)
    REFERENCES `QUESTOES_APRENDIZADO` (`id_questao`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- DADOS BASE E CONTEUDOS EDUCATIVOS

-- =====================================================================
-- SeniorShield - Dados Iniciais do Sistema (Seed)
-- Popula dados fundamentais para operação do motor do ISD e aprendizado.
-- NOTA IMPORTANTE: Nenhum usuário (comum ou administrador) é criado aqui.
-- =====================================================================

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


-- ATUALIZACOES DE REGRAS (MIGRACOES 001 e 002)

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
