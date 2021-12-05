<?php

namespace Src\Models;

use Src\Config\AppConfig;
use Src\Support\Collection;
use Src\Support\Str;

class Lesson
{
    /**
     * See @link https://regex101.com/r/S7njgP/1
     */
    private const PARSE_TEACHERS_AUDITORIES_REGEX =
        '#((?<teachersSurnames>\p{Lu}\p{Ll}+)\s+(?<teachersInitials>\p{Lu}\.\s*\p{Lu}\.)\s+(?<auditory>\S+))|((?<teacher>\*+)\s*(?<auditory2>\S+))#u';

    public const FIRST_WEEK = 1;
    public const SECOND_WEEK = 2;
    public const FIRST_AND_SECOND_WEEK = 12;

    private Pair $pair;
    private int $row;
    private Cell $cell;

    /** @var int */
    private int $weekPosition;
    private bool $isEmpty;
    private bool $isClassHour;
    private bool $isMendeleeva4;

    private string $subject = '';
    private Collection $teachersAuditories;

    private bool $isValid = true;

    public function __construct(Pair $pair, int $row)
    {
        $this->pair = $pair;
        $this->row = $row;
        $this->teachersAuditories = new Collection();

        $this->init();
    }

    /**
     * @param int $weekPosition
     * @return Lesson
     */
    public function setWeekPosition(int $weekPosition): self
    {
        $this->weekPosition = $weekPosition;

        return $this;
    }

