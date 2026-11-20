# Site Gerencial

Sistema local de gestão empresarial para operação diária, sem necessidade de login, projetado para execução local em computador único.

## Módulos do Sistema

1. **Dashboard (`/`)**:
   - Painel central integrado com seletor de período dinâmico (`<x-period-pills>`).
   - Faixa discreta de monitoramento da integridade do último backup (`App\Support\BackupStatus`).
   - Visão consolidada de vendas, notas fiscais, contas a pagar e bloco de atenção com contas vencidas e próximos vencimentos.
2. **Vendas e Metas (`/modulos/vendas`)**:
   - Registro de venda do dia com prevenção de duplicação e proteção contra duplo clique (`SubmissionGuard`).
   - Comparativo com meta acumulada e expectativa diária.
   - Gestão de expectativas do mês em lote com validação transacional tudo-ou-nada (`/modulos/vendas/expectativas`).
   - Visão avançada mensal com histórico diário (`/modulos/vendas/avancado`).
3. **Notas Fiscais (`/modulos/notas-fiscais`)**:
   - Cadastro ágil de notas emitidas com data, número, cliente, CNPJ e valor.
   - Listagem e edição das notas emitidas no dia, preservando o contexto de busca e período ao editar.
   - Cancelamento e reativação de notas (`canceled_at` / `cancel_reason`): notas canceladas permanecem no histórico com badge `Cancelada` e valor riscado, mas nunca entram em totais, contagens, ticket médio ou dashboard.
   - Consulta analítica avançada com filtro de situação (`todas`, `ativas`, `canceladas`), busca textual e ticket médio (`/modulos/notas-fiscais/avancado`).
4. **Contas a Pagar (`/modulos/contas-a-pagar`)**:
   - Cadastro e acompanhamento de compromissos financeiros.
   - Seção "Em atenção" com contas ativas vencidas e a vencer nos próximos 7 dias.
   - Quitação rápida com 1 clique ("Marcar como paga").
   - Cancelamento e reativação de contas em aberto (contas pagas devem ser reabertas limpando `paid_at` antes de cancelar; contas canceladas não podem ser pagas nem editadas).
   - Consulta avançada com filtros por situação (`todas`, `pendentes`, `vencidas`, `pagas`, `canceladas`) e busca (`/modulos/contas-a-pagar/avancado`).

---

## Como Executar Localmente

### Método 1: Inicialização Rápida (Recomendado no Windows)
Basta dar dois cliques no arquivo:
```cmd
iniciar.bat
```
O script:
1. Confirma que `.env` e `vendor` existem.
2. Verifica se o serviço MySQL está em execução (`Running`) via PowerShell e tenta iniciá-lo caso esteja parado.
3. Avisa caso `public/build/manifest.json` não exista ou caso `APP_ENV=local` / `APP_DEBUG=true`.
4. Inicia `php artisan serve --host=127.0.0.1 --port=8000` em janela própria.
5. Aguarda a porta `8000` responder (até 20 segundos) e **só então** abre o navegador padrão em `http://127.0.0.1:8000`.

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

## Central de Backup, Agendamento e Restauração

> [!IMPORTANT]
> Copiar apenas os arquivos da aplicação **NÃO** preserva os dados cadastrados. Os dados residem no banco de dados MySQL `site_gerencial` e exigem dump para backup efetivo.
> **Recomendação importante**: Copie periodicamente a pasta `backups` para um **pendrive** ou configure o **destino secundário** na Central de Backup.

