<?php

namespace Src\Config;

class ExcelConfig
{
    public $dayCol = null;
    public $timeCol = null;
    public $firstGroupCol = null;

    public $groupNamesRow = null;
    public $firstScheduleRow = null;

    public $classHourLessonColumn = null;

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