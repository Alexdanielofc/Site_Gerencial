# Site Gerencial — Prompt de correções e melhorias (fase 2)

*Para o agente de implementação. Leia tudo antes de agir. NÃO altere nenhum arquivo antes de o plano (seção 2) ser aprovado. Atualizado em 18/11/2026.*

## 1. Contexto e regras (valem para tudo)

- Projeto Laravel 13, Blade, Tailwind 4, Pest, MySQL. A especificação anterior (`MVP.MD`) continua valendo; este documento a complementa e, em conflito, prevalece.
- Estado atual: 123 testes passando e `npm run build` ok. Eles devem continuar verdes. Só altere uma asserção quando o comportamento mudar DE PROPÓSITO (ex.: arredondamento do percentual) e registre o motivo. Nunca apague teste para passar.
- Banco real `site_gerencial`: NUNCA rodar `migrate:fresh`, `migrate:rollback`, `migrate:reset`, `db:wipe`, `TRUNCATE`, `DROP`, nem seed fora das regras do Item 10. Só migrations NOVAS; nunca editar as já executadas. Antes de rodar `php artisan migrate` no banco real, execute `backup.bat` e confirme que o arquivo gerado existe e não está vazio.
- Testes usam `site_gerencial_test` com `RefreshDatabase`. Manter o teste-guarda `TestDatabaseTest`.
- Não ler, exibir nem commitar o `.env`. NÃO fazer commit nem push (o usuário revisa pelo projeto compactado). Ao final, liste todos os arquivos criados e alterados.
- Sem dependências novas (composer ou npm). Sem Livewire, React ou Vue. JavaScript só o descrito aqui, em `resources/js/app.js`, sem biblioteca.
- White label: nenhum nome de empresa nem do ramo de negócio (ex.: papelaria) em código, views, testes, seeds, dados de demonstração ou README. Código em inglês; interface em português do Brasil; cores só pelos tokens do `:root` (sem hex solto nem paleta padrão do Tailwind).
- Sem exclusão de registros. A única forma de tirar um lançamento dos totais é o status cancelada (Item 8).
- Leia o arquivo real antes de editar. Não presuma.

## 2. FASE 0 — Plano para aprovação (sem alterar nada)

1. Rode `git status`, `php artisan test --compact` e `npm run build` e informe os resultados.
2. Para CADA item (1 a 11) responda: (a) diagnóstico confirmado, com arquivo e trecho; (b) solução proposta; (c) arquivos que serão criados ou alterados; (d) migrations (nome, colunas, tipos, índices); (e) testes que serão adicionados; (f) como verificar manualmente; (g) riscos e dúvidas.
3. Informe a ORDEM de execução (sugestão na seção 14) e justifique qualquer mudança.
4. Liste discordâncias e ambiguidades deste documento. Não resolva sozinho: pergunte.
5. PARE e aguarde a minha resposta 'aprovado' (ou pedidos de ajuste). O plano será revisado antes da aprovação. Nesta fase não crie, edite nem apague arquivos.
6. Depois da aprovação, execute na ordem aprovada, um item por vez. Ao fim de cada item rode `php artisan test --compact` e `npm run build`, resuma o que mudou e siga. Se um teste falhar ou algo contradizer este documento, pare e pergunte.

## 3. Item 1 — Modo noturno (corrigir)

Defeitos (em `resources/css/app.css`, `header.blade.php` e `footer.blade.php`):

- Em `html[data-theme='dark']`, `--branco` virou `#111a2e`. O `text-branco` é usado sobre fundo azul (botão primário, pílula selecionada, logo 'SG') e fica escuro (cerca de 3,4:1).
- Header e footer usam `text-line`; no escuro `--line` é `#26344f` sobre `--chrome` `#070d19` (cerca de 1,6:1): texto e navegação quase invisíveis.

Correção:

