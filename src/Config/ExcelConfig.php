<?php

namespace Src\Config;

use Src\Models\Sheet;

class ExcelConfig
{
    public $dayCol = null;
    public $timeCol = null;
    public $groupNamesRow = null;

    public $firstGroupCol = null;
    public $lastGroupCol = null;
    public $firstScheduleRow = null;
    public $lastScheduleRow = null;

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