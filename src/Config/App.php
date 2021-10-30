<?php

namespace Src\Config;

class App
{
    public array $version = [
        'number' => '1.0.9',
        'stability' => 'beta'
    ];
    public bool $debug = false;
    public bool $forceConsoleMode = false;

    /*
     * System
     */
    public bool $enableSystemPages = true;
    public string $hitsStorageFile = ROOT . '/src/storage/hits.csv';

    public int $maxFileSize = 512; // in kilobytes
    public int $minFileSize = 25; // in kilobytes
    public array $allowedMimes = [
        'application/vnd.ms-excel', // xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
    ];
    public array $allowedExtensions = ['.xls', '.xlsx'];

    public string $pageWithScheduleFiles = 'http://www.belsk.ru/p12321aa3.html';

    public array $samples = [
        '1.xls',
        '2.xls',
        '3.xls',
    ];
    public array $groupsList; // initialized in constructor
    public array $messagesOnSchedulePage = [
        [
            'type' => 'warning',
            'content' => 'Сервис в тестовом режиме - могут быть ошибки.',
        ],
    ];

    public array $dayWords = ['день недели', 'дни'];
    public array $timeWords = ['часы', 'пара'];
    public array $skipCellsThatStartsWith = ['Цветом отмечены'];
    public array $mendeleeva4HouseCellColors = ['000000'];
    public string $mendeleeva4KeywordInFilename = 'менделеева';
    public array $mendeleeva4KeywordsInSheetCell = ['менделеева', '4'];
    public string $classHourCellKeyword = 'классный час';

    protected function __construct()
    {
        $this->groupsList = require ROOT . '/src/Config/group-list.php';
    }

    /*
     * Singleton stuff
     */

    private static $instances = [];

    protected function __clone() { }

    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }

    public static function getInstance(): self
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}