- `--branco` NUNCA muda: `#ffffff` nos dois temas. Remover do bloco escuro os overrides dos tokens antigos (`--cin8 --cin6 --cin3 --cin2 --cin1 --branco`) depois de confirmar por grep que nenhuma view os usa; trocar `body { color: var(--cin8) }` e usos internos por `var(--ink)`.
- Criar `--chrome-ink` (texto do header e do footer; `#d1d5db` nos dois temas) e `--chrome-line` (bordas deles; claro `#4b5563`, escuro `#26344f`), expor em `@theme inline` e usar `text-chrome-ink` e `border-chrome-line` no header e no footer, no lugar de `text-line` e `border-ink-soft`. O hover dos links continua `blu3`.
- Conferir contraste WCAG AA (4,5:1 para texto; 3:1 para ícones e bordas de interface) nos DOIS temas: header, footer e navegação; botão primário (branco sobre `#2563eb` = 5,17:1); pílulas selecionada e não selecionada; logo SG; cada tipo de alerta; badges de situação; campos, placeholders e foco; tabelas; textos secundários. Se um par falhar, ajuste o token (não a classe) e registre os valores no README.
- Teste de proteção: um teste Pest que lê `resources/css/app.css` e falha se o bloco `html[data-theme='dark']` redefinir `--branco`.

## 4. Item 2 — Validação das expectativas do mês

Defeitos em `SalesExpectationController::update`: valor inválido (ex.: abc) vira 0,00 e mostra sucesso; valor enorme estoura o DECIMAL (erro 500); `expectations[data][]` gera TypeError (500); chave fora do mês é ignorada em silêncio; não há testes.

Correção:

- Criar `UpdateSalesExpectationsRequest` (Form Request). Extrair a janela anterior, atual e seguinte (hoje duplicada em `edit` e `update`) para um único método; fora da janela = redirecionar ao mês atual com aviso.
- `prepareForValidation`: para cada item, se for texto, normalizar com `Money::parseToDecimal`, mantendo o texto original quando não converter (para o erro e o `old()`); vazio vira '0.00'.
- Regras: `expectations` obrigatório e array; cada chave é uma data válida (`!Y-m-d`) DENTRO do mês exibido (chave fora do mês = erro, não ignorar); cada valor é string (array = erro), numérico, mínimo 0, máximo 9999999999.99, até 2 casas.
- Tudo ou nada: se qualquer dia falhar, NADA é salvo. Salvar com `upsert` dentro de `DB::transaction`.
- Erros por dia, em português, em `lang/pt_BR/validation.php` (mensagens e `attributes`), ex.: 'Expectativa de 05/11/2026: informe um valor válido.'. Voltar com `withErrors` e `withInput`. A view mostra `old('expectations.AAAA-MM-DD')` com prioridade sobre o valor salvo, destaca o campo com erro (borda `red6`, mensagem ligada por `aria-describedby`) e lista os erros via `x-flash`.
- Datas vindas da URL (editar venda, atualizar venda, expectativas): usar `Carbon::createFromFormat('!Y-m-d', ...)` (o `!` zera a hora); data inválida = 404.
- Testes: texto inválido; negativo; acima do máximo; array injetado; chave fora do mês; mês fora da janela; um dia inválido entre válidos não salva nenhum; vazio vira 0,00; formatos '1.234,56', '1234,56' e '1234.5'; o campo mantém o digitado após o erro; nenhuma resposta 500.

## 5. Item 3 — Duplo clique e feedback de envio

Defeitos: notas fiscais (sem número) e contas a pagar não têm proteção: dois cliques criam dois registros. Não há feedback de envio. Botões sem `motion-reduce`.

Correção em três camadas:

**a) Front** (`resources/js/app.js`, sem biblioteca). Em todo formulário que não seja GET, no evento `submit`: se já estiver enviando, `preventDefault`; senão marcar `data-submitting` e, no próximo tick (`setTimeout` 0, para não perder o envio), desabilitar os botões de submit, trocar o rótulo por spinner mais o texto de `data-loading-text` (padrão 'Aguarde...'; 'Salvando...' nos formulários de gravação; 'Buscando...' nos filtros) e marcar `aria-busy`. No `pageshow` com `event.persisted` (voltar do navegador), reabilitar. Não pode interferir em links, na validação do servidor nem no tema. Criar `x-icons.spinner` no padrão dos outros ícones, com `animate-spin`.

