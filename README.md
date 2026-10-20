# Site Gerencial

Aplicação local em Laravel preparada para receber, por etapas, os módulos de vendas e metas, notas fiscais de clientes CNPJ e contas a pagar.

Nesta etapa existem somente a página inicial, a navegação e páginas indicativas para os módulos. Não há autenticação, cadastros, operações de negócio ou tabelas específicas da papelaria.

## Tecnologias e dependências

Versões instaladas no projeto:

- PHP 8.5.
- Laravel Framework 13.37.
- Laravel Tinker 3.0.
- Blade para renderização das páginas.
- Tailwind CSS 4.3, integrado com `@tailwindcss/vite`.
- Vite 8.3 e Laravel Vite Plugin 3.2 para compilar os arquivos frontend.
- Pest 5.3 e Pest Laravel Plugin 5.0 para testes.
- Laravel Boost 2.10, Pail 1.2 e Pint 1.32 como ferramentas de desenvolvimento.
- PDO MySQL disponível na instalação PHP verificada.

Não foram adicionadas dependências nesta etapa. A interface usa CSS/Tailwind e não precisa de JavaScript de aplicação ou biblioteca de ícones.

## Executar localmente

Instale as dependências se ainda não estiverem instaladas:

```powershell
composer install
npm install
```

Em dois terminais, na pasta do projeto, execute:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Acesse o endereço local informado pelo Artisan, normalmente `http://127.0.0.1:8000`.

Para compilar os assets sem manter o servidor do Vite em execução:

```powershell
npm run build
```

## Banco de dados

O `.env` existente está configurado para SQLite. A configuração Laravel também oferece suporte a MySQL e o PHP possui PDO MySQL, mas o projeto ainda não está usando o servidor MySQL.

Para mudar futuramente para MySQL, primeiro crie um banco vazio pelo MySQL Workbench ou pelo cliente MySQL. Depois, edite localmente no `.env` as variáveis `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` com os dados do seu ambiente; configure também o cache, as filas e as sessões para não dependerem das tabelas padrão antes de aplicar as migrations. Não compartilhe o `.env` nem publique credenciais.

Após alterar o `.env`, limpe o cache de configuração:

```powershell
php artisan config:clear
```

Não há migrations específicas da papelaria. As migrations padrão do Laravel para usuários, cache e filas permanecem no projeto; nenhuma migration foi executada como parte da preparação desta interface.

## Testes

```powershell
php artisan test --compact
```

## Rotas iniciais

- `/` — página inicial.
- `/modulos/vendas` — placeholder de vendas e metas.
- `/modulos/notas-fiscais` — placeholder de notas fiscais.
- `/modulos/contas-a-pagar` — placeholder de contas a pagar.
