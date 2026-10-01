# Central Imóveis

Sistema pessoal para administração e consulta de imóveis.

## Arquitetura

- PHP 8.3 + Apache: aplicação web.
- Supabase PostgreSQL: banco de dados.
- Render: hospedagem.
- GitHub: código e versionamento.

A aplicação acessa o Supabase somente pelo servidor. A credencial privada do banco deve existir apenas nas variáveis de ambiente do Render.

## Recursos atuais

- Login administrativo com sessão.
- Proteção CSRF nos formulários.
- Cadastro, edição e exclusão de imóveis.
- Pesquisa por código, nome, bairro e proprietário.
- Filtro por bairro e status.
- Características, capacidade, distância da praia e diária.
- Banco centralizado no Supabase.
- Docker pronto para o Render.

## Variáveis de ambiente

No Render:

- SUPABASE_URL: URL do projeto Supabase.
- SUPABASE_SERVER_KEY: credencial privada usada apenas pelo servidor.
- ADMIN_USER: usuário de acesso.
- ADMIN_PASSWORD_HASH: hash da senha administrativa.

Gere o hash localmente:

```bash
php -r 'echo password_hash("SUA_SENHA", PASSWORD_DEFAULT), PHP_EOL;'
```

Nunca coloque a credencial privada ou a senha no GitHub.

## Banco

A tabela public.imoveis já existe no projeto Supabase e contém os campos usados pelo sistema.

## Desenvolvimento local

Defina as variáveis de ambiente e execute:

```bash
php -S localhost:8000
```
