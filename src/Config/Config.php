<?php

namespace Src\Config;

use Src\Traits\PropertiesApplier;

class Config
{
    use PropertiesApplier;

    public array $version;
    public bool $debug;
    public int $maxFileSize;
    public int $minFileSize;
    public array $allowedMimes;
    public array $allowedExtensions;

    public string $pageWithScheduleFiles;

    public array $samples;
    public array $groupsList;
    public array $days;
    public array $messagesOnSchedulePage;

    public array $dayWords;
    public array $timeWords;
    public array $skipCellsThatStartsWith;
    public array $mendeleeva4HouseCellColors;

    protected function __construct()
    {
        $this->applyFromArray([
            'version' => [
                'number' => '0.9',
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
            'days' => [
                'Понедельник',
                'Вторник',
                'Среда',
                'Четверг',
                'Пятница',
                'Суббота',
            ],
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
        ]);
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