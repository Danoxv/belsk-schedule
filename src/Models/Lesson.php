<?php

namespace Src\Models;

use Src\Config\Config;
use Src\Support\Str;

class Lesson
{
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

    private string $subject;
    private string $teacher;
    private string $auditory;

    private bool $isValid = true;

    public function __construct(Pair $pair, int $row)
    {
        $this->pair = $pair;
        $this->row = $row;

        $this->init();
    }

    /**
     * @param int $weekPosition
     */
    public function setWeekPosition(int $weekPosition)
    {
        $this->weekPosition = $weekPosition;
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
     * @return string
     */
    public function getTeacher(): string
    {
        return $this->teacher;
    }

    /**
     * @return string
     */
    public function getAuditory(): string
    {
        return $this->auditory;
    }

    /**
     * @return bool
     */
    public function isMendeleeva4(): bool
    {
        return $this->isMendeleeva4;
    }

    /**
     * @param string $rawCellValue
     * @return bool
     */
    public static function isClassHourLesson(string $rawCellValue): bool
    {
        if (empty($rawCellValue)) {
            return false;
        }

        return self::formatClassHourLesson($rawCellValue) === 'Классный час';
    }

    /**
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
        $uniqueChar = '|';

        $spacesCount = 10;
        $replacementPerformed = false;
        do {
            $lesson = str_replace(str_repeat($space, $spacesCount), $uniqueChar, $lesson, $count);

            if ($count > 0) {
                $replacementPerformed = true;
            }

            $spacesCount--;
        } while($count === 0 && $spacesCount > 0);

        $lesson = Str::removeSpaces($lesson);

        if ($replacementPerformed) {
            $lesson = str_replace($uniqueChar, $space, $lesson);
        }

        $lesson = Str::lower($lesson);
        return Str::ucfirst($lesson);
    }

    private function init()
    {
        $this->resolveCellAndIsClassHour();

        // Lesson with invisible cell can't be a valid lesson.
        if ($this->cell->isInvisible()) {
            $this->isValid = false;
            return;
        }

        // Resolve "is empty"
        $this->isEmpty = $this->cell->isEmpty();

        $this->resolveSubjectTeacherAuditory();
        $this->resolveIsMendeleeva4();
    }

    private function resolveCellAndIsClassHour()
    {
        $this->isClassHour = false;

        $sheet = $this->pair->getSheet();

        $cell = new Cell(
            $this->pair->getGroup()->getColumn() . $this->row,
            $sheet
        );

        if ($cell->isEmpty() && $sheet->hasClassHourLessonColumn()) {
            $possibleClassHourCell = new Cell(
                $sheet->getClassHourLessonColumn() . $this->row,
                $sheet
            );

            if (self::isClassHourLesson($possibleClassHourCell->getValue(true))) {
                $cell = $possibleClassHourCell;
            }
        }

        $this->cell = $cell;

        $this->isClassHour = self::isClassHourLesson($this->cell->getValue(true));
    }

    private function resolveSubjectTeacherAuditory()
    {
        $this->subject = '';
        $this->teacher = '';
        $this->auditory = '';

        $value = $this->cell->getValue();

        if ($this->isClassHour()) {
            $value = self::formatClassHourLesson($value);
        }

        $parts = explode("\n", $value);

        foreach ($parts as &$part) {
            $part = trim($part);
            $part = Str::replaceManySpacesWithOne($part);
        }
        unset($part); // prevent side-effects

        $firstPart = $parts[0];

        $this->subject = trim($firstPart ?? '');

        if (count($parts) >= 3) {
            foreach ($parts as $k => $part) {
                if ($k === 0) continue; // was already processed (as 'subject')

                $teacherAndAuditory = $this->explodeTeacherAndAuditory($part);

                $this->auditory .= ($teacherAndAuditory['auditory'] . PHP_EOL);
                $this->teacher .= ($teacherAndAuditory['teacher'] . PHP_EOL);
            }

            return;
        }

        $teacherAndAuditory = $this->explodeTeacherAndAuditory($parts[1] ?? '');

        $this->teacher = $teacherAndAuditory['teacher'];
        $this->auditory = $teacherAndAuditory['auditory'];
    }

    /**
     * @param string $string
     * @return string[]
     */
    private function explodeTeacherAndAuditory(string $string): array
    {
        $result = [
            'teacher' => '',
            'auditory' => ''
        ];

        $string = trim($string);

        if (empty($string)) {
            return $result;
        }

        $lastSpace = Str::rpos($string, ' ');
        if ($lastSpace !== false) {
            $result['teacher'] = trim(Str::substr($string, 0, $lastSpace));
            $result['auditory'] = trim(Str::substr($string, $lastSpace));
        }

        return $result;
    }

    private function resolveIsMendeleeva4()
    {
        $this->isMendeleeva4 = false;

        if (!$this->cell->getSheet()->hasMendeleeva4()) {
            return;
        }

        $sheet = $this->cell->getSheet();
        if ($sheet->needForceApplyMendeleeva4() && !$this->isClassHour()) {
            $this->isMendeleeva4 = true;
            return;
        }

        if (empty($this->getSubject())) {
            return;
        }

        $cellColor = $this->cell->getNativeCell()->getStyle()->getFill()->getEndColor()->getRGB();

        $config = Config::getInstance();
        if (in_array($cellColor, $config->mendeleeva4HouseCellColors, true)) {
            $this->isMendeleeva4 = true;
        }
    }
}