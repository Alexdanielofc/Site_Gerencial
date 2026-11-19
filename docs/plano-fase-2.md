# Plano Detalhado de Implementação — Fase 2 (Site Gerencial)

## 1. Status Inicial do Projeto
- **`git status`**:
  ```
  On branch main
  Your branch is up to date with 'origin/main'.
  Untracked files:
    "Site Gerencial — Prompt de correções e melhorias (fase 2).md"
  nothing added to commit but untracked files present
  ```
- **`php artisan test --compact`**:
  `{"tool":"pest","result":"passed","tests":123,"passed":123,"assertions":328,"duration_ms":22472}` — **123 testes passando**.
- **`npm run build`**:
  `built in 3.46s` — **Build concluído com sucesso** (`app-BAIPI66F.css`, `app-gTA-prRp.js`).

---

## 2. Diagnóstico e Planejamento por Item (Itens 1 a 11)

### Item 1 — Modo noturno (corrigir)
- **(a) Diagnóstico confirmado**:
  - Em `resources/css/app.css` (linhas 135-171), o bloco `html[data-theme='dark']` define `--branco: #111a2e;`. O token `text-branco` usado em botões primários sobre `bg-blu6` (`#2563eb`), em pílulas selecionadas e no logo `SG` acaba herdando `#111a2e` no dark mode, derrubando o contraste para ~3,4:1 (falha WCAG AA).
  - Em `resources/views/components/header.blade.php` e `resources/views/components/footer.blade.php`, os elementos utilizam `text-line` e `border-ink-soft`. No dark mode, `--line` é `#26344f` sobre `--chrome` (`#070d19`), contraste de ~1,6:1 (quase ilegível).
- **(b) Solução proposta**:
  - No `app.css`, garantir que `--branco` seja sempre `#ffffff` (nunca redefinido em `html[data-theme='dark']`).
  - Remover do bloco `html[data-theme='dark']` os overrides legados (`--cin8`, `--cin6`, `--cin3`, `--cin2`, `--cin1`, `--branco`), garantindo que o tema dependa exclusivamente dos tokens semânticos.
  - Atualizar `body` para usar `color: var(--ink);` e referências internas.
  - Criar os tokens `--chrome-ink: #d1d5db` (fixo nos 2 temas) e `--chrome-line` (claro `#4b5563`, escuro `#26344f`), expor em `@theme inline` (`--color-chrome-ink`, `--color-chrome-line`).
  - Atualizar `header.blade.php` e `footer.blade.php` para usar `text-chrome-ink` e `border-chrome-line`.
