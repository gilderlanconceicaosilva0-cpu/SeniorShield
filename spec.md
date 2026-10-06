# SeniorShield

## 1. Objetivo

O SeniorShield é uma plataforma web de segurança digital desenvolvida para auxiliar principalmente pessoas idosas e usuários com pouca experiência tecnológica na identificação de possíveis golpes digitais.

O sistema permitirá que o usuário insira uma mensagem suspeita para análise. A partir de indicadores de risco identificados no conteúdo, o sistema calculará o **Índice de Suspeição Digital (ISD)**, de 0 a 100, apresentará uma classificação de risco e explicará quais características contribuíram para o resultado.

Além da análise de mensagens, o sistema terá recursos educativos e um **Modo Aprendizado**, com situações simuladas para ajudar o usuário a reconhecer golpes.

---

## 2. Usuários

### 2.1 Usuário comum

O usuário comum poderá:

* criar uma conta;
* realizar login;
* analisar mensagens suspeitas;
* visualizar o resultado do ISD;
* visualizar os indicadores encontrados na mensagem;
* consultar seu histórico de análises;
* acessar conteúdos educativos;
* utilizar o Modo Aprendizado;
* visualizar seu desempenho nas atividades de aprendizado.

### 2.2 Administrador

O administrador poderá:

* realizar login na área administrativa;
* cadastrar, consultar, atualizar e desativar indicadores;
* cadastrar, consultar, atualizar e desativar tipos de golpes;
* definir e atualizar os pesos dos indicadores utilizados pelo ISD;
* cadastrar, consultar, atualizar e desativar conteúdos educativos;
* cadastrar, consultar, atualizar e desativar questões do Modo Aprendizado.

---

## 3. Casos de uso principais

### Usuário comum

1. Cadastrar usuário.
2. Realizar login.
3. Analisar mensagem suspeita.
4. Visualizar resultado da análise.
5. Consultar histórico de análises.
6. Visualizar indicadores encontrados.
7. Acessar conteúdos educativos.
8. Responder questões do Modo Aprendizado.
9. Visualizar desempenho no Modo Aprendizado.

### Administrador

10. Realizar login administrativo.
11. Gerenciar indicadores.
12. Gerenciar pesos dos indicadores.
13. Gerenciar tipos de golpes.
14. Gerenciar conteúdos educativos.
15. Gerenciar questões do Modo Aprendizado.

---

## 4. Requisitos funcionais

### Cadastro e autenticação

**RF01** — O sistema deve permitir o cadastro de novos usuários.

**RF02** — O sistema deve solicitar nome, e-mail e senha durante o cadastro.

**RF03** — O sistema deve impedir o cadastro de dois usuários com o mesmo e-mail.

**RF04** — O sistema deve armazenar as senhas de forma protegida por hash.

**RF05** — O sistema deve permitir que usuários cadastrados realizem login.

**RF06** — O sistema deve identificar o tipo de usuário após o login.

**RF07** — O sistema deve restringir a área administrativa aos usuários com permissão de administrador.

### Análise de mensagens

**RF08** — O sistema deve permitir que o usuário informe uma mensagem para análise.

**RF09** — O sistema deve analisar a mensagem em busca de indicadores de risco cadastrados no sistema.

**RF10** — O sistema deve identificar os indicadores encontrados na mensagem.

**RF11** — O sistema deve utilizar os pesos dos indicadores encontrados para calcular o Índice de Suspeição Digital (ISD).

**RF12** — O sistema deve apresentar o ISD em uma escala de 0 a 100.

**RF13** — O sistema deve classificar o resultado da análise de acordo com as faixas definidas.

**RF14** — O sistema deve apresentar ao usuário os indicadores que contribuíram para o resultado.

**RF15** — O sistema deve informar que o resultado representa uma avaliação de risco e não uma confirmação absoluta de fraude.

**RF16** — O sistema deve orientar o usuário a não inserir informações pessoais, senhas, códigos de confirmação ou outros dados sensíveis na mensagem enviada para análise.

### Classificação do ISD

O sistema utilizará as seguintes classificações:

| Pontuação | Classificação     |
| --------- | ----------------- |
| 0 a 20    | Risco muito baixo |
| 21 a 40   | Atenção           |
| 41 a 60   | Suspeito          |
| 61 a 80   | Alto risco        |
| 81 a 100  | Risco crítico     |

### Cálculo do ISD

O ISD será calculado a partir dos pesos dos indicadores encontrados na mensagem.

A fórmula inicial será:

**ISD = soma dos pesos dos indicadores encontrados**

O resultado será limitado ao valor máximo de 100.

Exemplo:

