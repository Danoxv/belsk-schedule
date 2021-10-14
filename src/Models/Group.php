<?php

namespace Src\Models;

use Src\Support\Collection;

class Group
{
    private string $column;
    private string $name;
    private Sheet $sheet;

    private Collection $pairs;

    public function __construct(string $column, Sheet $sheet)
    {
        $this->column = $column;
        $this->sheet = $sheet;
        $this->pairs = new Collection();

        $this->init();
    }

    /**
     * Recognize and add Pairs
     *
     * @param int[] $rows
     */
    public function process(array $rows)
    {
        foreach ($rows as $row) {
            $timeCol = $this->sheet->getTimeColumn();

            $pairCellCoordinate = $timeCol . $row;
            $pairCell = new Cell($pairCellCoordinate, $this->getSheet());

            $pair = new Pair($pairCell, $this);

            if ($pair->isValid()) {
                $this->pairs->put($pairCellCoordinate, $pair);
            }
        }
    }

    /**
     * @param string $day
     * @return Collection
     */
    public function getPairsByDay(string $day): Collection
    {
        return $this->pairs->filter(function (Pair $pair) use ($day) {
           return $pair->getDay() === $day;
        });
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return Sheet
     */
    public function getSheet(): Sheet
    {
        return $this->sheet;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    private function init()
    {
        // Resolve name
        $this->name = $this->sheet->getCellValue(
            $this->column . $this->sheet->getGroupNamesRow()
        );
    }
}