- **(c) Arquivos criados/alterados**:
  - `resources/css/app.css` (alterado)
  - `resources/views/components/header.blade.php` (alterado)
  - `resources/views/components/footer.blade.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Teste Pest em `tests/Feature/ComponentsTest.php` lendo `resources/css/app.css` e assegurando que `html[data-theme='dark']` não contenha redefinição de `--branco`.
- **(f) Verificação manual**:
  - Alternar tema claro/escuro pelo botão no header; conferir contraste no header, footer, logo SG, botões primários (`bg-blu6` com texto branco nítido) e cards.
- **(g) Riscos e dúvidas**: Nenhum risco funcional; puramente CSS e Blade.

---

### Item 2 — Validação das expectativas do mês
- **(a) Diagnóstico confirmado**:
  - Em `SalesExpectationController.php` (métodos `edit` e `update`), o cálculo de meses permitidos está duplicado (linhas 22-26 e 79-84).
  - Em `update()`, valores como `"abc"` retornam `null` de `Money::parseToDecimal()`, virando `"0.00"` silenciosamente com flash de sucesso.
  - Arrays aninhados (`expectations[data][]`) ou valores gigantes podem causar estouro de campo DECIMAL ou TypeError.
  - Chaves fora do mês corrente são ignoradas com `continue` sem emitir erro de validação.
  - Não há cobertura de testes para essas falhas.
- **(b) Solução proposta**:
  - Criar `app/Http/Requests/UpdateSalesExpectationsRequest.php`.
  - Mover o cálculo da janela de 3 meses (`allowedMonths()`) para método reutilizável (pode residir no Request ou Model/Support).
  - Em `prepareForValidation`: normalizar cada valor com `Money::parseToDecimal`, mantendo valor bruto se inválido para `old()` e regras de validação; strings vazias viram `'0.00'`.
  - Regras: `expectations` obrigatório, array; cada chave validada com formato estrito `!Y-m-d` pertencente ao mês da requisição (se houver chave de outro mês, rejeitar); cada valor string/numérico entre 0 e 9.999.999.999,99 com até 2 casas decimais.
  - Execução atômica no controller com `DB::transaction` e `upsert`.
  - Mensagens personalizadas em `lang/pt_BR/validation.php`.
  - View `expectations.blade.php` preparada para receber `$errors`, destacar campo com borda `red6` e `aria-describedby`, priorizando `old('expectations.'.$key)`.
  - Nas rotas de edição de venda (`SalesController`), usar `Carbon::createFromFormat('!Y-m-d', ...)` com `abort(404)` se inválido.
- **(c) Arquivos criados/alterados**:
  - `app/Http/Requests/UpdateSalesExpectationsRequest.php` (criado)
  - `app/Http/Controllers/SalesExpectationController.php` (alterado)
  - `app/Http/Controllers/SalesController.php` (alterado)
  - `resources/views/modules/sales/expectations.blade.php` (alterado)
  - `lang/pt_BR/validation.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Em `tests/Feature/SalesTest.php`: payload com texto inválido, números negativos, acima do teto decimal, array injetado, chave fora do mês, mês fora da janela, integridade transacional (dia inválido não salva os válidos), manutenção de valores via `old()`.
- **(f) Verificação manual**:
  - Acessar `/modulos/vendas/expectativas`, digitar "abc" em um dia e tentar salvar; conferir mensagem em português e persistência do valor no input.
- **(g) Riscos e dúvidas**: Nenhum.

---

### Item 3 — Duplo clique e feedback de envio
- **(a) Diagnóstico confirmado**:
  - Hoje submissões rápidas consecutivas em Notas e Contas podem gerar duplicidade de registros sem `invoice_number`.
  - Não há tratamento de `submitting` no front nem prevenção de idempotência no backend.
  - Botões não possuem `motion-reduce` nem estilização completa de `disabled`.
- **(b) Solução proposta**:
  - **Front (`resources/js/app.js`)**: Listener global de `submit` em formulários POST/PUT/DELETE. Prevenir reenvio se `data-submitting` estiver ativo; desabilitar botões no tick seguinte (`setTimeout`), injetar spinner (`x-icons.spinner`) com texto de `data-loading-text` (`Salvando...`, `Buscando...`, etc.) e `aria-busy="true"`. Tratar evento `pageshow` com `persisted` para reabilitar se voltar pelo histórico.
  - **Back (`App\Support\SubmissionGuard`)**: Classe com método `firstTime(Request $request): bool`, gravando token `submission:{$id}` no `Cache::add(..., true, 600)`.
  - Campo oculto `_submission_id` injetado nos formulários de criação (`old('_submission_id', Str::uuid()->toString())`).
  - Nos métodos `store()` (Sales, Invoices, Payables), após validação, checar `SubmissionGuard::firstTime()`. Se falso, redirecionar com aviso flash `warning` ('Este registro já foi enviado.'). Se requisição vier sem token (ex.: testes legados), prossegue normalmente.
  - Componente `x-button`, `x-pill` e `x-theme-toggle`: aplicar `motion-reduce:transition-none motion-reduce:transform-none`, e em `x-button` adicionar classes `disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none`.
  - Criar `resources/views/components/icons/spinner.blade.php`.
