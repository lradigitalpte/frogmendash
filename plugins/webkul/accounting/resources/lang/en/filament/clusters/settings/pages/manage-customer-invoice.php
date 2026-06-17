<?php

return [
    'title' => 'Manage Customer Invoice',

    'form' => [
        'cash-rounding' => [
            'label'       => 'Cash Rounding',
            'helper-text' => 'Specify the lowest denomination of the currency accepted for cash payments.',
        ],

        'incoterm' => [
            'label' => 'Default Incoterm',
        ],

        'invoice-template' => [
            'label'       => 'Invoice Template',
            'helper-text' => 'The design used when previewing and printing customer invoices.',
        ],
    ],
];
