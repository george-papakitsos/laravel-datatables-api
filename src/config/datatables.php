<?php

return [

    'models_namespace' => 'App\\Models\\',

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
        'null_delimiter' => '-nullDelimiter-',
        'null_delimiter_text' => '-',
    ],

];