- **(c) Arquivos criados/alterados**:
  - `resources/js/app.js` (alterado)
  - `app/Support/SubmissionGuard.php` (criado)
  - `resources/views/components/icons/spinner.blade.php` (criado)
  - `resources/views/components/button.blade.php` (alterado)
  - `resources/views/components/pill.blade.php` (alterado)
  - `resources/views/components/theme-toggle.blade.php` (alterado)
  - `resources/views/modules/sales/index.blade.php` (alterado)
  - `resources/views/modules/invoices/index.blade.php` (alterado)
  - `resources/views/modules/invoices/create.blade.php` (alterado)
  - `resources/views/modules/payables/index.blade.php` (alterado)
  - `resources/views/modules/payables/create.blade.php` (alterado)
  - `app/Http/Controllers/SalesController.php` (alterado)
  - `app/Http/Controllers/InvoiceController.php` (alterado)
  - `app/Http/Controllers/PayableController.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Testes em `InvoicesTest`, `PayablesTest` e `SalesTest` enviando o mesmo `_submission_id` duas vezes e confirmando apenas 1 registro criado; envio com tokens distintos criando 2; falha de validação não consumindo token.
- **(f) Verificação manual**:
  - Clicar rapidamente duas vezes no botão de salvar e observar o spinner com texto "Salvando..." e bloqueio do botão.
- **(g) Riscos e dúvidas**: Nenhum; compatibilidade mantida com testes sem token.

---

### Item 4 — Voltar ao contexto depois de editar
- **(a) Diagnóstico confirmado**:
  - Em `InvoiceController::edit/update` e `PayableController::edit/update`, o redirecionamento pós-update retorna fixamente para o mês atual, perdendo `ano`, `mes`, `busca`, `situacao` e `page`.
- **(b) Solução proposta**:
  - Links de edição passam query parameters (`origem`, `ano`, `mes`, `busca`, `situacao`, `page`).
  - Formulários de edição renderizam inputs ocultos para esses parâmetros.
  - `UpdateInvoiceRequest` e `UpdatePayableRequest` validam o contexto (`ano` entre 2000 e 2100, `mes` 1-12, `busca` até 100 caracteres, `situacao` whitelist, `page` inteiro >= 1, `origem` em `['index', 'advanced']`). Parâmetros inválidos são descartados com fallback seguro.
  - O redirecionamento pós-update usa rotas nomeadas com parâmetros validados (sem open redirect).
  - Criações originadas de `/nova` redirecionam para a visão avançada do mês do registro criado.
- **(c) Arquivos criados/alterados**:
  - `app/Http/Requests/UpdateInvoiceRequest.php` (alterado)
  - `app/Http/Requests/UpdatePayableRequest.php` (alterado)
  - `app/Http/Controllers/InvoiceController.php` (alterado)
  - `app/Http/Controllers/PayableController.php` (alterado)
  - `resources/views/modules/invoices/advanced.blade.php` (alterado)
  - `resources/views/modules/invoices/edit.blade.php` (alterado)
  - `resources/views/modules/payables/advanced.blade.php` (alterado)
  - `resources/views/modules/payables/edit.blade.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Em `InvoicesTest` e `PayablesTest`: testar fluxo de edição mantendo query strings validadas; testar valores corrompidos caindo nos fallbacks.
- **(f) Verificação manual**:
  - Filtrar contas vencidas de um mês anterior na página 2, editar uma conta e salvar; verificar se retorna exatamente para a mesma página com filtros ativos.
- **(g) Riscos e dúvidas**: Nenhum.

---

### Item 5 — Extrair a lógica das pílulas de período
- **(a) Diagnóstico confirmado**:
  - O agrupamento e ordenação de anos e meses a partir de `periodsWithRecords()` está duplicado em `DashboardController`, `SalesController::advanced`, `InvoiceController::advanced` e `PayableController::advanced`.
  - A marcação Blade `<nav aria-label="Selecionar período">` também se repete nas views.
