<?php

namespace Src\Models;

use Src\Support\Collection;

class Group
{
    private string $column;
    private string $name;
    private Sheet $sheet;

    private Collection $pairs;

    public function __construct(string $column, string $name, Sheet $sheet)
    {
        $this->column = $column;
        $this->name = $name;
        $this->sheet = $sheet;
        $this->pairs = new Collection();
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

            if ($pairCell->isEmpty() /* and it's not "class hour" lesson */) {
                continue;
            }

            $pair = new Pair($pairCell, $this);

            $this->pairs->put($pairCellCoordinate, $pair);
        }
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getSheet(): Sheet
    {
        return $this->sheet;
    }
}