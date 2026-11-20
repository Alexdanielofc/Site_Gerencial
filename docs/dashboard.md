# Dashboard Principal

O **Dashboard** é a visão central do Site Gerencial (`/`), fornecendo um panorama consolidado dos três módulos (Vendas e Metas, Notas Fiscais e Contas a Pagar).

## Funcionalidades

### 1. Seletor de Período Unificado e Status do Backup
- Localizado no topo da página (`<x-period-pills>` via `App\Support\PeriodPills`).
- Agrupa dinamicamente todos os anos e meses com registros em **qualquer um dos módulos** (vendas, expectativas, notas fiscais e contas a pagar, incluindo períodos com registros cancelados), em conjunto com o mês corrente do servidor.
- Os períodos são deduplicados e apresentados no padrão de pílulas acessíveis.
- **Faixa de Status do Backup (`App\Support\BackupStatus`)**: exibe alerta discreto (`info` até 2 dias, `warning` de 3 a 7 dias, `danger` acima de 7 dias ou sem backup) com base no arquivo `site_gerencial_*.sql` mais recente em `config('backup.path')`.

### 2. Seção Vendas e Metas
- **Mês Atual**:
  - Total vendido acumulado no mês.
  - Meta acumulada até a data atual (soma das expectativas até o dia corrente).
  - Percentual da meta acumulada atingido.
  - Venda e expectativa registradas para o dia de hoje.
- **Outros Períodos**:
  - Total vendido no período.
  - Meta completa do mês.
  - Percentual da meta mensal atingido.

### 3. Seção Notas Fiscais
- Total monetário emitido no período em R$.
- Quantidade de notas fiscais emitidas no período.
- Emissões do dia atual (quando o período visualizado for o mês corrente).
- Estado vazio com orientação quando não há emissões no período.

### 4. Seção Contas a Pagar
- **Resumo do Período**: Total de contas, total pago e total pendente para o mês selecionado.
- **Bloco Atenção (Visão Imediata)**:
  - Independe do período selecionado nas pílulas superiores para garantir que pendências urgentes nunca passem despercebidas.
  - Exibe o valor total e contagem de todas as contas vencidas em aberto no sistema (com alerta vermelho).
  - Lista os próximos vencimentos previstos para os próximos 7 dias (até 5 contas mais urgentes).
