<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Src\Config\Config;
use Src\Config\ExcelConfig;
use Src\Support\Str;

class Sheet
{
    private Worksheet $worksheet;
    private ExcelConfig $excelConfig;
    private array $cells = [];

    private int $firstRow = 1;
    private int $lastRow;

    private string $firstColumn = 'A';
    private string $lastColumn;

    /**
     * @param Worksheet $worksheet
     */
    public function __construct(Worksheet $worksheet)
    {
        $this->worksheet = clone $worksheet;
        $this->resolveLastRowAndColumn();
        $this->resolveExcelConfig();
    }

    public function getWorksheet(): Worksheet
    {
        return $this->worksheet;
    }

    public function getTitle()
    {
        return trim($this->worksheet->getTitle());
    }

    public function addCell(string $coordinate)
    {
        $cellModel = new Cell($coordinate, $this);

        $cellModel->setLesson(new Lesson($cellModel));

        $this->cells[] = $cellModel;
    }

    /**
     * @return Cell[]
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * @param string $coordinate
     * @param bool $rawValue
     * @return string
     */
    public function getCellValue(string $coordinate, bool $rawValue = false): string
    {
        $cell = $this->cells[$coordinate] ?? $this->worksheet->getCell($coordinate);

        $cellValue = (string) $cell;

        if ($rawValue) {
            return $cellValue;
        }

        return trim($cellValue);
    }

    public function getExcelConfig(): ExcelConfig
    {
        return $this->excelConfig;
    }

    public function isProcessable(): bool
    {
        return $this->excelConfig->isProcessable();
    }

    /**
     * @param string|null $start
     * @param string|null $end
     * @return array
     */
    public function getColumnsRange(string $start = null, string $end = null): array
    {
        $start = $start ?? $this->firstColumn;
        $end = $end ?? $this->lastColumn;

        $end++;
        $letters = [];
        while ($start !== $end) {
            $letters[] = $start++;
        }
        return $letters;
    }

    /**
     * @param int|null $start
     * @param int|null $end
     * @return array
     */
    public function getRowsRange(int $start = null, int $end = null): array
    {
        $start = $start ?? $this->firstRow;
        $end = $end ?? $this->lastRow;

        return range($start, $end);
    }

    private function resolveExcelConfig()
    {
        $config = Config::getInstance();

        $dayWords = $config->dayWords;
        $timeWords = $config->timeWords;

        $excelConfig = new ExcelConfig();

        $columns = $this->getColumnsRange();
        $rows = $this->getRowsRange();

        foreach ($columns as $column) {
            foreach ($rows as $row) {
                if ($excelConfig->isProcessable()) {
                    break(2);
                }

                $rawCellValue = $this->getCellValue($column.$row, true);

                $cellValue = Str::lower(Str::replaceManySpacesWithOne($rawCellValue));

                if (in_array($cellValue, $dayWords)) {
                    $excelConfig->dayCol           = $column;
                    $excelConfig->groupNamesRow    = $row;
                    $excelConfig->firstScheduleRow = nextRow($excelConfig->groupNamesRow);
                } elseif (in_array($cellValue, $timeWords)) {
                    $excelConfig->timeCol          = $column;
                    $excelConfig->firstGroupCol    = nextColumn($excelConfig->timeCol);
                    $excelConfig->groupNamesRow    = $row;
                    $excelConfig->firstScheduleRow = nextRow($excelConfig->groupNamesRow);
                } else if (isClassHourLesson($rawCellValue)) {
                    $excelConfig->classHourLessonColumn = $column;
                }
            }
        }

        if ($excelConfig->classHourLessonColumn === null) {
            $excelConfig->classHourLessonColumn = false;
        }

        $this->excelConfig = $excelConfig;
    }

    private function resolveLastRowAndColumn()
    {
        $highestColRow = $this->worksheet->getHighestRowAndColumn();
        $this->lastColumn = $highestColRow['column'];
        $this->lastRow = $highestColRow['row'];
    }
}