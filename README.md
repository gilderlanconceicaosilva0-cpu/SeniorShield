# SeniorShield

Plataforma web em **PHP + MySQL** de apoio na identificacao de possiveis golpes digitais, com **Indice de Suspeicao Digital (ISD)**, orientacoes educativas, Modo Aprendizado e administracao de usuarios e conteudos.

A analise e **indicativa** e nao constitui confirmacao definitiva de fraude. Nunca informe senhas, codigos SMS ou documentos pessoais no formulario de analise.

## Publicar no InfinityFree

Acesse o tutorial detalhado em **[docs/PUBLICACAO_INFINITYFREE.md](docs/PUBLICACAO_INFINITYFREE.md)**.

1. Crie um banco MySQL no painel da hospedagem.
2. Em um banco vazio, importe **`database/hosting_infinityfree.sql`** pelo phpMyAdmin.
3. Envie os arquivos do repositorio diretamente para **`htdocs/`**.
4. Crie **`htdocs/.env`** usando **`.env.example`** como modelo. Nunca publique senhas no GitHub.
5. Crie uma conta em `/cadastro.php`; para acessar o painel admin, promova-a pelo phpMyAdmin conforme o tutorial.

O projeto inclui `spec.md` com os requisitos originais e testes de sintaxe PHP executados pelo GitHub Actions. Os testes de banco e de seguranca precisam ser conferidos na hospedagem real.