```text
Urgência = 15
Pedido de dinheiro = 25
Link suspeito = 20
Pedido de senha = 25

ISD = 15 + 25 + 20 + 25
ISD = 85
```

Nesse exemplo, a mensagem será classificada como **Risco crítico**.

Os pesos poderão ser alterados pelo administrador.

### Histórico

**RF17** — O sistema deve armazenar cada análise realizada por um usuário.

**RF18** — O sistema deve registrar a mensagem analisada.

**RF19** — O sistema deve registrar a pontuação do ISD.

**RF20** — O sistema deve registrar a classificação da análise.

**RF21** — O sistema deve registrar a data e hora da análise.

**RF22** — O sistema deve registrar quais indicadores foram identificados em cada análise.

**RF23** — O sistema deve permitir que o usuário consulte seu histórico de análises.

### Conteúdos educativos

**RF24** — O sistema deve permitir a exibição de conteúdos educativos sobre segurança digital.

**RF25** — O administrador deve poder cadastrar conteúdos educativos.

**RF26** — O administrador deve poder atualizar conteúdos educativos.

**RF27** — O administrador deve poder desativar conteúdos educativos.

### Modo Aprendizado

**RF28** — O sistema deve disponibilizar situações simuladas para o usuário identificar possíveis golpes.

**RF29** — O sistema deve apresentar uma mensagem simulada para cada questão.

**RF30** — O sistema deve permitir que o usuário informe sua resposta.

**RF31** — O sistema deve informar posteriormente se a resposta estava correta.

**RF32** — O sistema deve apresentar uma explicação sobre os sinais existentes na situação apresentada.

**RF33** — O sistema deve registrar as tentativas realizadas pelo usuário.

**RF34** — O sistema deve registrar se cada tentativa foi respondida corretamente.

**RF35** — O sistema deve permitir a consulta do desempenho do usuário no Modo Aprendizado.

### Área administrativa

**RF36** — O administrador deve poder cadastrar indicadores.

**RF37** — O administrador deve poder atualizar indicadores.

**RF38** — O administrador deve poder definir ou alterar o peso de um indicador.

**RF39** — O administrador deve poder ativar ou desativar indicadores.

**RF40** — O administrador deve poder cadastrar tipos de golpes.

**RF41** — O administrador deve poder atualizar tipos de golpes.

**RF42** — O administrador deve poder ativar ou desativar tipos de golpes.

**RF43** — O administrador deve poder cadastrar questões do Modo Aprendizado.

**RF44** — O administrador deve poder atualizar questões do Modo Aprendizado.

**RF45** — O administrador deve poder ativar ou desativar questões do Modo Aprendizado.

---

## 5. Requisitos não funcionais

### Segurança

**RNF01** — As senhas dos usuários nunca devem ser armazenadas em texto puro.

**RNF02** — O sistema deve utilizar hash de senha adequado para armazenamento seguro.

**RNF03** — O sistema deve utilizar consultas preparadas para evitar ataques de injeção SQL.

**RNF04** — Os dados recebidos pelos formulários devem ser validados no servidor.

**RNF05** — Credenciais, senhas ou chaves de acesso não devem ser armazenadas diretamente no código-fonte.

**RNF06** — Usuários comuns não devem conseguir acessar funcionalidades exclusivas do administrador.

**RNF07** — O sistema não deve exibir informações internas ou detalhes técnicos desnecessários em mensagens de erro para o usuário.

**RNF08** — A versão publicada do sistema deve utilizar HTTPS quando o provedor de hospedagem disponibilizar o recurso.

### Privacidade

**RNF09** — O sistema deve informar ao usuário que mensagens analisadas podem conter dados pessoais.

**RNF10** — O sistema deve orientar o usuário a não inserir senhas, códigos de confirmação ou outros dados sensíveis.

**RNF11** — O resultado do ISD deve ser apresentado como uma avaliação de risco, e não como uma confirmação definitiva de que uma mensagem é fraudulenta.

### Usabilidade

**RNF12** — A interface deve ser simples e fácil de compreender, considerando principalmente usuários idosos e pessoas com pouca experiência digital.

**RNF13** — As informações de risco devem ser apresentadas de forma clara e objetiva.

**RNF14** — Os principais recursos devem ser facilmente identificáveis na interface.

### Funcionamento

**RNF15** — O sistema deve funcionar em navegadores modernos.

**RNF16** — O sistema deve utilizar banco de dados relacional MySQL para armazenamento das informações.

---

## 6. Modelo de dados (DER simples)

O sistema utilizará as seguintes tabelas principais:

### 6.1 USUARIOS

Armazena os usuários cadastrados.

```text
USUARIOS
---------
PK id_usuario
   nome
   email
   senha_hash
   tipo_usuario
   data_cadastro
```

