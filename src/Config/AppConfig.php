<?php

namespace Src\Config;

use Src\Traits\PropertiesApplier;

class AppConfig
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
    public string $mendeleeva4KeywordInFilename;
    public array $mendeleeva4KeywordsInSheetCell;

    protected function __construct()
    {
        $this->applyFromArray(require ROOT . '/src/Config/app.php');
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