- **(b) Solução proposta**:
  - Criar `app/Support/PeriodPills.php` com o método `build(Collection $recordPeriods, Carbon $selected, Carbon $today): array`.
  - Criar componente Blade `resources/views/components/period-pills.blade.php` recebendo `years`, `months`, `selected-year`, `selected-month`, `route` e `params` opcionais.
  - Refatorar os 4 controllers e as respectivas views para consumir `PeriodPills` e `<x-period-pills>`.
- **(c) Arquivos criados/alterados**:
  - `app/Support/PeriodPills.php` (criado)
  - `resources/views/components/period-pills.blade.php` (criado)
  - `app/Http/Controllers/DashboardController.php` (alterado)
  - `app/Http/Controllers/SalesController.php` (alterado)
  - `app/Http/Controllers/InvoiceController.php` (alterado)
  - `app/Http/Controllers/PayableController.php` (alterado)
  - `resources/views/index.blade.php` (alterado)
  - `resources/views/modules/sales/advanced.blade.php` (alterado)
  - `resources/views/modules/invoices/advanced.blade.php` (alterado)
  - `resources/views/modules/payables/advanced.blade.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Teste unitário `tests/Unit/PeriodPillsTest.php` cobrindo regras de negócio (ano atual, ano diferente, ausência do mês, rótulo).
  - Teste em `ComponentsTest` para a renderização do `<x-period-pills>`.
- **(f) Verificação manual**:
  - Navegar pelas pílulas no Dashboard e nas áreas avançadas; verificar se a troca de ano/mês permanece idêntica.
- **(g) Riscos e dúvidas**: Nenhum.

---

### Item 6 — Scripts e execução local
- **(a) Diagnóstico confirmado**:
  - `iniciar.bat` abre o browser antes de `php artisan serve` estar pronto e usa `sc query` que apenas verifica existência do serviço, sem checar se está em execução. Não valida `manifest.json` do Vite.
  - `backup.bat` usa `wmic` (depreciado/ausente em builds modernos do Win11), cria `.site_gerencial_mysql.cnf` com usuário `root` e senha vazia se ausente, não rotaciona backups e não remove dumps vazios em falhas.
- **(b) Solução proposta**:
  - `iniciar.bat`:
    1. Checar existência de `.env` e pasta `vendor`.
    2. Validar serviço MySQL com PowerShell (`Get-Service`) garantindo status `Running`. Se parado, tentar iniciar; se falhar, alertar em português e abortar.
    3. Verificar existência de `public/build/manifest.json`; se ausente, alertar para rodar `npm run build`.
    4. Checar se `APP_DEBUG=true` ou `APP_ENV=local` e exibir aviso amigável sem expor dados.
    5. Iniciar `php artisan serve` em processo/janela separada.
    6. Fazer polling via PowerShell até a porta 8000 responder (até 20s) e só então abrir o navegador padrão.
  - `backup.bat`:
    1. Obter timestamp via PowerShell (`Get-Date -Format yyyy-MM-dd_HHmm`).
    2. Localizar `mysqldump` no PATH ou diretórios padrão do MySQL.
    3. Em caso de ausência do `.site_gerencial_mysql.cnf`, criar template seguro com `user=site_gerencial` e `password=COLOQUE_A_SENHA_AQUI`, alertando o usuário e pausando.
    4. Gravar em `backups\site_gerencial_AAAA-MM-DD_HHMM.sql`.
    5. Validar arquivo gerado: tamanho > 0 e presença de "Dump completed". Se falhar, remover arquivo parcial e registrar erro.
    6. Rotacionar pasta mantendo no máximo os 30 backups mais recentes.
    7. Suportar parâmetro `auto` (para rotinas agendadas, gravando em `backups/backup.log` sem comando `pause`).
  - Documentar orientações de restauração no `README.md`.
- **(c) Arquivos criados/alterados**:
  - `iniciar.bat` (alterado)
  - `backup.bat` (alterado)
  - `README.md` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Verificação via comandos de terminal/PowerShell.
- **(f) Verificação manual**:
  - Executar `backup.bat auto` e checar o arquivo gerado na pasta `backups/`, garantindo conteúdo válido.
- **(g) Riscos e dúvidas**: Nenhum; regras claras e seguras para Windows.

---

### Item 7 — Ajustes pequenos
- **(a) Diagnóstico confirmado**:
  - Em `Money::percentOf()`, o cálculo não é explicitamente arredondado para 1 casa decimal antes da avaliação de tom, permitindo discrepâncias visuais como 99.96% aparecer como 100,0% sem atingir o tom verde.
  - Em `DailySale::indicators()`, `centsOn()` e queries do dashboard, há operações como `sum(amount * 100)` ou multiplicações em PHP em vez de conversão inteira no SQL (`CAST(ROUND(amount * 100) AS SIGNED)`).
  - Ocorrências de `\DB` sem import explícito.
  - O `DashboardController` executa atualmente 19 queries na tela inicial.
- **(b) Solução proposta**:
  - Ajustar `Money::percentOf` para arredondar a 1 casa decimal e calibrar a comparação do tom contra o valor arredondado. Atualizar `MoneyTest` registrando a alteração proposital.
  - Padronizar consultas de centavos usando `CAST(ROUND(amount * 100) AS SIGNED)` no SQL.
  - Importar `use Illuminate\Support\Facades\DB;` onde ainda houver `\DB`.
  - Otimizar agregações do `DashboardController`: agrupar somas e contagens de Contas a pagar com `SUM(CASE ...)` e unificar contagem e soma de vencidas, reduzindo de 19 para ~15 queries.
  - Adicionar teste em `DashboardTest` fixando teto máximo de queries.
- **(c) Arquivos criados/alterados**:
  - `app/Support/Money.php` (alterado)
  - `app/Models/DailySale.php` (alterado)
  - `app/Models/SalesExpectation.php` (alterado)
  - `app/Models/Invoice.php` (alterado)
  - `app/Models/Payable.php` (alterado)
  - `app/Http/Controllers/DashboardController.php` (alterado)
  - `tests/Unit/MoneyTest.php` (alterado)
  - `tests/Feature/DashboardTest.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - `MoneyTest` atualizado para nova regra de arredondamento.
  - `DashboardTest` com asserção de limite de queries com `DB::enableQueryLog()`.
