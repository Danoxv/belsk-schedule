<?php

namespace Src\Config;

class SheetConfig
{
    public ?string $dayCol = null;
    public ?string $timeCol = null;
    public ?int $groupNamesRow = null;

    public ?string $firstGroupCol = null;
    public ?string $lastGroupCol = null;
    public ?int $firstScheduleRow = null;
    public ?int $lastScheduleRow = null;
    /** @var string|false|null */
    public $classHourLessonColumn = null;

    /**
     * @return bool
     */
    public function isProcessable(): bool
    {
        static $configProps = null;

        if ($configProps === null) {
            $configProps = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        }

        foreach ($configProps as $prop) {
            if ($this->{$prop->getName()} === null) {
                return false;
            }
        }

        return true;
    }
}