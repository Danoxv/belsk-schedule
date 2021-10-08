<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell as PhpSpreadsheetCell;

class Cell
{
    private string $coordinate;
    private string $column;
    private int $row;

    private string $rawValue;
    private bool $isEmpty;

    private PhpSpreadsheetCell $cell;
    private Sheet $sheet;
    private ?Lesson $lesson = null;

    private bool $isInvisible;

    /**
     * @param string $coordinate
     * @param $sheet
     */
    public function __construct(string $coordinate, $sheet)
    {
        $this->sheet = $sheet;

        $this->init($coordinate);
    }

    private function init(string $coordinate)
    {
        $this->setCoordinate($coordinate);
        $this->cell = $this->sheet->getWorksheet()->getCell($this->coordinate);
        $this->rawValue = (string) $this->cell;
        $this->resolveIsInvisible();
        $this->isEmpty = empty($this->getValue());
    }

    /**
     * @return Sheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param Lesson $lesson
     */
    public function setLesson(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    /**
     * @return Lesson|null
     */
    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    /**
     * @return bool
     */
    public function isLesson(): bool
    {
        return $this->lesson !== null;
    }

    public function isInvisible(): bool
    {
        return $this->isInvisible;
    }

    private function resolveIsInvisible()
    {
        // В ячейке есть значение
        if ($this->getValue(true)) {
            return false;
        }

        $range = $this->cell->getMergeRange();

        // Ячейка не объединена
        if (!$range) {
            return false;
        }

        $prevRowCell = $this->sheet->getWorksheet()->getCell($this->column.prevRow($this->row));
        $prevRowRange = $prevRowCell->getMergeRange();

        // Ячейка объединена не с ячейкой на предыдущей строке
        if ($range !== $prevRowRange) {
            return false;
        }

        // Похоже, что ячейка невидима... Но это неточно.
        return true;
    }

    /**
     * @param bool $rawValue
     * @return string
     */
    public function getValue(bool $rawValue = false): string
    {
        $cellValue = $this->rawValue;

        if ($rawValue) {
            return $cellValue;
        }

        return trim($cellValue);
    }

    public function __toString()
    {
        return $this->getValue();
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return false|string
     */
    public function getMergeRange()
    {
        return $this->cell->getMergeRange();
    }

    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    private function setCoordinate(string $coordinate)
    {
        $this->coordinate = $coordinate;

        [$this->column, $this->row] = Coordinate::coordinateFromString($this->coordinate);
    }
}