- **(f) Verificação manual**:
  - Conferir percentuais e cards no Dashboard.
- **(g) Riscos e dúvidas**: Nenhum.

---

### Item 8 — Status cancelada em notas e contas
- **(a) Diagnóstico confirmado**:
  - Atualmente, não há mecanismo para cancelar notas fiscais ou contas a pagar; exclusões físicas são proibidas pelas regras do projeto.
- **(b) Solução proposta**:
  - Criar migrations adicionando `canceled_at` (TIMESTAMP nullable indexado) e `cancel_reason` (string(255) nullable) nas tabelas `invoices` e `payables`.
  - Criar local scope `active()` em `Invoice` e `Payable` (`whereNull('canceled_at')`) e aplicar em todas as agregações, contagens, ticket médio e listas de atenção/vencimentos.
  - Em `Payable::status()`, a condição de cancelada tem precedência sobre paga, vencida e pendente.
  - Regra: conta paga não pode ser cancelada; conta cancelada não pode ser paga nem editada.
  - Reativação limpa `canceled_at` e `cancel_reason`.
  - Criar Form Requests `CancelInvoiceRequest` e `CancelPayableRequest` (motivo opcional até 255 caracteres).
  - Rotas:
    - POST `/modulos/notas-fiscais/{invoice}/cancelar` (`invoices.cancel`)
    - POST `/modulos/notas-fiscais/{invoice}/reativar` (`invoices.reactivate`)
    - POST `/modulos/contas-a-pagar/{payable}/cancelar` (`payables.cancel`)
    - POST `/modulos/contas-a-pagar/{payable}/reativar` (`payables.reactivate`)
  - Interface: linhas canceladas ficam esmaecidas com valor tachado (`line-through`) e badge 'Cancelada' em cor neutra; botões 'Cancelar' com confirmação via `data-confirm` tratado em `app.js` (`window.confirm`); botão 'Reativar'.
  - Filtro nas telas avançadas para exibir 'Canceladas'.