**b) Back (idempotência).** Campo oculto `_submission_id` (UUID gerado na view e mantido com `old('_submission_id', ...)` para não mudar após erro de validação) em TODOS os formulários de criação (vendas, notas, contas). Criar `App\Support\SubmissionGuard` com `firstTime(Request $request): bool`, usando `Cache::add('submission:'.$id, true, 600)`. Nos `store` de notas, contas e vendas, DEPOIS da validação e antes de criar: se não for a primeira vez, não criar e redirecionar à tela de origem com o aviso 'Este registro já foi enviado.' (flash `warning`). Sem token no pedido o fluxo segue normal (compatível com os testes atuais). O token só é consumido quando a validação passa.

**c) Botões e pílulas.** `motion-reduce:transition-none` e `motion-reduce:transform-none` em `x-button`, `x-pill` e `x-theme-toggle`. Estado `disabled` do `x-button`: `disabled:opacity-60`, `disabled:cursor-not-allowed`, sem escala nem sombra no hover.

Testes: o mesmo `_submission_id` enviado duas vezes cria 1 registro (notas, contas, vendas); tokens diferentes criam 2; pedido inválido não consome o token (reenvio válido com o mesmo token cria); componente `x-button` disabled e motion-reduce. Verificação manual do JS: duplo clique rápido em Salvar; voltar do navegador reabilita o botão.

## 6. Item 4 — Voltar ao contexto depois de editar

Defeito: editar nota ou conta volta ao mês ATUAL e perde busca, filtro e página (Vendas já preserva `ano` e `mes`).

Correção (notas e contas):

- Os links EDITAR levam `origem` e o contexto (`ano`, `mes`, `busca`, `situacao`, `page`). O formulário de edição repete esses valores em campos ocultos.
- Validar o contexto no Form Request: `ano` 2000 a 2100, `mes` 1 a 12, `busca` string até 100, `situacao` dentro de uma lista permitida, `page` inteiro maior ou igual a 1, `origem` em \[index, advanced\]. Valor inválido é descartado (nunca erro 500).
- `update` redireciona para a rota nomeada (`invoices.advanced`, `payables.advanced` ou `index`) com o contexto validado. PROIBIDO redirecionar para URL vinda do usuário (open redirect).
- Criação feita a partir de `create` (qualquer data): depois de salvar, ir à área avançada do mês do registro criado, com mensagem de sucesso.
- `payables.pay` continua voltando à página anterior mantendo a query string.
- Testes: editar nota de mês passado com busca volta ao mesmo mês e à mesma busca; idem contas com filtro e página; contexto inválido cai no padrão; `origem` desconhecida não quebra.

## 7. Item 5 — Extrair a lógica das pílulas de período

Defeito: a mesma lógica está copiada no `DashboardController` e nos controllers avançados de Vendas, Notas e Contas; o `<nav>` Blade também se repete.

Correção sem mudar o comportamento:

- Criar `App\Support\PeriodPills` com `build(Collection $recordPeriods, Carbon $selected, Carbon $today): array`, devolvendo `years` e `months`. Regras (iguais às atuais): lista = períodos com registro UNIÃO o mês atual, sem duplicar; ano já selecionado mantém o mês; ano atual vai ao mês atual; outro ano mantém o mês selecionado se existir nele, senão o mais recente; rótulo do mês por extenso com inicial maiúscula.
- Criar `x-period-pills` (props: years, months, selected-year, selected-month, route, params) que renderiza as duas linhas de pílulas e preserva parâmetros extras (busca, situacao).
- Usar nos 4 controllers e nas 4 views. Cada Model mantém o seu `periodsWithRecords()`.
- Testes unitários de `PeriodPills` (um por regra) e teste de componente. Os testes existentes continuam verdes sem alteração.

