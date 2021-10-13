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

    private function init()
    {
        // Resolve name
        $this->name = $this->sheet->getCellValue(
            $this->column . $this->sheet->getGroupNamesRow()
        );
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
     * @return Collection
     */
    public function getPairs(): Collection
    {
        return $this->pairs;
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
}