    /**
     * @param string $subject
     * @return Lesson
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    private function setTeachersAuditories(Collection $teachersAuditories)
    {
        $this->teachersAuditories = $teachersAuditories;

        return $this;
    }

    /**
     * @param bool $isValid
     * @return $this
     */
    public function setIsValid(bool $isValid)
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClassHour(): bool
    {
        return $this->isClassHour;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return string
     */
    public function getCoordinate(): string
    {
        return $this->cell->getCoordinate();
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->pair->getNumber();
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->pair->getTime();
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $separator
     * @return string
     */
    public function getTeachersAsString(string $separator = ''): string
    {
        return $this->teachersAuditories->keys()->implode($separator);
    }

    /**
     * @return bool
     */
    public function hasAuditories(): bool
    {
        return !empty($this->getAuditoriesAsString(''));
    }

    /**
     * @param bool $rawValue
     * @return string
     */
    public function getCellValue(bool $rawValue = false)
    {
        return $this->cell->getValue($rawValue);
    }

    /**
     * @param string $separator
     * @return string
     */
    public function getAuditoriesAsString(string $separator = ''): string
    {
        return $this->teachersAuditories->values()->implode($separator);
    }

    /**
     * @return bool
     */
    public function isMendeleeva4(): bool
    {
        return $this->isMendeleeva4;
    }

    /**
     * @param string $cellValue
     * @return bool
     */
    public static function isClassHourLesson(string $cellValue): bool
    {
        $cellValue = trim($cellValue);

        if (empty($cellValue)) {
            return false;
        }

        $config = AppConfig::getInstance();

        return Str::lower(Str::removeSpaces($cellValue)) === Str::lower(Str::removeSpaces($config->classHourCellKeyword));
    }

    /**
     * @return string
     */
    public function getTechnicalTitle(): string
    {
        return sprintf(
            '%s [%s]',
            $this->cell->getCoordinate(),
            $this->pair->getSheet()->getTitle()
        );
    }

    /**
     * Resolve subject, teacher and auditory in lessons
     *
     * @param Lesson $lesson1
     * @param Lesson $lesson2
     */
    public static function processResolving(Lesson $lesson1, Lesson $lesson2)
    {
        if ($lesson1->isValid()) {
            /*
             * Resolve first lesson
             */

            $value = $lesson1->getCellValue();

            if ($lesson1->isClassHour()) {
                $value = self::formatClassHourLesson($value);
            }

            $parsed = self::parse($value);

            // Not parsed, seems like was intersected with second lesson
            if ($value && !$parsed) {
                $lesson2->setIsValid(false);

                $value = "$value " . $lesson2->getCellValue();

                $parsed = self::parse($value);
            }

            if ($parsed) {
                $lesson1->setSubject($parsed['subject']);
                $lesson1->setTeachersAuditories($parsed['teachersAuditories']);
            } else {
                $lesson1->setSubject($value);
            }
        }

        if ($lesson2->isValid()) {
            /*
             * Resolve second lesson
             */

            $value = $lesson2->getCellValue();

            if ($lesson2->isClassHour()) {
                $value = self::formatClassHourLesson($value);
            }

            $parsed = self::parse($value);

            if ($parsed) {
                $lesson2->setSubject($parsed['subject']);
                $lesson2->setTeachersAuditories($parsed['teachersAuditories']);
            } else {
                $lesson2->setSubject($value);
            }
        }
    }

    /**
     * @param string $value
     * @return array|false Array with parsed or FALSE on failure
     */
    private static function parse(string $value)
    {
        $parsed = [
            'subject' => '',
            'teachersAuditories' => new Collection(),
        ];

        $value = trim($value);

        if (empty($value)) {
            return false;
        }

        $value = Str::replaceManySpacesWithOne($value);

        $matched = preg_match_all(self::PARSE_TEACHERS_AUDITORIES_REGEX, $value, $matches);

        if (empty($matched)) {
            return false;
        }

        /*
         * Resolve subject
         */

        $firstTeacher = $matches['teachersSurnames'][0] ?? '';
        if (empty($firstTeacher)) {
            $firstTeacher = $matches['teacher'][0] ?? '';
        }
        $parsed['subject'] = trim(Str::before($value, $firstTeacher));

        /*
         * Resolve teachers and auditories
         */

        $teacherWasFound = false;
        foreach ($matches['teachersSurnames'] as $k => $surname) {
            $initials = $matches['teachersInitials'][$k] ?? '';
            $auditory = $matches['auditory'][$k] ?? '';

            $teacher = trim("$surname $initials");

            if ($teacher) {
                $teacherWasFound = true;
                $parsed['teachersAuditories']->put($teacher, $auditory);
            }
        }

        if (!$teacherWasFound) {
            $teacher = $matches['teacher'][0] ?? '';
            if ($teacher) {
                $parsed['teachersAuditories']->put($teacher, $matches['auditory2'][0] ?? '');
            }
        }

        return $parsed;
    }

    /**
     * Turn this one:
     * 'К Л А С С Н Ы Й   Ч А С'
     * Into this one:
     * 'Классный час'
     *
     * @param string $cellValue
     * @return string
     */
    private static function formatClassHourLesson(string $cellValue): string
    {
        $lesson = trim($cellValue);

        if (empty($lesson)) {
            return '';
        }

        $space = ' ';
        $maxSpacesCount = Str::maxConsecutiveCharsCount($cellValue, $space);

        if ($maxSpacesCount > 1) {
            $uniqueChar = '|';

            // К Л А С С Н Ы Й   Ч А С -> К Л А С С Н Ы Й|Ч А С
            $lesson = str_replace(str_repeat($space, $maxSpacesCount), $uniqueChar, $lesson);

            // К Л А С С Н Ы Й|Ч А С -> КЛАССНЫЙ|ЧАС
            $lesson = Str::removeSpaces($lesson);

            // КЛАССНЫЙ|ЧАС -> КЛАССНЫЙ ЧАС
            $lesson = str_replace($uniqueChar, $space, $lesson);
        }

        // КЛАССНЫЙ ЧАС -> Классный час
        return Str::ucfirst(Str::lower($lesson));
    }

    private function init()
    {
        $this->resolveCellAndIsClassHour();

        // Lesson with invisible cell can't be a valid one.
        if ($this->cell->isInvisible()) {
            $this->isValid = false;
            return;
        }

        // Resolve "is empty"
        $this->isEmpty = $this->cell->isEmpty();

        $this->resolveIsMendeleeva4();
    }

    /**
     * TODO Possible optimization:
     * execute $cell->process() only once
     */
    private function resolveCellAndIsClassHour()
    {
        $sheet = $this->pair->getSheet();

        $cell = new Cell(
            $this->pair->getGroup()->getColumn() . $this->row,
            $sheet
        );
        $cell->process();

        if (!$sheet->hasClassHourLessonColumn()) {
            $this->cell = $cell;
            $this->isClassHour = false;
            return;
        }

        $isClassHour = null;

        if ($cell->isEmpty()) {
            $possibleClassHourCell = new Cell(
                $sheet->getClassHourLessonColumn() . $this->row,
                $sheet
            );

            if (self::isClassHourLesson($possibleClassHourCell->getValue())) {
                $isClassHour = true;
                $possibleClassHourCell->process();
                $cell = $possibleClassHourCell;
            } else {
                $isClassHour = false;
            }
        }

        $this->cell = $cell;

        if ($isClassHour === null) {
            $isClassHour = self::isClassHourLesson($this->cell->getValue());
        }

        $this->isClassHour = $isClassHour;
    }

    private function resolveIsMendeleeva4()
    {
        $this->isMendeleeva4 = false;

        if ($this->isClassHour()) {
            return;
        }

        $sheet = $this->cell->getSheet();

        if (!$sheet->hasMendeleeva4()) {
            return;
        }

        if ($sheet->needForceApplyMendeleeva4()) {
            $this->isMendeleeva4 = true;
            return;
        }

        if ($this->cell->isEmpty()) {
            return;
        }

        $cellColor = $this->cell->getEndColorRgb();

        $config = AppConfig::getInstance();
        if (in_array($cellColor, $config->mendeleeva4HouseCellColors, true)) {
            $this->isMendeleeva4 = true;
        }
    }
}