### 6.2 ANALISES

Armazena as análises realizadas pelos usuários.

```text
ANALISES
---------
PK id_analise
FK id_usuario
   mensagem
   pontuacao_isd
   classificacao
   data_analise
```

### 6.3 INDICADORES

Armazena os indicadores utilizados pelo algoritmo do ISD.

```text
INDICADORES
-----------
PK id_indicador
   nome
   descricao
   palavras_chave
   peso
   ativo
```

### 6.4 ANALISE_INDICADORES

Relaciona as análises aos indicadores encontrados.

```text
ANALISE_INDICADORES
-------------------
PK/FK id_analise
PK/FK id_indicador
     peso_aplicado
```

Essa tabela permite registrar quais indicadores contribuíram para cada análise.

### 6.5 TIPOS_GOLPE

Armazena os tipos de golpes utilizados pelo sistema.

```text
TIPOS_GOLPE
-----------
PK id_tipo_golpe
   nome
   descricao
   ativo
```

### 6.6 CONTEUDOS

Armazena os conteúdos educativos.

```text
CONTEUDOS
---------
PK id_conteudo
   titulo
   texto
   tipo
   ativo
   data_cadastro
```

### 6.7 QUESTOES_APRENDIZADO

Armazena as questões e situações simuladas do Modo Aprendizado.

```text
QUESTOES_APRENDIZADO
--------------------
PK id_questao
   mensagem
   resposta_correta
   explicacao
FK id_tipo_golpe
   ativo
```

### 6.8 TENTATIVAS_APRENDIZADO

Armazena as respostas dadas pelos usuários no Modo Aprendizado.

```text
TENTATIVAS_APRENDIZADO
----------------------
PK id_tentativa
FK id_usuario
FK id_questao
   resposta_usuario
   acertou
   data_tentativa
```

### 6.9 Relacionamentos

```text
USUARIOS 1:N ANALISES

ANALISES 1:N ANALISE_INDICADORES

INDICADORES 1:N ANALISE_INDICADORES

TIPOS_GOLPE 1:N QUESTOES_APRENDIZADO

USUARIOS 1:N TENTATIVAS_APRENDIZADO

QUESTOES_APRENDIZADO 1:N TENTATIVAS_APRENDIZADO
```

A relação entre `ANALISES` e `INDICADORES` é do tipo muitos-para-muitos (N), sendo implementada pela tabela intermediária `ANALISE_INDICADORES`.

---

## 7. Critérios de aceite

### CA01 — Cadastro

**Dado que** o usuário ainda não possui uma conta,

**quando** preencher corretamente os dados obrigatórios do cadastro,

**então** o sistema deve criar sua conta e armazenar seus dados no banco.

### CA02 — Login

**Dado que** o usuário possui uma conta cadastrada,

**quando** informar e-mail e senha corretos,

**então** o sistema deve permitir seu acesso.

### CA03 — Login inválido

**Dado que** o usuário informe credenciais incorretas,

**quando** tentar realizar o login,

**então** o sistema deve impedir o acesso e informar que as credenciais são inválidas.

### CA04 — Análise de mensagem

**Dado que** o usuário esteja autenticado,

**quando** enviar uma mensagem para análise,

**então** o sistema deve analisar os indicadores cadastrados e gerar um ISD.

### CA05 — Resultado do ISD

**Dado que** uma mensagem tenha sido analisada,

**quando** o sistema finalizar a análise,

**então** deve apresentar uma pontuação entre 0 e 100 e sua respectiva classificação.

### CA06 — Explicação da análise

**Dado que** uma mensagem possua indicadores de risco,

**quando** a análise for apresentada,

**então** o sistema deve informar quais indicadores foram identificados.

### CA07 — Armazenamento da análise

**Dado que** uma análise tenha sido concluída,

**quando** o resultado for gerado,

**então** a análise deve ser armazenada no banco de dados associada ao usuário responsável.

### CA08 — Histórico

**Dado que** o usuário já realizou análises,

**quando** acessar seu histórico,

**então** o sistema deve apresentar suas análises armazenadas.

### CA09 — Conteúdo educativo

**Dado que** existam conteúdos educativos ativos,

**quando** o usuário acessar a área educativa,

**então** o sistema deve apresentar esses conteúdos.

### CA10 — Modo Aprendizado

**Dado que** existam questões ativas,

**quando** o usuário iniciar o Modo Aprendizado,

**então** o sistema deve apresentar uma situação simulada para resposta.

### CA11 — Resultado do aprendizado

**Dado que** o usuário responda uma questão,

**quando** enviar sua resposta,

**então** o sistema deve informar se ela está correta e apresentar uma explicação.

### CA12 — Registro de tentativa

