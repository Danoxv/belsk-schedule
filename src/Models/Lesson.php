<?php

namespace Src\Models;

class Lesson
{
    private Pair $pair;
    private int $row;
    private Cell $cell;

    private bool $isValid = true;

    public function __construct(Pair $pair, int $row)
    {
        $this->pair = $pair;
        $this->row = $row;

        $this->init();
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

    public function isClassHour(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return string
     */
    public function getCoordinate(): string
    {
        return $this->cell->getCoordinate();
    }

    private function init()
    {
        $this->cell = new Cell(
            $this->pair->getGroup()->getColumn() . $this->row,
            $this->pair->getSheet()
        );

        // Lesson with invisible cell can't be a valid lesson.
        if ($this->cell->isInvisible()) {
            $this->isValid = false;
            return;
        }

        // Other stuff...
    }
}