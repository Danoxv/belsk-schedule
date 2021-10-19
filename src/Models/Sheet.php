<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Src\Config\AppConfig;
use Src\Config\ExcelConfig;
use Src\Config\SheetProcessingConfig;
use Src\Support\Arr;
use Src\Support\Collection;
use Src\Support\Coordinate;
use Src\Support\Security;
use Src\Support\Str;

class Sheet
{
    private Worksheet $worksheet;
    private AppConfig $config;
    private ExcelConfig $excelConfig;
    private SheetProcessingConfig $sheetProcessingConfig;

    private bool $isProcessed = false;

    private bool $hasMendeleeva4 = false;

    private string $title;

    private Collection $groups;

    /** @var string[] */
    private array $coordinatesForSkip = [];

    /**
     * @param Worksheet $worksheet
     * @param SheetProcessingConfig $sheetProcessingConfig
     */
    public function __construct(Worksheet $worksheet, SheetProcessingConfig $sheetProcessingConfig)
    {
        $this->worksheet                = $worksheet;

        $this->config                   = AppConfig::getInstance();
        $this->sheetProcessingConfig    = $sheetProcessingConfig;
        $this->excelConfig              = new ExcelConfig($this);

        $this->groups                   = new Collection();

        $this->init();
        $this->process();
    }

    /**
     * @return Worksheet
     */
    public function getWorksheet(): Worksheet
    {
        return $this->worksheet;
    }

    /**
     * @param string $coordinate
     * @param bool $rawValue
     * @return string
     */
    public function getCellValue(string $coordinate, bool $rawValue = false): string
    {
        return (new Cell($coordinate, $this))->getValue($rawValue);
    }

    /**
     * @return bool
     */
    private function isProcessable(): bool
    {
        return $this->excelConfig->isProcessable();
    }

    /**
     * @return string|null
     */
    public function getTimeColumn(): ?string
    {
        return $this->excelConfig->timeCol;
    }

    /**
     * @return bool
     */
    public function hasGroups(): bool
    {
        return $this->groups->isNotEmpty();
    }

    /**
     * @return Collection
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * @return ?Group
     */
    public function getFirstGroup(): ?Group
    {
        return $this->groups->first();
    }

    /**
     * @return string|null
     */
    public function getDayCol(): ?string
    {
        return $this->excelConfig->dayCol;
    }

    /**
     * @return int|null
     */
    public function getGroupNamesRow(): ?int
    {
        return $this->excelConfig->groupNamesRow;
    }

    /**
     * @return bool
     */
    public function hasClassHourLessonColumn(): bool
    {
        return !empty($this->excelConfig->classHourLessonColumn);
    }

    /**
     * @return string|null
     */
    public function getClassHourLessonColumn(): ?string
    {
        return empty($this->excelConfig->classHourLessonColumn) ? null : $this->excelConfig->classHourLessonColumn;
    }

    public function hasMendeleeva4(): bool
    {
        return $this->hasMendeleeva4;
    }

    /**
     * @return bool
     */
    public function needForceApplyMendeleeva4(): bool
    {
        return !empty($this->sheetProcessingConfig->forceApplyMendeleeva4ToLessons);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
       return $this->title;
    }

    /**
     * Get processable (potentially with lessons)
     * columns range.
     *
     * @return array
     */
    private function getColumnsRange(): array
    {
        $start = $this->excelConfig->firstGroupCol;
        $end = $this->excelConfig->lastGroupCol;

        return Coordinate::generateColumnsRange($start, $end);
    }

    /**
     * Get processable (potentially with lessons)
     * rows range.
     *
     * @return int[]
     */
    private function getRowsRange(): array
    {
        $start = $this->excelConfig->firstScheduleRow;
        $end = $this->excelConfig->lastScheduleRow;

        return Coordinate::generateRowsRange($start, $end);
    }

