# Módulo Vendas e Metas

O módulo de **Vendas e Metas** é responsável pelo registro diário de vendas, acompanhamento de expectativas e progresso em relação às metas estabelecidas.

## Funcionalidades

### 1. Página Principal (`/modulos/vendas`)
- **Registro de venda de hoje**:
  - Exibe formulário com valor em R$ para registrar a venda do dia corrente.
  - Impede duplicação: caso o registro de hoje já exista, exibe aviso e botão para edição.
  - Alerta `DIA SEM EXPECTATIVA` se não houver expectativa cadastrada para o dia (ou se for igual a 0).
- **Indicadores do mês**:
  - Total vendido no mês.
  - Meta acumulada até hoje (soma das expectativas do dia 1 até o dia atual).
  - Percentual atingido da meta acumulada (destaque visual: verde para >= 100%, azul para < 100%, neutro se sem meta).
- **Ações**:
  - Botão de acesso para "EXPECTATIVA DE VENDA MÊS".
  - Botão de acesso para "AVANÇADO".

### 2. Expectativas do Mês (`/modulos/vendas/expectativas`)
- Janela de seleção de 3 períodos: mês anterior, mês atual e mês seguinte (`UpdateSalesExpectationsRequest::allowedMonths()`).
- Tabela com todos os dias do mês selecionado.
- Validação estrita em Form Request (`UpdateSalesExpectationsRequest`): chaves devem ser datas válidas (`!Y-m-d`) pertencentes ao mês exibido; valores numéricos de `0` a `9.999.999.999,99` com até 2 casas decimais.
- Gravação atômica tudo-ou-nada via `upsert` dentro de `DB::transaction`. Valores em branco são salvos como `0,00`. Erros destacam o campo com borda `red6` e preservam o valor digitado via `old()`.
- Campos já cadastrados são carregados automaticamente para consulta e atualização.

### 3. Área Avançada (`/modulos/vendas/avancado`)
- Navegação por pílulas de Ano e Mês (períodos com movimentação ou mês corrente).
- Indicadores do período: total vendido, meta total do mês e percentual alcançado.
- Tabela analítica com todos os dias do mês:
  - Dias sem venda aparecem como `R$ 0,00` com marcação "sem registro" (sem gerar linhas vazias no banco).
  - Botão "EDITAR" disponível para datas passadas e data atual. Dias futuros não exibem opção de edição.

### 4. Edição de Venda Diária (`/modulos/vendas/dia/{date}/editar`)
- Permite editar ou lançar retroativamente a venda de qualquer dia até a data atual.
- Datas futuras são bloqueadas.
- Exibe a data e a expectativa do dia para consulta.

## Suporte a Moeda (`App\Support\Money`)
- Manipulação interna de valores em centavos (`int`) e colunas `DECIMAL(12,2)`.
- `parseToDecimal`: aceita formatos monetários brasileiros (`1.234,56`, `1234,56`), formato ponto (`1234.50`) e prefixo `R$`.
- `format`: converte centavos para string formatada `R$ X.XXX,XX`.
- `percentOf` e `formatPercent`: cálculo e formatação de percentuais com 1 casa decimal.
