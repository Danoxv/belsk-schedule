<?php
declare(strict_types=1);

namespace Src\Models;

use Src\Support\Collection;
use Src\Support\Helpers;

class Group
{
    private string $column;
    private string $name;
    private Sheet $sheet;
    private bool $isProcessed = false;
    private bool $isValid;

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
    public function process(array $rows): void
    {
        $timeCol = $this->sheet->getTimeColumn();
        foreach ($rows as $row) {
            $pairTimeCellCoord = $timeCol . $row;
            $pairTimeCell = $this->getSheet()->getCell($pairTimeCellCoord);

            $pair = new Pair($pairTimeCell, $this);

            if ($pair->isValid()) {
                $this->pairs->put($pairTimeCellCoord, $pair);
            }
        }

        $this->isProcessed = true;

        if($this->pairs->isEmpty()) {
            return;
        }

        // Assign pair number if not parsed from Excel.
        if ($this->pairs->first()->getNumber()) {
            // Optimization: if first pair have number,
            // assume that all pairs have too.
            return;
        }

        foreach (Day::getAll() as $day) {
            $pairNum = 1;

            /** @var Pair $pair */
            foreach ($this->getPairsByDay($day) as $pair) {
                if (!$pair->getNumber()) {
                    $pair->setNumber(Helpers::intToRoman($pairNum));
                }
                $pairNum++;
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

    public function isValid(): bool
    {
        return $this->isValid;
    }

    private function init(): void
    {
        // Resolve name
        $this->name = $this->sheet->getCellValue(
            $this->column . $this->sheet->getGroupNamesRow()
        );

        // Resolve isValid
        $this->isValid = $this->name !== '';
    }
}
