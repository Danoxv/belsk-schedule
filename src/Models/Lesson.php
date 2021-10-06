<?php

namespace Src\Models;

class Lesson
{
    private Cell $cell;

    /**
     * @var string
     */
    public string $group;

    public function __construct(Cell $cell)
    {
        $this->cell = $cell;

        $this->group = $this->cell->getSheet()->getLessonGroupByColumn(
            $this->cell->getColumn()
        );
    }

    public function getCell()
    {
        return $this->cell;
    }

    public function isFirstWeek(): bool
    {
        return false;
    }

    public function isSecondWeek(): bool
    {
        return false;
    }

    public function isFirstAndSecondWeek(): bool
    {
        return true;
    }

    public function isCommon(): bool
    {
        return false;
    }
}