Consulte a documentação completa em [docs/backup.md](file:///c:/DEV/Projetos/pessoal/Site_Gerencial/docs/backup.md).

### 1. Central de Backup na Web (`/configuracoes/backup`)
- Acesse via navegador ou clicando no **ícone de relógio no rodapé** de qualquer tela.
- **Gerar Backup Imediato**: Botão "GERAR BACKUP AGORA" cria e valida um dump na hora.
- **Destino Secundário (Pendrive/Rede)**: Permite configurar uma pasta externa (ex.: `E:\Backups`) e testar a conectividade isoladamente ("Testar destino"). Todo backup gerado é replicado automaticamente para lá.
- **Lista de Cópias**: Visualização de tamanho, integridade de cada arquivo e status da última restauração.

### 2. Gerar Backup por Linha de Comando ou Script
Dê dois cliques no script:
```cmd
backup.bat
```
Ou via Artisan:
```powershell
php artisan backup:run
```
- O dump é gravado em `backups\site_gerencial_AAAA-MM-DD_HHMM.sql`.
- Valida integridade (> 0 bytes, `Dump completed` e presença de todas as 4 tabelas essenciais).
- A rotação automática mantém os **30 backups mais recentes** na pasta local.

### 3. Agendar Backup Automático Diário (Windows)
Para agendar o backup diário automático às **20:00** (com `StartWhenAvailable`, executando ao ligar o computador caso tenha perdido o horário):
1. Dê dois cliques em `agendar-backup.bat`.
2. A tarefa `SiteGerencial-Backup` é registrada na raiz do Agendador de Tarefas do Windows chamando `backup.bat auto` (histórico gravado em `backups\backup.log`).
3. Para remover o agendamento a qualquer momento, execute `remover-agendamento.bat`.

### 4. Indicador de Monitoramento no Rodapé
Todas as páginas contam com um indicador no rodapé exibindo a situação do último backup:
- **Verde**: Hoje (com horário).
- **Azul**: Há 1 ou 2 dias.
- **Amarelo**: Entre 3 e 7 dias.
- **Vermelho**: Mais de 7 dias ou sem backup.

### 5. Restauração pela Interface e Chave de Segurança
Por segurança estrita contra perda acidental de dados, a restauração via web é **desabilitada por padrão** em todos os ambientes.

**Para habilitar temporariamente:**
1. No `.env` (ou `.env.demo`), defina:
   ```env
   BACKUP_RESTORE_ENABLED=true
   ```
2. Limpe os caches de configuração:
   ```powershell
   php artisan config:clear
   ```
3. Acesse a Central de Backup (`/configuracoes/backup`), clique em **Restaurar** ao lado do backup desejado e digite a palavra `RESTAURAR` em maiúsculas para confirmar.
4. O sistema cria automaticamente um backup de segurança (`pre-restauracao_...`), restaura o arquivo e executa as migrações pendentes (`php artisan migrate --force`).
5. Ao concluir a recuperação, desabilite novamente no `.env`:
   ```env
   BACKUP_RESTORE_ENABLED=false
   ```
   e execute `php artisan config:clear`.

### 6. Atalho no Desktop com Ícone Personalizado
Para criar um atalho na Área de Trabalho com o ícone oficial da aplicação:
```cmd
criar-atalho.bat
```
O script gera o atalho `Site Gerencial.lnk` apontando para o servidor local e associado ao ícone multi-resolução `public/icons/site-gerencial.ico`.

---

## Modo Demonstração (`_demo`)

O sistema possui um modo de demonstração completamente isolado para apresentar o produto com dados fictícios plausíveis sem nunca tocar no banco real `site_gerencial`.

### Regras de Segurança do Modo Demonstração
- O comando `php artisan demo:seed` **só executa se o nome do banco conectado terminar obrigatoriamente em `_demo`** (ex.: `site_gerencial_demo`).
- Em qualquer outro banco (incluindo `site_gerencial` e `site_gerencial_test`), o comando recusa a operação imediatamente com código de erro diferente de zero. Não existe opção `--force` que contorne essa trava.

### Passo a Passo para Configurar e Iniciar a Demonstração
1. Crie o banco `site_gerencial_demo` no MySQL e conceda permissão ao mesmo usuário da aplicação:
   ```sql
   CREATE DATABASE IF NOT EXISTS site_gerencial_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON site_gerencial_demo.* TO 'site_gerencial'@'localhost';
   FLUSH PRIVILEGES;
   ```
2. Copie o modelo `.env.demo.example` para `.env.demo` (o arquivo `.env.demo` está no `.gitignore` e nunca é versionado) e preencha `APP_KEY` e `DB_PASSWORD`:
   ```powershell
   Copy-Item .env.demo.example .env.demo
   php artisan key:generate --env=demo
   ```
3. Execute as migrations e popule os dados fictícios determinísticos no ambiente `demo`:
   ```powershell
   php artisan migrate --env=demo
   php artisan demo:seed --env=demo --yes
   ```
4. Inicie o servidor de demonstração na porta `8001` dando dois cliques em:
   ```cmd
   iniciar-demo.bat
   ```
   O navegador abrirá em `http://127.0.0.1:8001` exibindo 3 meses anteriores + mês atual (com o dia de hoje em aberto para demonstrar o lançamento de venda diária, expectativas preenchidas, notas fiscais e contas a pagar pagas, pendentes, vencidas e canceladas).

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

A aplicação conta com suíte completa de testes de funcionalidades e regras de negócio via Pest (utilizando o banco isolado `site_gerencial_test`):

```powershell
php artisan test --compact
```

---

## Pontos de Personalização (White Label)

O sistema foi desenvolvido como produto white label. Para personalizar para a empresa final, consulte os pontos mapeados abaixo:

1. **Subtítulo e Iniciais do Cabeçalho**: `resources/views/components/header.blade.php` (marcador `Empresa` e monograma `SG`).
2. **Sufixo do Título das Páginas**: `resources/views/components/layout.blade.php` (`<title>{{ $title }} | Site Gerencial</title>`).
3. **Crédito do Rodapé**: `resources/views/components/footer.blade.php`.
4. **Paleta de Cores e Tokens Visuais**: `resources/css/app.css` (variáveis CSS dentro de `:root`, `@theme inline` e `html[data-theme='dark']`).
5. **Nome da Aplicação**: Variável `APP_NAME` no arquivo `.env`.

---

## Tokens Semânticos, Tema Escuro e Contraste WCAG AA

O sistema utiliza **tokens semânticos de cor** definidos em `resources/css/app.css`. Esses tokens se sobrepõem automaticamente sob `html[data-theme='dark']` sem espalhar classes `dark:` nas views. O token `--branco` (`#ffffff`) nunca muda entre os temas para garantir contraste elevado sobre botões primários e pílulas selecionadas (`bg-blu6`).

### Tabela de Tokens Semânticos

| Token CSS | Tailwind | Modo Claro | Modo Escuro | Uso |
|---|---|---|---|---|
| `--surface` | `bg-surface` | `#ffffff` | `#111a2e` | Fundo de cards, campos, tabelas |
| `--surface-2` | `bg-surface-2` | `#f9fafb` | `#16213a` | Cabeçalhos de tabela, fundos suaves |
| `--line` | `border-line` | `#d1d5db` | `#26344f` | Bordas de cards, inputs, divisores |
| `--ink` | `text-ink` | `#1f2937` | `#e6edf7` | Texto principal |
| `--ink-soft` | `text-ink-soft` | `#4b5563` | `#9fb0c8` | Texto secundário e placeholders |
| `--accent` | `text-accent` | `#2563eb` | `#93c5fd` | Links, textos de destaque, anel de foco |
| `--chrome` | `bg-chrome` | `#1f2937` | `#070d19` | Fundo do header e do footer |
| `--chrome-ink` | `text-chrome-ink` | `#d1d5db` | `#d1d5db` | Texto e links do header e do footer |
| `--chrome-line` | `border-chrome-line` | `#4b5563` | `#26344f` | Borda divisória do header e do footer |

### Razões de Contraste WCAG AA Aferidas

| Par avaliado | Modo Claro | Modo Escuro | Mínimo WCAG AA |
|---|---|---|---|
| Header / Footer (`chrome-ink` sobre `chrome`) | **9,96:1** | **13,19:1** | 4,5:1 |
| Navegação ativa / hover (`blu3` sobre `chrome`) | **8,14:1** | **10,78:1** | 4,5:1 |
| Botão primário, pílula ativa e logo SG (`branco` sobre `blu6`) | **5,17:1** | **5,17:1** | 4,5:1 |
| Texto principal em card/campo/tabela (`ink` sobre `surface`) | **14,68:1** | **14,72:1** | 4,5:1 |
| Texto secundário e placeholder (`ink-soft` sobre `surface`) | **7,56:1** | **7,86:1** | 4,5:1 |
| Cabeçalho de tabela (`ink-soft` sobre `surface-2`) | **7,23:1** | **7,25:1** | 4,5:1 |
| Links e foco (`accent` sobre `surface`) | **5,17:1** | **9,62:1** | 4,5:1 |
| Alerta e badge `success` (`grn7` sobre `grn1`) | **4,79:1** | **10,96:1** | 4,5:1 |
| Alerta e badge `danger` (`red7` sobre `red1`) | **5,91:1** | **9,15:1** | 4,5:1 |
| Alerta e badge `warning` (`yel8` sobre `yel1`) | **6,84:1** | **12,59:1** | 4,5:1 |
| Alerta `info` (`ink` sobre `blu1`) | **13,49:1** | **13,88:1** | 4,5:1 |
| Mensagem de erro de campo (`red6` sobre `surface`) | **4,83:1** | **5,76:1** | 4,5:1 |
