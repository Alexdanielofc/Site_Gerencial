# Módulo Contas a Pagar

O módulo de **Contas a Pagar** é responsável pela gestão e acompanhamento das despesas e compromissos financeiros futuros, prazos de vencimento e quitações.

## Banco de Dados (`payables`)
- `id`: identificador sequencial.
- `description`: descrição da conta ou fornecedor (string até 150 caracteres).
- `amount`: valor monetário da conta (`DECIMAL(12,2)`, mínimo `0,01`).
- `due_date`: data de vencimento (com índice, vencimento livre).
- `paid_at`: data de liquidação/pagamento (com índice, nullable, não pode ser futura).
- `notes`: observações adicionais (string até 500 caracteres, opcional).
- `canceled_at`: data/hora de cancelamento (`TIMESTAMP`, nullable, com índice).
- `cancel_reason`: motivo opcional do cancelamento (`VARCHAR(255)`, nullable).
- `timestamps`: controle de data/hora de criação e alteração.

## Situação da Conta (Calculada em Tempo de Execução)
A situação da conta nunca é gravada como coluna estática, sendo deduzida a partir de `canceled_at`, `paid_at` e `due_date`:
1. **Cancelada**: `canceled_at` preenchido (tem prioridade máxima sobre as demais; badge neutra com valor riscado; nunca entra em totais, indicadores, "Em atenção", vencidas ou próximos vencimentos).
2. **Paga**: `canceled_at` nulo e `paid_at` preenchido (badge verde com ícone de confirmação). Contas pagas não podem ser canceladas sem antes reabri-las.
3. **Vencida**: `canceled_at` e `paid_at` nulos e `due_date` anterior ao dia atual (badge vermelho com ícone de alerta).
4. **Pendente**: `canceled_at` e `paid_at` nulos e `due_date` igual ou posterior ao dia atual (badge âmbar). Contas que vencem hoje são consideradas pendentes.

## Funcionalidades

### 1. Página Principal (`/modulos/contas-a-pagar`)
- **Indicadores gerais**:
  - Total em aberto em R$ (todas as contas com `paid_at` nulo).
  - Contas vencidas (quantidade e valor total em aberto com vencimento anterior a hoje; destaque em vermelho quando > 0).
  - Contas a vencer em até 7 dias (quantidade e valor das contas que vencem entre hoje e os próximos 7 dias).
- **Formulário de cadastro**:
  - Cadastro rápido de novas despesas com data de vencimento sugerida para o dia atual.
- **Seção "Em atenção"**:
  - Tabela com todas as contas em aberto que estão vencidas ou que vencem nos próximos 7 dias, ordenadas por data de vencimento.
  - Ação rápida "Pagar" para quitar no dia atual com apenas um clique.
  - Ação "Editar" para ajustes detalhados.

### 2. Área Avançada (`/modulos/contas-a-pagar/avancado`)
- Navegação por pílulas de Ano e Mês (período definido com base na data de vencimento `due_date`).
- Cards de resumo do período selecionado: Total no período, Total pago, Total pendente e Total vencido.
- Filtros interativos:
  - Por situação: Todas, Pendentes, Vencidas, Pagas.
  - Por texto: busca em tempo real na descrição da despesa.
- Tabela paginada (25 registros por página) com destaque visual suave em linhas vencidas.
- Botão "Nova conta" para cadastro com qualquer data de vencimento.

### 3. Ações e Edição
- **Marcar como paga (`payables.pay`)**:
  - Registra a data de liquidação como a data atual do servidor.
  - Se a conta já estiver paga, preserva a data original e exibe aviso amigável.
- **Reabertura de conta**:
  - Possível através da edição, limpando o campo "Data de pagamento".
- **Sem exclusão**:
  - O sistema preserva o histórico financeiro completo sem rota de exclusão física.
