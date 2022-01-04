<?php
declare(strict_types=1);

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
        static $configPropNames = null;

        if ($configPropNames === null) {
            $configPropNames = [];

            $publicProps = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($publicProps as $prop) {
                $configPropNames[] = $prop->getName();
            }
        }

        foreach ($configPropNames as $propName) {
            if ($this->$propName === null) {
                return false;
            }
        }

        return true;
    }
}