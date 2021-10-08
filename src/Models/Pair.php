<?php

namespace Src\Models;

use Src\Support\Collection;

class Pair
{
    /** @var Cell */
    private Cell $cell;

    /** @var Group */
    private Group $group;

    /** @var Collection */
    private Collection $lessons;

    public function __construct(Cell $cell, Group $group)
    {
        $this->cell = $cell;
        $this->group = $group;
        $this->lessons = new Collection();

        $this->init();
    }

    public function getCell(): Cell
    {
        return $this->cell;
    }

    /**
     * Find and add Lessons.
     */
    private function init()
    {
        $row1 = $this->cell->getRow();
        $row2 = nextRow($row1);

        $lesson1 = new Lesson($this, $row1);
        $lesson2 = new Lesson($this, $row2);

        // Todo set first/second week, is class hour

        $this->lessons->push([$lesson1, $lesson2]);
    }
}