Site Gerencial — Especificação do MVP
Documento de handoff para o agente de implementação. Leia tudo antes de escrever código. Atualizado em 17/11/2026.
1. Objetivo
Sistema local de gestão para uma única empresa, em um computador, sem login. Três módulos (Vendas e metas, Notas fiscais, Contas a pagar) e um dashboard em /. Meta: MVP pronto para uso diário. Prioridade: dados corretos e regras de negócio fiéis. A estética já está definida e NÃO deve mudar.
Cada módulo tem uma página principal (registro rápido + indicadores) e uma área avançada (mês inteiro, seletores de ano e mês, indicadores, tabela, edição).
2. Regras invioláveis
1.  Banco real: site_gerencial (MySQL 9). NUNCA rodar migrate:fresh, migrate:rollback, migrate:reset, db:wipe, TRUNCATE ou DROP nele. Tabela nova = migration nova. Nunca editar migrations já executadas.
2.  Testes usam site_gerencial_test (definido no phpunit.xml; RefreshDatabase ativo em tests/Pest.php). Manter tests/Feature/TestDatabaseTest.php. Não usar SQLite.
3.  Não exibir nem commitar .env. Sem commit/push sem autorização do usuário.
4.  Nenhuma dependência nova (composer ou npm). Sem React, Vue, Livewire, Inertia. JavaScript só se indispensável.
5.  Sem autenticação. Sem exclusão: nenhuma rota, botão ou método de delete em nenhum módulo; só criar e editar.
6.  White label: proibido escrever nome de empresa ou do ramo de negócio (ex.: papelaria) em código, views, testes, seeds ou README. O produto é 'Site Gerencial'; o subtítulo 'Empresa' do header é um marcador.
7.  Código em inglês (classes, tabelas, colunas, variáveis, nomes de teste). Interface em português do Brasil. URLs em português (já definidas).
8.  Antes de alterar um arquivo, leia a versão real. Não presuma. Reutilize o que existe.
9.  Arquitetura simples: Controllers, Models/Eloquent, Form Requests, Blade, Tailwind, Pest. Única classe de apoio: App\Support\Money. Sem services, repositories ou camadas novas.
10.  Dinheiro nunca em float.
11.  AGENTS.md e CLAUDE.md do repositório continuam valendo; em conflito, este documento prevalece.
12.  Regra ambígua: siga a decisão registrada aqui, não invente outra.
3. Estado atual (já feito, não refazer)
• Laravel 13, PHP 8.5, Blade, Tailwind 4, Vite 8, Pest 5, MySQL 9. Fuso America/Sao_Paulo, locale pt_BR. Fonte IBM Plex Sans servida pelo Vite (@fonts no layout).
• Tabelas: daily_sales (sale_date DATE UNIQUE, amount DECIMAL(12,2)) e sales_expectations (expectation_date DATE UNIQUE, amount DECIMAL(12,2)). Models DailySale e SalesExpectation.
• Prontos: PeriodRequest (lê ano e mes; método monthStart()), DashboardController (pílulas de período + indicadores de Vendas), DailySale::periodsWithRecords(), totalCentsBetween() e centsOn() nos dois Models, App\Support\Money (format, percentOf, formatPercent).
• Views: layout, header, footer, navbar, componentes (seção 4), index.blade.php (pílulas + seção Vendas), modules/placeholder.blade.php (usada por enquanto pelas rotas dos módulos).
• Rotas: site.index usa o DashboardController; sales.index, invoices.index e payables.index ainda usam Route::view com o placeholder.
• Testes: 21 passando antes do passo 4.4. O código do 4.4 (indicadores de Vendas no dashboard) foi entregue, mas a validação manual NÃO foi confirmada. Primeiro passo: php artisan test --compact e abrir / sem erro.
4. Design system (manter idêntico)
• Cores: SOMENTE os tokens do :root em resources/css/app.css: cin1 cin2 cin3 cin6 cin8, blu1 blu3 blu5 blu6 blu7, grn1 grn2 grn6 grn7, red1 red2 red6 red7, yel1 yel2 yel6 yel8, branco, bg, dot. Proibido hex solto e paleta padrão do Tailwind (gray-*, blue-*). Faltou um tom: crie o token no :root e em @theme inline.
• Significado: azul = valor principal e ação; verde = positivo (meta atingida, conta paga); vermelho = alerta, vencida, erro; âmbar = atenção, pendente; cinza = neutro. Usar com moderação.
• Visual: fundo branco com pontilhado azul-claro, IBM Plex Sans, cards brancos (rounded-xl border-cin2 shadow-sm), cabeçalho e rodapé cinza-escuro, container max-w-7xl.
• Componentes (resources/views/components): x-page-header (title, description, eyebrow, back-href, back-label, slot actions); x-card (as, size md|lg|none); x-stat (label, value, hint, tone blue|green|red|neutral); x-button (variant primary|secondary, href ou type; rótulo em maiúsculas por CSS); x-alert (type danger|warning|success|info, title, icon); x-pill (href, selected); x-empty-state (title, description, slot); ícones x-icons.* (arrow-left, arrow-right, warning, check, info, pencil, calendar). Novos componentes só se necessário (sugeridos: x-money-input, x-badge de situação), com os mesmos tokens e teste em tests/Feature/ComponentsTest.php.
• Tabelas: contêiner overflow-x-auto, cabeçalho bg-cin1, bordas cin2, valores em R$ à direita com tabular-nums.
• Responsivo (desktop primeiro, utilizável em telas pequenas). Acessibilidade: label em todo campo, mensagens com role, foco visível, status nunca só por cor (ícone + texto).
• Textos fixos (usar exatamente): 'Registro de hoje já adicionado.', 'DIA SEM EXPECTATIVA', 'EXPECTATIVA DE VENDA MÊS', 'AVANÇADO', 'EDITAR'.
5. Regras transversais
Dinheiro
• Banco: DECIMAL(12,2). Somas no SQL com SUM(amount * 100), devolvendo centavos inteiros; o PHP trabalha com int em centavos e só formata ao exibir: Money::format() (R$ 1.234,56; null vira —), Money::percentOf() (divisão por zero devolve null, exibido como —) e Money::formatPercent() (1 casa, vírgula).
• Entrada: campo de texto com inputmode='decimal'. Normalizar no servidor em prepareForValidation() com um novo Money::parseToDecimal(?string): ?string (com testes unitários): remover 'R$' e espaços; havendo vírgula, remover os pontos e trocar a vírgula por ponto; sem vírgula, aceitar um único ponto seguido de 1 ou 2 dígitos como decimal, ou pontos de milhar em grupos de 3 dígitos; qualquer outra forma é inválida. Regras: numérico, até 2 casas, máximo 9999999999.99. Zero é válido em vendas e expectativas; notas e contas exigem no mínimo 0,01.
Datas
• Colunas DATE. Usar today() e now() do Laravel (fuso configurado), nunca date(). Comparar com toDateString(). Carbon é mutável: use ->copy() ao derivar datas. Para mês por extenso, Carbon::create(2000, $mes, 1) evita estouro de dia.
• Exibição dd/mm/aaaa; dia da semana por extenso com inicial maiúscula (Str::ucfirst($d->translatedFormat('l'))).
• Período na URL: ?ano=AAAA&mes=M, validado por PeriodRequest (reutilizar em toda tela com período; padrão = mês atual).
• Vendas e notas fiscais NÃO aceitam data futura. Contas a pagar: vencimento livre; paid_at não pode ser futuro.
Pílulas de período (ano em cima, mês embaixo; selecionado = fundo azul, demais = branco com borda)
• Lista = períodos com registro no módulo UNIÃO o mês atual (decisão do usuário: o mês atual sempre aparece). Vale também nas áreas avançadas, para sempre haver uma pílula selecionada.
• Link de ano: ano já selecionado mantém o mês; ano atual vai para o mês atual; outro ano mantém o mês selecionado se existir ali, senão vai para o mais recente com registro. Já implementado no DashboardController: extrair para reuso (método estático ou partial Blade), sem service.
Mensagens e erros
• Sucesso e erro via session()->flash exibidos com x-alert. Validação em português: criar lang/pt_BR/validation.php à mão (sem pacote) com as mensagens das regras usadas e attributes. Violação de UNIQUE vira mensagem amigável, nunca erro 500.
• @csrf e @method em todo formulário; um Form Request por formulário; nunca $request->all() em create/update; usar $fillable.
• Sem exclusão: corrigir erro = editar (venda errada vira outro valor, inclusive 0,00).
6. Rotas (nomes obrigatórios)
Rotas estáticas (avancado, expectativas, nova) devem ser declaradas antes das com parâmetro. IDs com whereNumber; data com restrição AAAA-MM-DD (inválida = 404). Manter nomes e URLs existentes; trocar Route::view por controllers (SalesController, SalesExpectationController, InvoiceController, PayableController). Remover o placeholder só quando nenhuma rota o usar.
Nome
Método e URL
sales.index
GET /modulos/vendas
sales.store
POST /modulos/vendas (venda de hoje)
sales.edit
GET /modulos/vendas/dia/{date}/editar
sales.update
PUT /modulos/vendas/dia/{date}
sales.expectations.edit
GET /modulos/vendas/expectativas
sales.expectations.update
PUT /modulos/vendas/expectativas
sales.advanced
GET /modulos/vendas/avancado
invoices.index
GET /modulos/notas-fiscais
invoices.store
POST /modulos/notas-fiscais
invoices.advanced
GET /modulos/notas-fiscais/avancado
invoices.create
GET /modulos/notas-fiscais/nova
invoices.edit
GET /modulos/notas-fiscais/{invoice}/editar
invoices.update
PUT /modulos/notas-fiscais/{invoice}
payables.index
GET /modulos/contas-a-pagar
payables.store
POST /modulos/contas-a-pagar
payables.advanced
GET /modulos/contas-a-pagar/avancado
payables.create
GET /modulos/contas-a-pagar/nova
payables.edit
GET /modulos/contas-a-pagar/{payable}/editar
payables.update
PUT /modulos/contas-a-pagar/{payable}
payables.pay
POST /modulos/contas-a-pagar/{payable}/pagar
7. Módulo Vendas e metas
Regras
• V1: no máximo uma venda por dia (UNIQUE). Dia sem registro é diferente de registro com 0,00.
• V2: expectativa é independente da venda e nunca muda sozinha.
• V3: meta acumulada até hoje = soma das expectativas do dia 1 até hoje (só no mês atual). Meta completa = soma de todos os dias do mês. Percentual = vendido dividido pela meta correspondente; meta 0 ou ausente = —. Não confundir as duas metas.
• V4: expectativa inexistente ou igual a 0 dispara o alerta 'DIA SEM EXPECTATIVA'.
• V5: venda com data futura é proibida.
• V6: tabelas e colunas existentes não mudam.
Indicadores: mover salesIndicators do DashboardController para um método estático reutilizável (ex.: DailySale::indicators(Carbon $monthStart, Carbon $today): array), usado no dashboard e na página de Vendas. Mês atual: total vendido, meta acumulada até hoje, % da meta acumulada, expectativa de hoje (valor ou 'Pendente'), vendido hoje (valor ou — com 'Sem registro hoje'). Outro mês: total vendido, meta completa, % da meta mensal. Percentual >= 100 = verde; abaixo = azul; sem meta = neutro.
7.1 Página principal /modulos/vendas x-page-header com voltar ao dashboard. Uma section central em destaque (x-card size lg):
1.  Data de hoje (dd/mm/aaaa) e dia da semana por extenso. A data vem do servidor e não é editável aqui.
2.  Sem venda hoje: formulário com 'Venda diária' (R$) e botão Salvar (sales.store). Com venda hoje: x-alert success 'Registro de hoje já adicionado.' com o valor e botão EDITAR (leva a sales.edit com a data de hoje). store com registro existente não duplica: redireciona com a mensagem.
3.  Expectativa de hoje (valor ou 'Pendente'). Inexistente ou 0: x-alert danger com ícone de atenção, texto 'DIA SEM EXPECTATIVA' e link para a tela de expectativas. O alerta não bloqueia o registro da venda.
4.  Três x-stat: vendido no mês até hoje, meta acumulada até hoje, % atingido.
5.  Botões: 'EXPECTATIVA DE VENDA MÊS' (primary, destaque) e 'AVANÇADO' (secondary).
7.2 Expectativas do mês /modulos/vendas/expectativas
• Seletor em pílulas com apenas 3 opções: mês anterior, mês atual (padrão) e mês seguinte, em calendário real (cruza a virada de ano). Outro período na URL: redirecionar ao mês atual com aviso. Só esta tela tem essa janela.
• Tabela com TODOS os dias do mês (28 a 31, inclusive fins de semana e feriados): data completa, dia da semana por extenso e um campo de valor por dia (expectations[AAAA-MM-DD]).
• Carregar valores salvos (0 aparece como 0,00). Mês nunca salvo = campos vazios.
• Salvar: uma transação com upsert (chave expectation_date) para TODOS os dias do mês; campo vazio vira 0,00; preenchido atualiza. Nunca apagar linhas, nunca duplicar. Validar: cada chave é uma data dentro do mês exibido; cada valor segue a regra de dinheiro.
• Depois de salvar, voltar à mesma tela com sucesso.
7.3 Área avançada /modulos/vendas/avancado
• Pílulas de ano e mês (seção 5: períodos com venda ou expectativa, mais o mês atual). Padrão: ano e mês atuais.
• Cards: total vendido, meta completa do mês, % da meta mensal (meta 0 = —).
• Tabela com TODOS os dias do mês: data, dia da semana, valor vendido, ação EDITAR. Dia sem registro mostra R$ 0,00 com marca discreta 'sem registro' e NÃO cria linha no banco. Buscar as vendas do mês em uma única consulta indexada por data (sem N+1). Dias futuros sem ação de edição. A expectativa não é editável aqui.
• Meses nunca se misturam; mês de 28, 29 (bissexto), 30 ou 31 dias mostra exatamente seus dias.
7.4 Edição de um dia /modulos/vendas/dia/{date}/editar Campos: data (somente leitura) e valor. Serve para editar e para criar venda de data anterior. update faz updateOrCreate pela data; data futura é rejeitada. Expectativa do dia só para leitura. Ao salvar, volta à tela de origem (principal ou avançada, mesmo período) com sucesso e indicadores recalculados.
8. Módulo Notas fiscais
Decisões assumidas (a planilha original não foi fornecida; ajustáveis depois). Tabela invoices (nova migration): id; issue_date DATE com índice; invoice_number string(40) nullable UNIQUE; customer_name string(150) em texto livre (sem cadastro de clientes); customer_cnpj string(14) nullable (só dígitos, 14 dígitos, exibido com máscara 00.000.000/0000-00); amount DECIMAL(12,2); notes string(500) nullable; timestamps. Model Invoice com $fillable e casts.
Regras: N1 vários registros no mesmo dia (sem UNIQUE de data). N2 invoice_number, quando informado, é único (validação + índice; vários nulos permitidos). N3 data não pode ser futura. N4 valor mínimo 0,01. N5 sem exclusão.
Página principal: x-page-header; formulário (data padrão hoje, editável até hoje; número, cliente, CNPJ opcional, valor, observações); lista das notas de hoje, cada uma com EDITAR; indicadores do mês atual (quantidade, valor total, notas de hoje); botão AVANÇADO. Sem mensagem de 'já adicionado' (vários por dia é permitido). Estado vazio claro.
Área avançada: pílulas; cards do período (quantidade, valor total, ticket médio = total ÷ quantidade, — se 0 notas); busca por texto (cliente ou número) via query string; tabela (data, número, cliente, CNPJ, valor, EDITAR) ordenada por data e id decrescentes, 25 por página com withQueryString; botão 'Nova nota' para qualquer data (invoices.create); x-empty-state quando vazio.
Edição: mesmo formulário preenchido; a unicidade do número ignora o próprio registro. Nenhum indicador de 'atenção' para notas no MVP (não inventar métricas).
9. Módulo Contas a pagar
Tabela payables (nova migration): id; description string(150); amount DECIMAL(12,2); due_date DATE com índice; paid_at DATE nullable com índice; notes string(500) nullable; timestamps. Model Payable com $fillable, casts e método de situação.
Situação NUNCA é coluna; é calculada: paga = paid_at preenchido; vencida = paid_at nulo e due_date anterior a hoje; pendente = paid_at nulo e due_date de hoje em diante (vence hoje = pendente). Conta não vira paga só porque venceu. Visual: paga = verde com check; vencida = vermelho com ícone de atenção; pendente = âmbar; sempre ícone + texto (x-badge).
Regras: C1 vários registros por dia. C2 valor mínimo 0,01. C3 paid_at não pode ser futuro (pagar antes do vencimento é permitido). C4 vencimento livre. C5 sem exclusão; reabrir conta paga = editar e limpar paid_at. C6 o período de uma conta é o mês/ano do due_date.
'Marcar como paga' (payables.pay): grava paid_at = hoje; se já paga, não altera e mostra 'Conta já paga em dd/mm/aaaa.'. Data de pagamento diferente de hoje: pela edição.
Página principal: formulário (descrição, valor, vencimento com sugestão = hoje, observações); lista 'Em atenção' = contas em aberto vencidas ou que vencem em até 7 dias (inclusive hoje), por vencimento, cada uma com 'Marcar como paga' e EDITAR; indicadores: total em aberto, vencidas (quantidade e valor; vermelho se > 0), a vencer em 7 dias; botão AVANÇADO.
Área avançada: pílulas (período = due_date); cards do período em R$: total, pago, pendente, vencido; filtro por situação (todas, pendentes, vencidas, pagas) e por texto na descrição via query string; tabela (vencimento, descrição, valor, situação, pago em, ações), 25 por página; 'Nova conta' (payables.create). Vencidas em destaque vermelho.
10. Dashboard / (revisão final)
Três sections (Vendas e metas, Notas fiscais, Contas a pagar), cada uma em x-card com título, x-stat e botão 'Abrir módulo'. Seletor de ano e mês no topo: períodos com registro em QUALQUER módulo (venda ou expectativa por data, nota por issue_date, conta por due_date) mais o mês atual; cada Model expõe periodsWithRecords() e o dashboard junta e deduplica. Tudo vem do banco, sem número fixo.
• Vendas: já implementado (seção 7).
• Notas fiscais (período selecionado): quantidade e valor total; no mês atual, também 'notas de hoje'.
• Contas a pagar: resumo do período (total, pago, pendente) e bloco 'Atenção' independente do período: vencidas (todas em aberto com vencimento anterior a hoje: quantidade e valor, vermelho se > 0) e próximos vencimentos (até 5 contas em aberto de hoje a hoje+7 dias, com data, descrição e valor).
• Estados vazios claros em cada section (ex.: 'Nenhuma nota registrada neste período.') com valores zerados. Remover o conteúdo estático antigo da página. Não contar o mesmo registro duas vezes.
11. Testes (Pest, MySQL de teste)
Usar $this->travelTo() para fixar 'hoje' em todo teste que dependa da data. Cobrir no mínimo:
• Vendas: página principal; registrar venda de hoje; não duplicar; mensagem 'Registro de hoje já adicionado.' e EDITAR; editar; alerta DIA SEM EXPECTATIVA (ausente e zero); meta acumulada (sem dias futuros); meta completa; percentuais; meta zero = —; dias sem venda com R$ 0,00 sem criar linha; salvar expectativas do mês (vazio vira 0,00, sem duplicar, atualiza); campos preenchidos com valores salvos; janela de 3 meses; venda futura rejeitada; formatos de valor ('1.234,56', '1234,56', '1234.5', negativo, texto); períodos na área avançada; meses de 28, 29, 30 e 31 dias; independência entre venda e expectativa.
• Unitários: Money::parseToDecimal, format, percentOf.
• Notas: criar, validar, número único, vários no mesmo dia, editar, busca, indicadores, data futura rejeitada.
• Contas: situações (paga, vencida, pendente, vence hoje), marcar como paga, conta já paga, reabrir, indicadores, filtros, paid_at futuro rejeitado.
• Dashboard: períodos de todos os módulos, estados vazios, sem dupla contagem, links.
• Meta final: php artisan test --compact verde e npm run build sem erro.
12. Ordem de execução (conferir ao fim de cada etapa)
1.  Linha de base (seção 3). 1. Vendas: principal e edição (7.1, 7.4) com indicadores extraídos. 2. Expectativas (7.2). 3. Área avançada de Vendas (7.3). 4. Testes de Vendas e lang/pt_BR/validation.php. 5. Notas fiscais (migration, model, telas, testes). 6. Contas a pagar (idem). 7. Dashboard final (seção 10) com teste. 8. Revisão: mensagens, responsividade, acessibilidade, consultas sem N+1, README. 9. Execução local e backup (seção 13). 10. Regressão final: testes, build e conferência manual de todas as rotas.
Cada migration nova roda com php artisan migrate (só cria tabela). Nunca recriar o banco.
13. Execução local e backup
• iniciar.bat na raiz: confere o serviço MySQL e avisa em português se estiver parado; sobe php artisan serve --host=127.0.0.1 --port=8000; abre http://127.0.0.1:8000. Em caso de erro, mensagem clara.
• Produção local: .env com APP_ENV=production e APP_DEBUG=false; npm run build, php artisan config:cache, route:cache, view:cache; após mudar o .env, php artisan config:clear. A fonte já é local (não precisa de internet).
• backup.bat: mysqldump --defaults-extra-file=<credenciais fora do repositório> --single-transaction --default-character-set=utf8mb4 site_gerencial gravando em backups/site_gerencial_AAAA-MM-DD_HHMM.sql. O arquivo de credenciais ([client] com user e password) fica fora do projeto; backups/ entra no .gitignore. Restauração: mysql --defaults-extra-file=<...> site_gerencial < arquivo.sql. Copiar só os arquivos do Laravel NÃO preserva os dados: o banco precisa de dump.
• README: como iniciar, fazer e restaurar backup, e a lista da seção 14.
14. Pontos de personalização (white label)
Na entrega final o usuário informará nome da empresa e cores. Pontos: subtítulo 'Empresa' e iniciais 'SG' em components/header.blade.php; sufixo do <title> em components/layout.blade.php; crédito em components/footer.blade.php; paleta no :root de resources/css/app.css; APP_NAME no .env; README.md. Manter esses pontos isolados e documentados; não espalhar nomes pelo código.
15. Definição de pronto
Todas as rotas da seção 6 funcionam; regras V1 a V6, N1 a N5 e C1 a C6 cobertas por teste; nenhum hex ou cor fora dos tokens; nenhum nome de empresa no repositório; nenhuma rota de exclusão; testes e build verdes; dashboard 100% alimentado pelo banco; iniciar e backup funcionando; README atualizado.