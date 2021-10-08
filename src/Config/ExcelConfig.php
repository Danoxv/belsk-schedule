<?php

namespace Src\Config;

use Src\Models\Sheet;

class ExcelConfig
{
    public ?string $dayCol = null;
    public ?string $timeCol = null;
    public ?int $groupNamesRow = null;

    public ?string $firstGroupCol = null;
    public ?string $lastGroupCol = null;
    public ?int $firstScheduleRow = null;
    public ?int $lastScheduleRow = null;
    /** @var string|bool|null */
    public $classHourLessonColumn = null;

    private Sheet $sheet;

    public function __construct(Sheet $sheet)
    {
        $this->sheet = $sheet;
    }

    /**
     * @return bool
     */
    public function isProcessable(): bool
    {
        $configProps = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($configProps as $prop) {
            if ($this->{$prop->getName()} === null) {
                return false;
            }
        }

        return true;
    }
}