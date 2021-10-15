<?php

namespace Src\Models;

use Src\Support\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell as PhpSpreadsheetCell;
use Src\Support\Security;

class Cell
{
    private string $coordinate;
    private string $column;
    private int $row;

    private string $rawValue;
    private string $value;
    private bool $isEmpty;

    private bool $isProcessed = false;

    private PhpSpreadsheetCell $cell;
    private Sheet $sheet;

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
        $this->cell     = $this->sheet->getWorksheet()->getCell($this->coordinate);
        $this->rawValue = Security::sanitize((string) $this->cell);
        $this->value    = trim($this->rawValue);
        $this->isEmpty  = empty($this->value);
    }

    public function process()
    {
        $this->resolveIsInvisible();

        $this->isProcessed = true;
    }

    /**
     * @return string
     */
    public function getCoordinate(): string
    {
        return $this->coordinate;
    }

    /**
     * @return Sheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    public function isInvisible(): bool
    {
        return $this->isInvisible;
    }

    private function resolveIsInvisible()
    {
        // В ячейке есть значение
        if ($this->getValue(true)) {
            $this->isInvisible = false;
            return;
        }

        $range = $this->cell->getMergeRange();

        // Ячейка не объединена
        if (!$range) {
            $this->isInvisible = false;
            return;
        }

        $prevRowCell = $this->sheet->getWorksheet()->getCell($this->column.Coordinate::prevRow($this->row));
        $prevRowRange = $prevRowCell->getMergeRange();

        // Ячейка объединена не с ячейкой на предыдущей строке
        if ($range !== $prevRowRange) {
            $this->isInvisible = false;
            return;
        }

        // Похоже, что ячейка невидима... Но это неточно.
        $this->isInvisible = true;
    }

    /**
     * @param bool $rawValue
     * @return string
     */
    public function getValue(bool $rawValue = false): string
    {
        if ($rawValue) {
            return $this->rawValue;
        }

        return $this->value;
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

    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    public function getEndColorRgb(): string
    {
        return $this->cell->getStyle()->getFill()->getEndColor()->getRGB();
    }

    private function setCoordinate(string $coordinate)
    {
        $this->coordinate = $coordinate;

        [$this->column, $this->row] = Coordinate::explodeCoordinate($this->coordinate);
    }
}