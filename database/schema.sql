-- =====================================================================
-- SeniorShield - Script de Criação do Banco de Dados (DDL)
-- Compatível com MySQL 5.7+ / MariaDB 10.2+ / XAMPP
-- Codificação: utf8mb4 (suporte a acentuação e caracteres especiais)
-- Baseado estritamente na especificação oficial: spec.md
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `seniorshield`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `seniorshield`;

-- ---------------------------------------------------------------------
-- 1. Tabela: USUARIOS
-- Armazena os usuários cadastrados (comuns e administradores)
-- RF01, RF02, RF03, RF04, RF05, RF06, RF07 | RNF01, RNF02
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `USUARIOS` (
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
    REFERENCES `USUARIOS` (`id_usuario`)
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
    REFERENCES `USUARIOS` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_questao`
    FOREIGN KEY (`id_questao`)
    REFERENCES `QUESTOES_APRENDIZADO` (`id_questao`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
