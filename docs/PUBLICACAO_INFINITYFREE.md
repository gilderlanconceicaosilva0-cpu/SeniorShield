# SeniorShield — Publicacao no InfinityFree

Este guia instala o sistema PHP + MySQL diretamente no **htdocs**. Nao exige Composer, Node.js ou servidor SSH.

## 1. Criar hospedagem e banco
1. Crie o dominio/site no InfinityFree.
2. No painel, entre em **MySQL Databases** e crie um banco.
3. Copie o **MySQL Host Name**, **Database Name**, **MySQL User Name** e use a senha MySQL da hospedagem. **Nao envie esses dados em conversas nem os publique no GitHub.**
4. Abra o phpMyAdmin pelo painel e selecione o banco criado.

## 2. Importar SQL
- **Instalacao NOVA (banco vazio):** importe somente `database/hosting_infinityfree.sql` em phpMyAdmin > Importar.
- Esse SQL cria 8 tabelas, alimenta indicadores e conteudos educativos e aplica as atualizacoes 001/002. **Nao cria contas com senhas predefinidas.**
- **Banco existente:** NAO importe o SQL completo. Faca backup primeiro. Confira os nomes das tabelas. O codigo usa **`usuarios` em minusculo** e as demais tabelas em maiusculo (ex.: `INDICADORES`). Se o banco antigo tiver apenas `USUARIOS` em maiusculo, e NAO existir `usuarios`, apos backup execute `RENAME TABLE \`USUARIOS\` TO \`usuarios\`;` no phpMyAdmin. Nao execute isso se houver ambas as tabelas; nesse caso, reconcilie os dados primeiro.

## 3. Publicar arquivos
1. Baixe o ZIP do codigo no GitHub (`Code > Download ZIP`).
2. Extraia o ZIP no computador.
3. No gerenciador do InfinityFree abra `htdocs/` e envie **o conteudo da pasta extraida**, nao a pasta `SeniorShield-main` em si.
4. Confirme que `htdocs/index.php`, `htdocs/login.php` e `htdocs/.htaccess` existem na raiz.

## 4. Criar configuracao privada
Em `htdocs/.env` crie um arquivo com estas linhas, substituindo os dados pelos **valores reais do painel**:

```ini
APP_ENV=production
APP_URL=https://SEU-DOMINIO
DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_NAME=if0_XXXXXXXX_seniorshield
DB_USER=if0_XXXXXXXX
DB_PASS=SUA_SENHA_MYSQL
DB_CHARSET=utf8mb4
```

O arquivo `.env` nao faz parte do GitHub. **Nunca salve nele dados ficticios se quiser conectar ao banco real.** A senha pode conter espacos; o leitor preserva o valor (exceto espacos nas pontas).
Antes de inserir a senha, abra `https://SEU-DOMINIO/.env` para testar o bloqueio: deve dar **403/404**, nunca exibir o conteudo. Caso apareca o arquivo, interrompa a publicacao e corrija a configuracao Apache/htaccess.

## 5. Conta administradora
1. Acesse `https://SEU-DOMINIO/cadastro.php` e crie sua conta com uma senha unica.
2. No phpMyAdmin, no banco do SeniorShield, confirme a conta cadastrada e promova **apenas o e-mail escolhido**:

```sql
UPDATE usuarios SET tipo_usuario = 'admin' WHERE email = 'SEU_EMAIL_CADASTRADO';
```

3. Saia e entre novamente. A area de administracao fica em `/admin/index.php`. **Nenhum administrador ou senha publica e criado pelo instalador.**

## 6. Checklist de teste
- `/`, `/cadastro.php` e `/login.php` carregam.
- Cadastro e login realmente funcionam com o banco importado.
- `/analise.php` calcula ISD usando indicadores cadastrados.
- Conteudos e Modo Aprendizado exibem dados.
- Uma conta comum nao abre `/admin/`; a conta promovida abre.
- `/.env`, `/config/database.php`, `/database/hosting_infinityfree.sql`, `/tests/test_all_spec_requirements.php` e `/bin/promover_admin.php` nao ficam publicamente acessiveis.

**Importante:** testes PHP automatizados checam sintaxe, mas nao substituem o teste real do MySQL no InfinityFree. Evite executar os testes de integracao diretamente no banco de producao, pois eles criam e apagam registros de teste.

## Erros comuns
- **Erro 500:** revise a compatibilidade do `.htaccess` com o provedor, a versao do PHP e os logs.
- **Conexao recusada:** confira `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` do painel; use o hostname SQL fornecido pelo InfinityFree, nao `localhost`.
- **Table doesn't exist:** confira o nome da tabela e maiusculas/minusculas. O PHP usa `usuarios` minusc. e as outras tabelas em maiusculas.
- **404:** confira se os arquivos estao diretamente no `htdocs/`, sem uma pasta adicional.
