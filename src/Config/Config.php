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
    public array $messagesOnSchedule;

    public array $dayWords;
    public array $timeWords;
    public array $skipCellsThatStartsWith;
    public array $mendeleeva4HouseCellColors;

    protected function __construct()
    {
        $this->applyFromArray([
            'version' => [
                'number' => '0.8',
                'stability' => 'beta'
            ],
            'debug' => true,
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
            'groupsList' => $this->getGroupsList(),
            'days' => [
                'Понедельник',
                'Вторник',
                'Среда',
                'Четверг',
                'Пятница',
                'Суббота',
            ],
            'messagesOnSchedule' => [
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

    /**
     * @return string[]
     */
    private function getGroupsList(): array
    {
        return [
            0 => 'ДС-11',
            1 => 'ДС-21',
            2 => 'ДС-31',
            3 => 'ДТО-31',
            4 => 'КИП-11',
            5 => 'КИП-21',
            6 => 'КИП-31',
            7 => 'МКИП-11',
            8 => 'МКИП-21',
            9 => 'ПС-11',
            10 => 'ПС-21',
            11 => 'ПС-31',
            12 => 'ПС-41',
            13 => 'С-11',
            14 => 'С-12',
            15 => 'С-13',
            16 => 'С-21',
            17 => 'С-22',
            18 => 'С-23',
            19 => 'С-31',
            20 => 'С-32',
            21 => 'С-33',
            22 => 'С-41',
            23 => 'С-42',
            24 => 'СВ-11',
            25 => 'СВ-21',
            26 => 'СВ-31',
            27 => 'Т-11',
            28 => 'Т-12',
            29 => 'Т-21',
            30 => 'Т-31',
            31 => 'Т-41',
            32 => 'ТД-11',
            33 => 'ТД-12',
            34 => 'ТД-13',
            35 => 'ТД-21',
            36 => 'ТД-22',
            37 => 'ТД-23',
            38 => 'ТД-31',
            39 => 'ТД-32',
            40 => 'ТД-33',
            41 => 'ТД-41',
            42 => 'ТД-42',
            43 => 'ТДО-11',
            44 => 'ТДО-21',
            45 => 'ТДО-31',
            46 => 'ТК-11',
            47 => 'ТК-21',
            48 => 'ТК-31',
            49 => 'ТК-32',
            50 => 'ТК-41',
            51 => 'ТК-42',
            52 => 'ТО-31',
            53 => 'ТО-41',
            54 => 'ТО-42',
            55 => 'ТО-52',
            56 => 'Э-11',
            57 => 'Э-12',
            58 => 'Э-21',
            59 => 'Э-22',
            60 => 'Э-31',
            61 => 'Э-32',
            62 => 'Э-41',
            63 => 'ЭМ-11',
            64 => 'ЭМ-21',
            65 => 'ЭМ-31',
            66 => 'ЭМД-11',
        ];
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