- **(c) Arquivos criados/alterados**:
  - `database/migrations/2026_11_18_190000_add_canceled_columns_to_invoices_table.php` (criado)
  - `database/migrations/2026_11_18_190001_add_canceled_columns_to_payables_table.php` (criado)
  - `app/Models/Invoice.php` (alterado)
  - `app/Models/Payable.php` (alterado)
  - `app/Http/Requests/CancelInvoiceRequest.php` (criado)
  - `app/Http/Requests/CancelPayableRequest.php` (criado)
  - `app/Http/Controllers/InvoiceController.php` (alterado)
  - `app/Http/Controllers/PayableController.php` (alterado)
  - `routes/web.php` (alterado)
  - `resources/views/modules/invoices/index.blade.php` (alterado)
  - `resources/views/modules/invoices/advanced.blade.php` (alterado)
  - `resources/views/modules/invoices/edit.blade.php` (alterado)
  - `resources/views/modules/payables/index.blade.php` (alterado)
  - `resources/views/modules/payables/advanced.blade.php` (alterado)
  - `resources/views/modules/payables/edit.blade.php` (alterado)
  - `resources/views/components/badge.blade.php` (alterado)
  - `resources/js/app.js` (alterado)
- **(d) Migrations**:
  - `2026_11_18_190000_add_canceled_columns_to_invoices_table.php`:
    - `canceled_at`: timestamp nullable, índice
    - `cancel_reason`: string(255) nullable
  - `2026_11_18_190001_add_canceled_columns_to_payables_table.php`:
    - `canceled_at`: timestamp nullable, índice
    - `cancel_reason`: string(255) nullable
- **(e) Testes**:
  - Testes abrangentes em `InvoicesTest` e `PayablesTest`: canceladas excluídas de totais/indicadores/atenção, exibidas nas listagens com estilo apropriado, reativação restaurando totais, proibição de pagar cancelada e de cancelar paga, bloqueio de edição em cancelada.
- **(f) Verificação manual**:
  - Cancelar uma nota fiscal e uma conta a pagar; confirmar que somas e contagens diminuem de imediato e o registro aparece com badge Cancelada. Reativar e verificar retorno dos valores.
- **(g) Riscos e dúvidas**: Antes de rodar `php artisan migrate` em ambiente real, rodar `backup.bat` conforme estipulado.

---

### Item 9 — Backup automático e aviso no dashboard
- **(a) Diagnóstico confirmado**:
  - Não há rotina agendada no Windows para backups automáticos e o Dashboard não apresenta indicador do status do último backup.
- **(b) Solução proposta**:
  - Criar `agendar-backup.bat` configurando a tarefa agendada `SiteGerencial-Backup` via PowerShell `Register-ScheduledTask` para rodar diariamente às 20:00 com `backup.bat auto` e `StartWhenAvailable`.
  - Criar `remover-agendamento.bat` com `Unregister-ScheduledTask`.
  - Criar `config/backup.php` com o caminho padrão dos backups (`base_path('backups')`, customizável via `BACKUP_PATH`).
  - Criar `app/Support/BackupStatus.php` com o método `latest(): ?Carbon`, inspecionando o arquivo `site_gerencial_*.sql` mais recente por data de modificação.
  - No Dashboard (`index.blade.php`), exibir banner discreto via `<x-alert>`:
    - Até 2 dias: `info` ('Último backup: hoje' / 'há N dia(s)')
    - 3 a 7 dias: `warning`
    - > 7 dias ou ausente: `danger` ('Nenhum backup recente. Execute backup.bat ou agende o backup automático.')
