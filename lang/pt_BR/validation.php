<?php

return [
    'decimal' => 'O campo :attribute deve ser um valor numérico válido com até 2 casas decimais.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número válido.',
    'required' => 'O campo :attribute é obrigatório.',
    'unique' => 'Este :attribute já foi registrado.',
    'date' => 'O campo :attribute não é uma data válida.',
    'date_format' => 'O campo :attribute não corresponde ao formato :format.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'digits' => 'O campo :attribute deve ter exatamente :digits dígitos.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'string' => 'O campo :attribute deve ser um texto.',
    'nullable' => '',

    'attributes' => [
        'amount' => 'valor',
        'sale_date' => 'data da venda',
        'amount.*' => 'valor',
        'expectations' => 'expectativas',
        'issue_date' => 'data de emissão',
        'invoice_number' => 'número da nota fiscal',
        'customer_name' => 'nome do cliente',
        'customer_cnpj' => 'CNPJ',
        'notes' => 'observações',
    ],
];