**Dado que** o usuário responda uma questão,

**quando** a resposta for processada,

**então** o sistema deve registrar a tentativa e o resultado no banco de dados.

### CA13 — Acesso administrativo

**Dado que** um usuário comum esteja autenticado,

**quando** tentar acessar diretamente uma funcionalidade administrativa,

**então** o sistema deve impedir o acesso.

### CA14 — Gerenciamento de indicadores

**Dado que** o administrador esteja autenticado,

**quando** cadastrar ou atualizar um indicador,

**então** o sistema deve armazenar ou atualizar os dados no banco.

### CA15 — Gerenciamento de conteúdos

**Dado que** o administrador esteja autenticado,

**quando** cadastrar ou atualizar um conteúdo educativo,

**então** o sistema deve armazenar ou atualizar o conteúdo no banco.

### CA16 — Segurança de senha

**Dado que** um usuário realize seu cadastro,

**quando** sua senha for armazenada,

**então** o sistema não deve armazenar a senha em texto puro.

---

## 8. Decisão de stack e deploy

### Tecnologias

O SeniorShield será desenvolvido utilizando:

* **PHP** para a lógica do sistema;
* **HTML** para a estrutura das páginas;
* **CSS** para estilização e interface;
* **JavaScript**, quando necessário para interações no navegador;
* **MySQL** para armazenamento dos dados;
* **Git** para controle de versão;
* **GitHub** para armazenamento e acompanhamento do código-fonte.

### Banco de dados

O banco de dados utilizado será o **MySQL**, contendo as tabelas definidas no modelo de dados desta especificação.

### Hospedagem

Como o sistema será desenvolvido inicialmente em PHP e MySQL, o provedor de hospedagem deverá oferecer suporte compatível com PHP e MySQL.

A utilização do Vercel será considerada somente caso a arquitetura do sistema seja alterada para uma tecnologia compatível com o ambiente da plataforma.

A decisão final do provedor de hospedagem será registrada antes da implantação do sistema.

---

## 9. Escopo da primeira versão

A primeira versão do SeniorShield terá como prioridade:

* cadastro e login de usuários;
* controle de acesso entre usuário comum e administrador;
* análise de mensagens;
* identificação de indicadores de risco;
* cálculo do ISD;
* classificação do risco;
* explicação dos indicadores encontrados;
* armazenamento do histórico de análises;
* conteúdos educativos;
* Modo Aprendizado;
* registro das tentativas de aprendizado;
* área administrativa;
* gerenciamento de indicadores;
* gerenciamento de tipos de golpes;
* gerenciamento de conteúdos;
* gerenciamento de questões do Modo Aprendizado.

Funcionalidades avançadas, como análise de imagens, áudio, integração direta com aplicativos de mensagens e utilização de inteligência artificial generativa poderão ser consideradas em versões futuras e não fazem parte do escopo obrigatório da primeira versão.

---

## 10. Observações de segurança e privacidade

O SeniorShield é uma ferramenta de apoio à identificação de possíveis golpes e não deve apresentar suas classificações como confirmação absoluta de fraude.

O usuário deverá ser orientado a não inserir senhas, códigos de autenticação, dados bancários ou outras informações sensíveis nas mensagens submetidas para análise.

O sistema deverá aplicar boas práticas de segurança no armazenamento de senhas, acesso ao banco de dados, validação dos dados enviados pelos usuários e controle de acesso às funcionalidades administrativas.

---

## 11. Definição de conclusão da primeira versão

A primeira versão será considerada concluída quando:

* o usuário conseguir criar uma conta;
* o usuário conseguir realizar login;
* o usuário conseguir enviar uma mensagem para análise;
* o sistema conseguir identificar indicadores cadastrados;
* o sistema conseguir calcular e apresentar o ISD;
* o sistema conseguir explicar os indicadores encontrados;
* a análise for armazenada no banco;
* o usuário conseguir consultar seu histórico;
* o Modo Aprendizado estiver funcionando;
* as tentativas forem registradas;
* a área administrativa estiver protegida;
* o administrador conseguir gerenciar os dados previstos;
* os principais critérios de aceite forem testados;
* o sistema estiver disponível no ambiente de implantação escolhido;
* o código estiver versionado no GitHub.

---

## 12. Controle de versão da especificação

Esta especificação deve ser mantida junto ao código-fonte do projeto no arquivo:

```text
spec.md
```

Alterações relevantes nos requisitos, modelo de dados, funcionalidades ou tecnologias deverão ser registradas na própria especificação e consideradas durante o desenvolvimento.

A `spec.md` será utilizada como referência para os pedidos realizados ao agente de desenvolvimento e para verificar se as funcionalidades implementadas correspondem ao planejamento do projeto.
