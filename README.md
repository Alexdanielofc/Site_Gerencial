# Site Gerencial

Sistema local de gestão empresarial para operação diária, sem necessidade de login, projetado para execução local em computador único.

## Módulos do Sistema

1. **Dashboard (`/`)**: Painel central integrado com seletor de período dinâmico, visão consolidada de vendas, notas fiscais, contas a pagar e bloco de atenção com contas vencidas e próximos vencimentos.
2. **Vendas e Metas (`/modulos/vendas`)**:
   - Registro de venda do dia com prevenção de duplicação.
   - Comparativo com meta acumulada e expectativa diária.
   - Gestão de expectativas do mês em lote (`/modulos/vendas/expectativas`).
   - Visão avançada mensal com histórico diário (`/modulos/vendas/avancado`).
3. **Notas Fiscais (`/modulos/notas-fiscais`)**:
   - Cadastro ágil de notas emitidas com data, número, cliente, CNPJ e valor.
   - Listagem e edição das notas emitidas no dia.
   - Consulta analítica avançada com busca e ticket médio (`/modulos/notas-fiscais/avancado`).
4. **Contas a Pagar (`/modulos/contas-a-pagar`)**:
   - Cadastro e acompanhamento de compromissos financeiros.
   - Seção "Em atenção" com contas vencidas e a vencer nos próximos 7 dias.
   - Quitação rápida com 1 clique ("Marcar como paga").
   - Consulta avançada com filtros por situação (todas, pagas, pendentes, vencidas) e busca (`/modulos/contas-a-pagar/avancado`).

---

## Como Executar Localmente

### Método 1: Inicialização Rápida (Recomendado no Windows)
Basta dar dois cliques no arquivo:
```cmd
iniciar.bat
```
Ele verifica o status do serviço MySQL, abre o navegador padrão em `http://127.0.0.1:8000` e inicia o servidor local.

### Método 2: Execução Manual por Linha de Comando
Instale as dependências caso ainda não o tenha feito:
```powershell
composer install
npm install
npm run build
```

Inicie o servidor do Laravel:
```powershell
php artisan serve --host=127.0.0.1 --port=8000
```
Acesse `http://127.0.0.1:8000` no seu navegador.

---

## Backup e Restauração do Banco de Dados

> [!IMPORTANT]
> Copiar apenas os arquivos da aplicação **NÃO** preserva os dados cadastrados. Os dados residem no banco de dados MySQL `site_gerencial` e exigem dump para backup efetivo.

### Gerar Backup
Dê dois cliques no script:
```cmd
backup.bat
```
O script gera um arquivo SQL em `backups/site_gerencial_AAAA-MM-DD_HHMM.sql` utilizando credenciais salvas de forma segura fora do repositório (`%USERPROFILE%\.site_gerencial_mysql.cnf`).

### Restaurar Backup
Para restaurar um backup existente em caso de necessidade:
```cmd
mysql --defaults-extra-file="%USERPROFILE%\.site_gerencial_mysql.cnf" site_gerencial < backups\site_gerencial_AAAA-MM-DD_HHMM.sql
```

---

## Execução em Produção Local
Para rodar em modo produção local de alto desempenho:
1. Ajuste no `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```
2. Compile os assets e gere o cache do Laravel:
   ```powershell
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Caso realize alterações no `.env` posteriormente, limpe os caches com:
   ```powershell
   php artisan config:clear
   ```

---

## Testes Automatizados

A aplicação conta com suíte completa de testes de funcionalidades e regras de negócio via Pest:

```powershell
php artisan test --compact
```

---

## Pontos de Personalização (White Label)

O sistema foi desenvolvido como produto white label. Para personalizar para a empresa final, consulte os pontos mapeados abaixo:

1. **Subtítulo e Iniciais do Cabeçalho**: `resources/views/components/header.blade.php` (marcador `Empresa` e monograma `SG`).
2. **Sufixo do Título das Páginas**: `resources/views/components/layout.blade.php` (`<title>{{ $title }} | Site Gerencial</title>`).
3. **Crédito do Rodapé**: `resources/views/components/footer.blade.php`.
4. **Paleta de Cores e Tokens Visuais**: `resources/css/app.css` (variáveis CSS dentro de `:root` e `@theme inline`).
5. **Nome da Aplicação**: Variável `APP_NAME` no arquivo `.env`.
