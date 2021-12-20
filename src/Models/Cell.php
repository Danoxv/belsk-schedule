<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\Exception;
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
     * @param Sheet $sheet
     */
    public function __construct(string $coordinate, Sheet $sheet)
    {
        $this->sheet = $sheet;

        $this->init($coordinate);
    }

    /**
     * @param string $coordinate
     */
    private function init(string $coordinate): void
    {
        $this->setCoordinate($coordinate);
        $this->cell     = $this->sheet->getWorksheet()->getCell($this->coordinate);
        $this->rawValue = Security::sanitizeString((string) $this->cell);
        $this->value    = trim($this->rawValue);
        $this->isEmpty  = empty($this->value);
    }

    /**
     * WARNING: High-cost operations was performed,
     * call processing only in necessary cases.
     */
    public function process(): void
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
    public function getSheet(): Sheet
    {
        return $this->sheet;
    }

    /**
     * @return bool
     */
    public function isInvisible(): bool
    {
        return $this->isInvisible;
    }

    /**
     * @return void
     * @throws Exception
     */
    private function resolveIsInvisible(): void
    {
        // Using a lookup cache adds a slight memory overhead, but boosts speed
        // caching using a static within the method is faster than a class static,
        // though it's additional memory overhead
        /** @var bool[] */
        static $invisibleCellsCache = [];

        $cacheKey = $this->getCoordinate() . '__' . $this->getSheet()->getId();

        if (isset($invisibleCellsCache[$cacheKey])) {
            $this->isInvisible = $invisibleCellsCache[$cacheKey];
            return;
        }

        // В ячейке есть значение
        if ($this->getValue(true)) {
            $this->isInvisible = false;
            $invisibleCellsCache[$cacheKey] = $this->isInvisible;
            return;
        }

        $range = $this->cell->getMergeRange();

        // Ячейка не объединена
        if (!$range) {
            $this->isInvisible = false;
            $invisibleCellsCache[$cacheKey] = $this->isInvisible;
            return;
        }

        $prevRowCell = $this->sheet->getWorksheet()->getCell($this->column.Coordinate::prevRow($this->row));
        $prevRowRange = $prevRowCell->getMergeRange();

        // Ячейка объединена не с ячейкой на предыдущей строке
        if ($range !== $prevRowRange) {
            $this->isInvisible = false;
            $invisibleCellsCache[$cacheKey] = $this->isInvisible;
            return;
        }

        // Похоже, что ячейка невидима... Но это неточно.
        $this->isInvisible = true;
        $invisibleCellsCache[$cacheKey] = $this->isInvisible;
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

    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return int
     */
    public function getRow(): int
    {
        return $this->row;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    /**
     * @return string
     */
    public function getEndColorRgb(): string
    {
        return $this->cell->getStyle()->getFill()->getEndColor()->getRGB();
    }

    private function setCoordinate(string $coordinate): void
    {
        [$this->column, $this->row] = Coordinate::explodeCoordinate($coordinate);
        
        $this->coordinate = $this->column . $this->row;
    }
}