## 8. Item 6 — Scripts e execução local

Defeitos:

- `iniciar.bat` abre o navegador ANTES de o servidor subir; o teste do MySQL só vê se o serviço existe, não se está rodando; não avisa se o front não foi compilado nem se `APP_DEBUG=true`.
- `backup.bat` usa `wmic` (removido em Windows 11 recentes) com fallback dependente de idioma; cria credenciais com `root` e senha vazia (usuário errado); deixa arquivo vazio se falhar; não rotaciona backups.

Correção:

- `iniciar.bat`: (1) confirmar que `.env` e `vendor` existem; (2) localizar o serviço MySQL e checar se está RUNNING (PowerShell `Get-Service`); se parado, tentar iniciar e, se não conseguir (ex.: sem administrador), explicar em português e parar; (3) avisar se `public/build/manifest.json` não existe ('execute npm run build'); (4) avisar, sem mostrar valores, se o `.env` tem `APP_DEBUG=true` ou `APP_ENV=local` (só avisar, nunca editar o `.env`); (5) subir `php artisan serve --host=127.0.0.1 --port=8000` em janela própria; (6) esperar a porta responder (até 20 s) e SÓ ENTÃO abrir o navegador; (7) se não subir, mensagem clara. Mensagens em português; nunca fechar a janela sozinho.
- `backup.bat`: data pelo PowerShell (`Get-Date -Format yyyy-MM-dd_HHmm`); localizar `mysqldump` no PATH ou em `C:\Program Files\MySQL\MySQL Server *\bin`; credenciais em arquivo fora do projeto: se não existir, NÃO criar com root; criar modelo com `user=site_gerencial` e `password=COLOQUE_A_SENHA_AQUI`, orientar e parar; gravar em `backups\site_gerencial_AAAA-MM-DD_HHMM.sql`; validar (maior que 0 bytes e contendo 'Dump completed'); se falhar, apagar o arquivo parcial e avisar; manter só os 30 backups mais recentes; aceitar o argumento `auto` (sem `pause`, registrando em `backups\backup.log`).
- README: passo a passo da restauração e a recomendação de testá-la em um banco de teste uma vez por mês. NÃO criar script que restaure sobre o banco real.

## 9. Item 7 — Ajustes pequenos

- `Money::percentOf`: arredondar para 1 casa; o tom (verde a partir de 100) usa o valor arredondado, para que 99,96% não apareça como '100,0%' em azul. Atualizar o `MoneyTest` de propósito e registrar.
- `DailySale::centsOn` e `SalesExpectation::centsOn` (e telas ou dashboard que fazem `amount * 100` em PHP): obter centavos pelo SQL (`CAST(ROUND(amount * 100) AS SIGNED)`), sem float.
- Usar `Illuminate\Support\Facades\DB` importado em todo o projeto (hoje há `\DB` em alguns Models).
- Dashboard: reduzir consultas. Medir antes e depois (`DB::enableQueryLog`); juntar as agregações de Contas a pagar numa consulta com somas condicionais (`SUM(CASE ...)`) e as vencidas (contagem e soma) numa só. Teste que fixa o limite de consultas do dashboard em (valor medido + pequena margem) e registra o número.
- Nenhuma mudança de comportamento visível.

## 10. Item 8 — Status cancelada em notas e contas

Objetivo: tirar um lançamento errado dos totais sem apagar (histórico preservado).

Banco (migrations NOVAS: `add_canceled_columns_to_invoices_table` e `add_canceled_columns_to_payables_table`): `canceled_at` TIMESTAMP nullable com índice; `cancel_reason` string(255) nullable. Nenhuma coluna existente muda.

Regras:

