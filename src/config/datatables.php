<?php

return [

    'models_namespace' => 'App\Models\\',

    'routes' => [
        'prefix' => 'datatable',
        'name' => 'datatable',
    ],

    'middleware' => [
        'web',
        // 'auth',
    ],

    'filters' => [
        'date_format' => 'd/m/Y',
        'date_delimiter' => '-dateDelimiter-',
        'date_field_prefix' => [
            'prefix' => 'date_field',
            'delimiter' => '##',
        ],
        'null_delimiter' => '-nullDelimiter-',
    ],

];
