# Módulo Notas Fiscais

O módulo de **Notas Fiscais** permite a organização e o acompanhamento das notas fiscais emitidas para clientes, com registro de numeração, cliente, CNPJ e valores.

## Banco de Dados (`invoices`)
- `id`: identificador sequencial.
- `issue_date`: data de emissão (com índice). Não aceita datas futuras.
- `invoice_number`: número da nota fiscal (string até 40 caracteres, nullable, único quando preenchido).
- `customer_name`: razão social ou nome do cliente (string até 150 caracteres).
- `customer_cnpj`: CNPJ do cliente (string com 14 dígitos numéricos, opcional, exibido com máscara `00.000.000/0000-00`).
- `amount`: valor monetário da nota (`DECIMAL(12,2)`, mínimo `0,01`).
- `notes`: observações adicionais (string até 500 caracteres, opcional).
- `timestamps`: controle de data/hora de criação e alteração.

## Funcionalidades

### 1. Página Principal (`/modulos/notas-fiscais`)
- **Indicadores do mês**:
  - Total emitido no mês em R$.
  - Quantidade total de notas emitidas no mês.
  - Total e quantidade de notas emitidas no dia atual.
- **Formulário de cadastro**:
  - Permite emissão rápida para o dia corrente ou datas anteriores.
  - Suporta múltiplas notas para um mesmo dia (sem restrição UNIQUE de data).
- **Lista de notas de hoje**:
  - Exibição de todas as notas fiscais emitidas hoje com valores, cliente, CNPJ e observações.
  - Link direto para edição de cada registro.
  - Exibição de estado vazio amigável caso não haja emissões hoje.

### 2. Área Avançada (`/modulos/notas-fiscais/avancado`)
- Navegação mensal e anual por pílulas (períodos com notas registradas unificados ao mês atual).
- Indicadores do período: quantidade total, valor total e ticket médio (`total / quantidade`).
- Busca por texto por nome do cliente ou número da nota fiscal via query string.
- Tabela paginada com ordenação decrescente por data e ID (25 registros por página).
- Botão "Nova nota" com atalho para o formulário de cadastro livre.

### 3. Edição (`/modulos/notas-fiscais/{invoice}/editar`)
- Formulário para atualização de todos os campos da nota.
- Validação de unicidade do número da nota ignora o próprio registro em edição.
- Sem exclusão física de registros (conforme requisitos de integridade do sistema).