- R1: cancelada = `canceled_at` preenchido. Fica no banco e nas listas (linha esmaecida, valor riscado, badge 'Cancelada' em cor neutra), mas NUNCA entra em totais, contagens, ticket médio, indicadores, dashboard, 'Em atenção', vencidas e próximos vencimentos. Criar o scope `active()` em `Invoice` e `Payable` e aplicar em TODA agregação e lista de indicador (varrer com grep; nenhuma agregação sem o scope).
- R2: períodos com registro incluem os cancelados (o histórico do mês continua visível).
- R3: na conta, `cancelada` tem prioridade sobre paga, vencida e pendente em `Payable::status()`.
- R4: conta paga não pode ser cancelada (reabrir antes, editando `paid_at`); conta cancelada não pode ser paga (aviso).
- R5: o número de uma nota cancelada continua único (não reutilizar).
- R6: registro cancelado não é editável; a tela de edição mostra `x-alert` explicando e oferece 'Reativar'.
- R7: reativar limpa `canceled_at` e `cancel_reason`; os totais voltam a incluí-lo.

Rotas novas (com `whereNumber`): `invoices.cancel` POST /modulos/notas-fiscais/{invoice}/cancelar; `invoices.reactivate` POST .../reativar; `payables.cancel` e `payables.reactivate` equivalentes. Motivo opcional (até 255). Validação em Form Request.

Interface: botão 'Cancelar' nas listas e na edição (secundário, com ícone), com confirmação por `data-confirm` tratada em `resources/js/app.js` (um único listener de `submit` com `window.confirm`; ex.: 'Cancelar esta nota? Ela deixará de entrar nos totais.'); 'Reativar' nos cancelados. Filtro de situação nas áreas avançadas inclui 'Canceladas' (notas: todas, ativas, canceladas). Mensagens via `x-flash`. Preservar o contexto (Item 4) nos redirecionamentos.

Testes: cancelada não entra em total, contagem, ticket, dashboard, atenção, vencidas e próximos; entra na lista; reativar restaura; status da conta; não pagar cancelada; não cancelar paga; edição bloqueada; número não reutilizável; atributo `data-confirm` presente.

## 11. Item 9 — Backup automático e aviso no dashboard

- `agendar-backup.bat` cria a tarefa 'SiteGerencial-Backup' diária às 20:00 chamando `backup.bat auto`, via PowerShell (`Register-ScheduledTask`) com `StartWhenAvailable` (roda ao ligar o PC se perdeu o horário). `remover-agendamento.bat` remove a tarefa. Avisar quando exigir administrador.
- `config/backup.php` com `path` (padrão `base_path('backups')`, via `BACKUP_PATH` no `.env.example`) e limites de alerta.
- `App\Support\BackupStatus::latest(): ?Carbon` lê o arquivo `site_gerencial_*.sql` mais recente da pasta (data de modificação).
- Dashboard: faixa discreta com `x-alert`. Até 2 dias = info ('Último backup: hoje' ou 'há N dia(s)'); 3 a 7 dias = warning; mais de 7 dias ou nenhum = danger ('Nenhum backup recente. Execute backup.bat ou agende o backup automático.'). Sem erro se a pasta não existir.
- Testes com pasta temporária (config apontando para ela e `touch` com data): nenhum arquivo, 1 dia, 5 dias, 10 dias, pasta inexistente.
- README: como agendar, onde ficam os backups, como verificar o último e a recomendação de copiar a pasta `backups` periodicamente para um pendrive ou para a nuvem (backup só no mesmo computador não protege contra defeito nele).

## 12. Item 10 — Dados de demonstração

Objetivo: mostrar o produto a um cliente novo sem dados reais e entregar uma versão em branco limpa.

Regras de segurança (inegociáveis):

- `php artisan demo:seed` só roda se o NOME DO BANCO terminar em `_demo` (ex.: `site_gerencial_demo`). Em qualquer outro banco, incluindo `site_gerencial` e `site_gerencial_test`, recusa com mensagem clara e código de saída diferente de zero. Não existe `--force` que contorne isso.
- Só apaga dados das 4 tabelas de negócio (`daily_sales`, `sales_expectations`, `invoices`, `payables`) do banco `_demo`, depois de confirmar o nome do banco, e pede confirmação interativa (ou `--yes`).
- Nenhuma automação chama o comando (não entra no `DatabaseSeeder` padrão nem em migrate).

