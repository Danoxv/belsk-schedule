<?php

namespace Src\Models;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Src\Config\AppConfig;
use Src\Config\SheetConfig;
use Src\Config\SheetProcessingConfig;
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
     * @return Cell
     */
    public function getCell(string $coordinate)
    {
        return new Cell($coordinate, $this);
    }

    /**
     * @param string $coordinate
     * @param bool $rawValue
     * @return string
     */
    public function getCellValue(string $coordinate, bool $rawValue = false): string
    {
        return $this->getCell($coordinate)->getValue($rawValue);
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

        $sheetCfg->lastGroupCol = $highestColumn;
        $sheetCfg->lastScheduleRow = $highestRow;

        $dayColFound = $timeColFound = $classHourColFound = false;
        $needMendeleeva4Detect = $this->sheetProcessingConfig->detectMendeleeva4;

        foreach ($columns as $column) {
            foreach ($rows as $row) {
                $cellValue = $this->getCellValue($column.$row);

                /*
                 * Resolve Excel config
                 */

                $cleanCellValue = '';
                if (!$dayColFound || !$timeColFound) {
                    $cleanCellValue = Str::lower(Str::replaceManySpacesWithOne($cellValue));
                }

                if (!$dayColFound && in_array($cleanCellValue, $this->config->dayWords, true)) {
                    $dayColFound                = true;
                    $sheetCfg->dayCol           = $column;

                    $sheetCfg->groupNamesRow    = $row;
                    $sheetCfg->firstScheduleRow = Coordinate::nextRow($sheetCfg->groupNamesRow);
                } elseif (!$timeColFound && in_array($cleanCellValue, $this->config->timeWords, true)) {
                    $timeColFound               = true;
                    $sheetCfg->timeCol          = $column;
                    $sheetCfg->firstGroupCol    = Coordinate::nextColumn($sheetCfg->timeCol);

                    $sheetCfg->groupNamesRow    = $row;
                    $sheetCfg->firstScheduleRow = Coordinate::nextRow($sheetCfg->groupNamesRow);
                } else if (!$classHourColFound && Lesson::isClassHourLesson($cellValue)) {
                    $classHourColFound                  = true;
                    $sheetCfg->classHourLessonColumn    = $column;
                }

                /*
                 * Detect "Has Mendeleeva 4 house"
                 */

                if ($needMendeleeva4Detect) {
                    if ($this->sheetProcessingConfig->forceApplyMendeleeva4ToLessons) {
                        $this->hasMendeleeva4 = true;
                        $needMendeleeva4Detect = false;
                    }

                    if (
                        $needMendeleeva4Detect &&
                        $cellValue &&
                        Str::containsAll(Str::lower($cellValue), $this->config->mendeleeva4KeywordsInCell)
                    ) {
                        $this->hasMendeleeva4 = true;
                        $needMendeleeva4Detect = false;
                    }
                }

                // Optimization: "Gotta Catch 'Em All" challenge completed
                if ($dayColFound && $timeColFound && $classHourColFound && !$needMendeleeva4Detect) {
                    break(2);
                }
            }
        }

        if (!$classHourColFound) {
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
                $group->process($rows);
            }
            $this->groups->put($column, $group);
        }

        // All done, mark sheet as processed
        $this->isProcessed = true;
    }
}
