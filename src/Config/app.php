<?php

return [
    'version' => [
        'number' => '1.0.5',
        'stability' => 'beta'
    ],
    'debug' => false,
    'maxFileSize' => 512, // in kilobytes
    'minFileSize' => 25, // in kilobytes
    'allowedMimes' => [
        'application/vnd.ms-excel', // xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
    ],
    'allowedExtensions' => ['.xls', '.xlsx'],

    'pageWithScheduleFiles' => 'http://www.belsk.ru/p12321aa3.html',

    'samples' => [
        '1.xls',
        '2.xls',
        '3.xls',
    ],
    'groupsList' => require ROOT . '/src/Config/group-list.php',
    'messagesOnSchedulePage' => [
        [
            'type' => 'warning',
            'content' => 'Сервис в тестовом режиме - могут быть ошибки.',
        ],
    ],

    'dayWords' => ['день недели', 'дни'],
    'timeWords' => ['часы', 'пара'],
    'skipCellsThatStartsWith' => ['Цветом отмечены'],
    'mendeleeva4HouseCellColors' => ['000000'],
    'mendeleeva4KeywordInFilename' => 'менделеева',
    'mendeleeva4KeywordsInSheetCell' => ['менделеева', '4'],
    'classHourCellKeyword' => 'классный час',
];