Como funciona: criar o banco `site_gerencial_demo` e dar permissão ao mesmo usuário; arquivo `.env.demo` (com modelo `.env.demo.example` versionado, sem segredos) com `DB_DATABASE=site_gerencial_demo` e `APP_ENV=demo`; `iniciar-demo.bat` define `APP_ENV=demo` e sobe o servidor na porta 8001; comandos `php artisan migrate --env=demo` e `php artisan demo:seed --env=demo`. VERIFIQUE e relate se o carregamento de `.env.demo` por `APP_ENV` e por `--env` funciona neste projeto; se não funcionar, proponha alternativa antes de implementar.

Dados (classe `DemoSeeder`, determinística com semente fixa, datas relativas a `today()`): 3 meses anteriores mais o atual (e expectativas do próximo); vendas de segunda a sábado com valores variados e plausíveis, nenhuma data futura e HOJE sem venda (para a tela mostrar o formulário); expectativas para todos os dias (domingo = 0); 12 a 25 notas por mês com clientes e CNPJs fictícios genéricos ('Cliente Exemplo 01 Ltda'), números sequenciais e pelo menos 1 cancelada; contas genéricas recorrentes (Aluguel, Energia, Água, Internet, Fornecedor A e B) com mistura de pagas, pendentes e vencidas e 1 cancelada. Nenhum nome real nem de ramo de negócio.

Testes: o comando recusa no banco de teste; o `DemoSeeder` chamado direto gera os volumes esperados, sem vendas futuras, com canceladas e respeitando os índices únicos; rodar duas vezes não duplica.

README: seção 'Modo demonstração' com o passo a passo e o aviso de que o banco real nunca é tocado.

## 13. Item 11 — Componente x-flash

Defeito: os blocos de mensagem (success, error, warning e lista de erros) estão copiados em pelo menos 7 telas.

Correção: criar `x-flash` (sem props obrigatórias) que lê `session('success')`, `session('error')`, `session('warning')`, `session('info')` e `$errors` (lista com o título 'Verifique os campos') e renderiza `x-alert` com o tipo e o `role` corretos; não renderiza nada sem mensagens; escapa o conteúdo (nunca HTML cru). Prop `except-errors` para telas que mostram erros junto do campo. Substituir os blocos repetidos em todas as views por `<x-flash />`, mantendo posição e visual. Testes de componente: cada tipo, ausência de mensagem, escape de HTML, erros de validação.

## 14. Ordem sugerida de execução

1. Item 1 (tema escuro). 2. Item 11 (x-flash). 3. Item 2 (expectativas). 4. Item 3 (duplo clique). 5. Item 5 (pílulas). 6. Item 4 (contexto de edição). 7. Item 8 (cancelada, com migration). 8. Item 7 (ajustes pequenos). 9. Item 6 (scripts). 10. Item 9 (backup automático e aviso). 11. Item 10 (demonstração). 12. Documentação: README, `docs/*.md`, lista de personalização e `MVP.MD` atualizados.

Motivo: o `x-flash` e as pílulas mexem nas mesmas views que o contexto de edição e a cancelada; os scripts de backup precisam estar corrigidos antes de rodar migrations no banco real.

## 15. Critérios de pronto

Todos os testes verdes (os 123 atuais mais os novos) e `npm run build` ok; nenhum erro 500 nos cenários dos itens 2, 3 e 4; tema escuro legível conforme o Item 1; cancelada fora de todos os totais; `iniciar.bat` abre o navegador só com o servidor no ar; backup validado e agendável; aviso de backup no dashboard; demonstração isolada em `_demo`; README completo; nenhum nome de empresa no repositório; nenhum commit feito.