- **(c) Arquivos criados/alterados**:
  - `agendar-backup.bat` (criado)
  - `remover-agendamento.bat` (criado)
  - `config/backup.php` (criado)
  - `app/Support/BackupStatus.php` (criado)
  - `app/Http/Controllers/DashboardController.php` (alterado)
  - `resources/views/index.blade.php` (alterado)
  - `README.md` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - `tests/Feature/BackupStatusTest.php`: testes com diretório temporário simulando arquivos de 0 dias, 5 dias, 10 dias e pasta inexistente.
- **(f) Verificação manual**:
  - Executar `backup.bat auto` e carregar o Dashboard; verificar o banner informando backup recente.
- **(g) Riscos e dúvidas**: Nenhum.

---

### Item 10 — Dados de demonstração
- **(a) Diagnóstico confirmado**:
  - O comando `php artisan demo:seed` ainda não existe.
  - **Verificação técnica de `--env=demo`**: Confirmamos que o Laravel suporta nativamente a flag `--env=demo`, que carrega automaticamente o arquivo `.env.demo` e seta a conexão de banco para `site_gerencial_demo`!
- **(b) Solução proposta**:
  - Criar o comando `app/Console/Commands/DemoSeedCommand.php` (`demo:seed`).
  - Trava estrita de segurança: inspecionar o nome da base conectada (`config('database.connections.mysql.database')` ou PDO). Se o nome do banco não terminar com `_demo`, abortar imediatamente com erro e código de saída diferente de 0.
  - Limpar apenas as 4 tabelas de negócio (`daily_sales`, `sales_expectations`, `invoices`, `payables`) e solicitar confirmação (`--yes` para ignorar interação).
  - Criar `database/seeders/DemoSeeder.php` determinístico (semente fixa) gerando dados fictícios genéricos (sem dados reais nem menção a ramos específicos) para 3 meses anteriores + mês atual + expectativas do próximo:
    - Vendas de segunda a sábado com HOJE em branco.
    - Expectativas diárias (domingos zerados).
    - 12 a 25 notas/mês com clientes fictícios e ao menos 1 cancelada.
    - Contas a pagar recorrentes com mix de pagas, pendentes, vencidas e cancelada.
  - Criar `.env.demo.example` versionado com `DB_DATABASE=site_gerencial_demo` e `APP_ENV=demo`.
  - Criar `iniciar-demo.bat` iniciando o servidor com `php artisan serve --port=8001 --env=demo`.
- **(c) Arquivos criados/alterados**:
  - `app/Console/Commands/DemoSeedCommand.php` (criado)
  - `database/seeders/DemoSeeder.php` (criado)
  - `.env.demo.example` (criado)
  - `iniciar-demo.bat` (criado)
  - `README.md` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - `tests/Feature/DemoSeedCommandTest.php`: assegurar que o comando é recusado no banco de testes `site_gerencial_test`; testar execução do `DemoSeeder` e regras de integridade.
- **(f) Verificação manual**:
  - Executar `php artisan demo:seed` no terminal padrão e conferir a rejeição de segurança.
- **(g) Riscos e dúvidas**: Nenhum risco; mecanismo de segurança rigoroso.

---

### Item 11 — Componente x-flash
- **(a) Diagnóstico confirmado**:
  - Blocos de mensagens (`session('success')`, `session('error')`, `session('warning')`, e listagem de `$errors`) estão duplicados em 7+ views do sistema.
- **(b) Solução proposta**:
  - Criar `resources/views/components/flash.blade.php` (`x-flash`).
  - Suportar prop `except-errors` (booleano).
  - Tratar `session('success')`, `session('error')`, `session('warning')`, `session('info')` e renderizar lista de `$errors` com título "Verifique os campos" quando `exceptErrors` for falso.
  - Escapar todos os textos contra XSS.
  - Substituir os blocos repetidos em todas as views por `<x-flash />`.