    /**
     * Resolve Excel config, detect "Has Mendeleeva 4 house".
     */
    private function init()
    {
        $this->title = trim(Security::sanitizeString($this->worksheet->getTitle()));

        $firstColumn = 'A';
        $firstRow = 1;

        $highestColRow = $this->worksheet->getHighestRowAndColumn();
        $highestColumn = $highestColRow['column'];
        $highestRow = $highestColRow['row'];

        $columns = Coordinate::generateColumnsRange($firstColumn, $highestColumn);
        $rows = Coordinate::generateRowsRange($firstRow, $highestRow);

        $excelConfig = &$this->excelConfig;

        foreach ($columns as $column) {
            foreach ($rows as $row) {
                $coordinate = $column.$row;

                $rawCellValue = $this->getCellValue($coordinate, true);
                $cellValue = trim($rawCellValue);

                if (Str::startsWith($cellValue, $this->config->skipCellsThatStartsWith)) {
                    $this->coordinatesForSkip[] = $coordinate;
                }

                /*
                 * Resolve Excel config
                 */

                if (!$excelConfig->isProcessable()) {
                    $cleanCellValue = Str::lower(Str::replaceManySpacesWithOne($cellValue));

                    if (in_array($cleanCellValue, $this->config->dayWords)) {
                        $excelConfig->dayCol           = $column;
                        $excelConfig->groupNamesRow    = $row;
                        $excelConfig->firstScheduleRow = Coordinate::nextRow($excelConfig->groupNamesRow);
                    } elseif (in_array($cleanCellValue, $this->config->timeWords)) {
                        $excelConfig->timeCol          = $column;
                        $excelConfig->firstGroupCol    = Coordinate::nextColumn($excelConfig->timeCol);
                        $excelConfig->groupNamesRow    = $row;
                        $excelConfig->firstScheduleRow = Coordinate::nextRow($excelConfig->groupNamesRow);
                    } else if (empty($excelConfig->classHourLessonColumn) && Lesson::isClassHourLesson($cellValue)) {
                        $excelConfig->classHourLessonColumn = $column;
                    }
                }

                /*
                 * Detect "Has Mendeleeva 4 house"
                 */

                if ($this->sheetProcessingConfig->detectMendeleeva4) {
                    if ($this->sheetProcessingConfig->forceApplyMendeleeva4ToLessons) {
                        $this->hasMendeleeva4 = true;
                    }

                    if (!$this->hasMendeleeva4 && $cellValue) {
                        if (Str::containsAll(Str::lower($cellValue), $this->config->mendeleeva4KeywordsInSheetCell)) {
                            $this->hasMendeleeva4 = true;
                        }
                    }
                }
            }
        }

        $excelConfig->lastGroupCol = $highestColumn;
        $excelConfig->lastScheduleRow = $highestRow;
        if ($excelConfig->classHourLessonColumn === null) {
            $excelConfig->classHourLessonColumn = false;
        }
    }

    /**
     * Start Sheet processing:
     * recognize and add Groups.
     */
    private function process()
    {
        if (!$this->isProcessable()) {
            return;
        }

        $conf = &$this->sheetProcessingConfig;

        $columns = $this->getColumnsRange();
        $rows = $conf->processGroups ? $this->getRowsRange() : [];

        $hasFilterByGroup = $conf->studentsGroup !== null;

        foreach ($columns as $column) {
            // Optimization: we are already found and processed selected group.
            if ($hasFilterByGroup && $this->hasGroups()) {
                break;
            }

            $group = new Group($column, $this);

            // Apply filter by student's group
            if ($hasFilterByGroup && $conf->studentsGroup !== $group->getName()) {
                continue;
            }

            // Add group
            if ($conf->processGroups) {
                $processableRows = $this->getProcessableRows($rows, $column);
                $group->process($processableRows);
            }
            $this->groups->put($column, $group);
        }

        // All done, mark sheet as processed
        $this->isProcessed = true;
    }

    /**
     * @param array $rows
     * @param string $currentColumn
     * @return array
     */
    private function getProcessableRows(array $rows, string $currentColumn): array
    {
        $rowsWasRemoved = false;

        foreach ($this->coordinatesForSkip as $coordinate) {
            [$column, $row] = Coordinate::explodeCoordinate($coordinate);

            if ($column !== $currentColumn) {
                continue;
            }

            Arr::removeByValue($rows, $row);

            $rowsWasRemoved = true;
        }

        return $rowsWasRemoved ? array_values($rows) : $rows;
    }
}