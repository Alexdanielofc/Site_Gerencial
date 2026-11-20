# Central de Backup, Agendamento e Restauração

O **Site Gerencial** possui um subsistema robusto de proteção de dados, garantindo que todas as informações operacionais (vendas, expectativas, notas fiscais e contas a pagar) permaneçam seguras, com cópias locais e possibilidade de espelhamento secundário.

---

## 1. Central de Backup (`/configuracoes/backup`)

A Central de Backup permite ao usuário monitorar e gerenciar cópias de segurança diretamente pela interface:
- **Resumo de armazenamento**: Exibe a quantidade de backups locais, espaço total ocupado em disco e data do backup mais recente.
- **Botão "Gerar Backup Agora"**: Dispara imediatamente a rotina `BackupRunner::run()`, gerando um dump validado sem necessidade de abrir terminais.
- **Lista de backups disponíveis**: Tabela ordenada do mais recente para o mais antigo com nome do arquivo, data/hora formatada, tamanho e indicador de integridade (`Completo` ou `Inválido`).
- **Última restauração**: Bloco informativo em destaque caso uma restauração recente tenha sido executada, apontando a data, arquivo utilizado e o backup de segurança gerado.

---

## 2. Destino Secundário (Pendrive, HD Externo ou Rede)

Manter backups no mesmo computador não protege contra defeito de hardware. A Central de Backup disponibiliza configuração de pasta secundária:
- **Caminho personalizado**: Suporta caminhos como `E:\Backups` (pendrive) ou `\\servidor\compartilhamento\backups` (rede).
- **Sem bloqueio de requisições**: A tela não testa nem acessa o caminho secundário no carregamento, evitando travamentos caso a unidade esteja desconectada.
- **Botão "Testar destino"**: Executa verificação isolada com limite estrito de tempo (timeout de 5 segundos), testando permissão de escrita e exclusão temporária.
- **Cópia automática**: Ao executar qualquer backup (manual, via Central ou agendado), o sistema replica automaticamente o arquivo gerado para o destino extra e registra o status da cópia.

---

## 3. Relógio e Indicador de Backup no Rodapé

Em todas as páginas do sistema, o rodapé exibe um botão/indicador com ícone de relógio e atalho para a Central de Backup:
- **Verde**: Backup realizado hoje (exibe horário).
- **Azul**: Backup recente (há 1 ou 2 dias).
- **Amarelo**: Backup com atenção recomendada (entre 3 e 7 dias).
- **Vermelho**: Sem backup recente (há mais de 7 dias ou ausente).
- **Otimização**: Executa leitura rápida por listagem de arquivos uma única vez por requisição (memoizada em memória), sem abrir conteúdo de arquivos.

---

## 4. Agendamento Automático de Backup (Windows)

- **Script `agendar-backup.bat`**: Executa `scripts/agendar-backup.ps1` com elevação de administrador e registra a tarefa `SiteGerencial-Backup` na raiz (`\`) do Agendador de Tarefas do Windows.
- **Configurações da tarefa**:
  - Execução diária às **20:00**.
  - Flag `StartWhenAvailable`: Caso o computador esteja desligado às 20:00, o backup executa assim que o Windows iniciar.
  - Executa mesmo em operação por bateria em notebooks.
  - Chama `backup.bat auto`, registrando logs em `backups\backup.log`.
- **Script `remover-agendamento.bat`**: Remove a tarefa agendada do sistema de forma limpa.

---

## 5. Restauração com Salvaguardas

A restauração de banco de dados é uma operação crítica e potencialmente destrutiva. O sistema implementa 5 níveis de proteção:

### 5.1 Chave de Ativação (`BACKUP_RESTORE_ENABLED`)
Por padrão, a restauração está **desabilitada em todos os ambientes** (`BACKUP_RESTORE_ENABLED=false`).
- Quando desabilitada: os botões de restauração não aparecem na interface e qualquer tentativa de acessar rotas de restauração retorna **HTTP 404 Not Found**.
- Para habilitar (por exemplo, no ambiente local de recuperação ou no ambiente de demonstração):
  1. Edite o `.env` (ou `.env.demo`):
     ```env
     BACKUP_RESTORE_ENABLED=true
     ```
  2. Limpe o cache de configuração:
     ```powershell
     php artisan config:clear
     ```
  3. Para desabilitar novamente após o uso:
     ```env
     BACKUP_RESTORE_ENABLED=false
     ```
     e execute `php artisan config:clear`.

### 5.2 Validação de Integridade
O arquivo selecionado para restauração é checado antes da execução: deve conter tamanho maior que zero, assinatura de encerramento `-- Dump completed` e a presença das tabelas `daily_sales`, `sales_expectations`, `invoices` e `payables`.

### 5.3 Confirmação por Digitação
A tela de confirmação (`/configuracoes/backup/restaurar/{file}`) exige que o usuário digite explicitamente a palavra `RESTAURAR` em letras maiúsculas.

### 5.4 Backup de Segurança Automático Pré-Restauração
Antes de iniciar a importação do arquivo, o sistema gera automaticamente uma cópia de segurança completa do estado atual com o prefixo:
`site_gerencial_pre-restauracao_AAAA-MM-DD_HHMM.sql`
Se a criação deste backup de segurança falhar, **o processo é imediatamente abortado** antes de tocar no banco de dados.

### 5.5 Migrações Automáticas Pós-Restauração
Logo após a importação do arquivo SQL, o sistema executa automaticamente `php artisan migrate --force` para garantir que quaisquer migrações criadas posteriormente sejam aplicadas sem incompatibilidades.

### 5.6 Registro Atômico de Status
O resultado é gravado em `storage/app/backup-restore-status.json` com gravação atômica (arquivo temporário seguido de rename), informando data/hora, arquivo de origem, backup de segurança e resumo da execução.

---

## 6. Atalho no Desktop e Favicons

- **Ícones**: Favicon SVG vetorial (`public/favicon.svg`) e ICO multi-resolução de 16x16 a 256x256 (`public/favicon.ico` e `public/icons/site-gerencial.ico`).
- **Script `criar-atalho.bat`**: Executa `scripts/criar-atalho.ps1`, criando o atalho `Site Gerencial.lnk` na Área de Trabalho com o ícone oficial da aplicação, apontando para o servidor local com pasta de trabalho configurada.
