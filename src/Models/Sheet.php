<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Src\Config\AppConfig;
use Src\Config\SheetConfig;
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
    private SheetConfig $sheetConfig;
    private SheetProcessingConfig $sheetProcessingConfig;

    private bool $isProcessed = false;

    private bool $hasMendeleeva4 = false;

    private string $title;

    private Collection $groups;

    private string $id;

    /** @var array */
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
        $this->sheetConfig              = new SheetConfig();

        $this->groups                   = new Collection();

        $this->init();
        $this->process();
    }

    /**
     * @param string $filePath
     * @param SheetProcessingConfig $sheetProcessingConfig
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function createSpreadsheet(string $filePath, SheetProcessingConfig $sheetProcessingConfig): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($filePath);

        if (!$sheetProcessingConfig->processGroups) {
            $reader->setReadDataOnly(true);
        }

        return $reader->load($filePath);
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
        return $this->sheetConfig->isProcessable();
    }

    /**
     * @return string|null
     */
    public function getTimeColumn(): ?string
    {
        return $this->sheetConfig->timeCol;
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
        return $this->sheetConfig->dayCol;
    }

    /**
     * @return int|null
     */
    public function getGroupNamesRow(): ?int
    {
        return $this->sheetConfig->groupNamesRow;
    }

    /**
     * @return bool
     */
    public function hasClassHourLessonColumn(): bool
    {
        return !empty($this->sheetConfig->classHourLessonColumn);
    }

    /**
     * @return string|null
     */
    public function getClassHourLessonColumn(): ?string
    {
        return empty($this->sheetConfig->classHourLessonColumn) ? null : $this->sheetConfig->classHourLessonColumn;
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

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get processable (potentially with lessons)
     * columns range.
     *
     * @return array
     */
    private function getColumnsRange(): array
    {
        $start = $this->sheetConfig->firstGroupCol;
        $end = $this->sheetConfig->lastGroupCol;

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
        $start = $this->sheetConfig->firstScheduleRow;
        $end = $this->sheetConfig->lastScheduleRow;

        return Coordinate::generateRowsRange($start, $end);
    }

    /**
     * Resolve Excel config, detect "Has Mendeleeva 4 house".
     */
    private function init()
    {
        $this->title = trim(Security::sanitizeString($this->worksheet->getTitle()));

        $this->resolveId();

        $firstColumn = Coordinate::FIRST_COL;
        $firstRow = Coordinate::FIRST_ROW;

        $highestColRow = $this->worksheet->getHighestRowAndColumn();
        $highestColumn = $highestColRow['column'];
        $highestRow = $highestColRow['row'];

        $columns = Coordinate::generateColumnsRange($firstColumn, $highestColumn);
        $rows = Coordinate::generateRowsRange($firstRow, $highestRow);

        $sheetCfg = &$this->sheetConfig;

        $dayColFound = $timeColFound = $classHourColFound = false;
        foreach ($columns as $column) {
            foreach ($rows as $row) {
                $coordinate = $column.$row;

                $cellValue = $this->getCellValue($coordinate);

                if (Str::startsWith($cellValue, $this->config->skipCellsThatStartsWith)) {
                    $this->coordinatesForSkip[] = ['column' => $column, 'row' => $row];
                }

                /*
                 * Resolve Excel config
                 */

                if (!$sheetCfg->isProcessable()) {
                    $cleanCellValue = '';
                    if (!$dayColFound || !$timeColFound) {
                        $cleanCellValue = Str::lower(Str::replaceManySpacesWithOne($cellValue));
                    }

                    if (!$dayColFound && in_array($cleanCellValue, $this->config->dayWords)) {
                        $dayColFound                = true;
                        $sheetCfg->dayCol           = $column;

                        $sheetCfg->groupNamesRow    = $row;
                        $sheetCfg->firstScheduleRow = Coordinate::nextRow($sheetCfg->groupNamesRow);
                    } elseif (!$timeColFound && in_array($cleanCellValue, $this->config->timeWords)) {
                        $timeColFound               = true;
                        $sheetCfg->timeCol          = $column;
                        $sheetCfg->firstGroupCol    = Coordinate::nextColumn($sheetCfg->timeCol);

                        $sheetCfg->groupNamesRow    = $row;
                        $sheetCfg->firstScheduleRow = Coordinate::nextRow($sheetCfg->groupNamesRow);
                    } else if (!$classHourColFound && Lesson::isClassHourLesson($cellValue)) {
                        $classHourColFound                  = true;
                        $sheetCfg->classHourLessonColumn    = $column;
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

        $sheetCfg->lastGroupCol = $highestColumn;
        $sheetCfg->lastScheduleRow = $highestRow;

        if ($sheetCfg->classHourLessonColumn === null) {
            $sheetCfg->classHourLessonColumn = false;
        }
    }

    private function resolveId()
    {
        $id = Str::slug($this->getTitle());

        if (empty($id)) {
            $id = $this->worksheet->getHashCode();
        }

        $this->id = $id . '_' . Str::random(4);
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

            if(!$group->isValid()) {
                continue;
            }

            // Apply filter by student's group
            if ($hasFilterByGroup && $conf->studentsGroup !== $group->getName()) {
                continue;
            }

            // Add group
            if ($conf->processGroups) {
                $processableRows = $this->removeUnprocessableRows($rows, $column);
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
    private function removeUnprocessableRows(array $rows, string $currentColumn): array
    {
        $rowsWasRemoved = false;

        foreach ($this->coordinatesForSkip as $coordinate) {
            if ($coordinate['column'] !== $currentColumn) {
                continue;
            }

            Arr::removeByValue($rows, $coordinate['row']);

            $rowsWasRemoved = true;
        }

        return $rowsWasRemoved ? array_values($rows) : $rows;
    }
}