- **(c) Arquivos criados/alterados**:
  - `resources/views/components/flash.blade.php` (criado)
  - `resources/views/modules/sales/index.blade.php` (alterado)
  - `resources/views/modules/sales/edit.blade.php` (alterado)
  - `resources/views/modules/sales/expectations.blade.php` (alterado)
  - `resources/views/modules/invoices/index.blade.php` (alterado)
  - `resources/views/modules/invoices/advanced.blade.php` (alterado)
  - `resources/views/modules/invoices/edit.blade.php` (alterado)
  - `resources/views/modules/invoices/create.blade.php` (alterado)
  - `resources/views/modules/payables/index.blade.php` (alterado)
  - `resources/views/modules/payables/advanced.blade.php` (alterado)
  - `resources/views/modules/payables/edit.blade.php` (alterado)
  - `resources/views/modules/payables/create.blade.php` (alterado)
- **(d) Migrations**: Nenhuma.
- **(e) Testes**:
  - Testes em `ComponentsTest` validando renderização de cada tipo de alerta via flash session, ausência de alertas vazios, escape de HTML e lista de erros de validação.
- **(f) Verificação manual**:
  - Disparar mensagens de sucesso/erro nos formulários e inspecionar visualmente.
- **(g) Riscos e dúvidas**: Nenhum.

---

## 3. Ordem de Execução Proposta

Seguiremos exatamente a ordem recomendada na Seção 14 do documento de especificações:
1. **Item 1 (Tema escuro)**: Correção fundamental de tokens de cores e contrastes.
2. **Item 11 (x-flash)**: Padronização das mensagens em todas as views antes de alterar fluxos de formulários.
3. **Item 2 (Expectativas)**: Form Request, validação estrita e atomismo transacional.
4. **Item 3 (Duplo clique e feedback)**: Token de idempotência e feedback de loading nos botões.
5. **Item 5 (Pílulas de período)**: Extração da lógica de períodos para `PeriodPills` e componente Blade.
6. **Item 4 (Contexto de edição)**: Preservação de parâmetros e query strings pós-edição em Notas e Contas.
7. **Item 8 (Status cancelada)**: Migrations novas de cancelamento, scopes `active()`, regras de negócio e rotas.
8. **Item 7 (Ajustes pequenos)**: Arredondamento de percentual em `Money`, centavos em SQL e redução de queries no Dashboard.
9. **Item 6 (Scripts locais)**: Aprimoramento robusto de `iniciar.bat` e `backup.bat`.
10. **Item 9 (Backup automático e aviso)**: Scripts de agendamento e alerta de integridade no Dashboard.
11. **Item 10 (Demonstração)**: Comando `demo:seed`, `DemoSeeder`, `.env.demo.example` e `iniciar-demo.bat`.
12. **Fechamento e Documentação**: Atualização de `README.md`, `MVP.MD` e documentação de personalização.

---

## 4. Análise de Ambigüidades e Resoluções Técnicas
1. **Carregamento de `.env.demo`**:
   - *Verificação concluída*: Testamos com sucesso o comportamento nativo do Laravel 13. Ao passar `--env=demo`, o Laravel localiza e carrega `.env.demo` perfeitamente, ajustando `APP_ENV=demo` e a base de dados para `site_gerencial_demo`.
2. **Contas a pagar pagas x canceladas**:
   - Conforme especificação, contas já pagas não podem ser canceladas diretamente; o usuário deve primeiro editar e remover o `paid_at`. De igual modo, contas canceladas não podem receber pagamento a menos que sejam reativadas.
3. **Segurança de Migrations**:
   - Conforme regra estrita do projeto, antes de rodar qualquer `php artisan migrate`, o script `backup.bat` será executado e validado.
