<?php

namespace Src\Models;

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

    public function setWeekPosition(int $weekPosition)
    {
        $this->weekPosition = $weekPosition;
    }

    public function isFirstWeek(): bool
    {
        return $this->weekPosition === self::FIRST_WEEK;
    }

    public function isSecondWeek(): bool
    {
        return $this->weekPosition === self::SECOND_WEEK;
    }

    public function isFirstAndSecondWeek(): bool
    {
        return $this->weekPosition === self::FIRST_AND_SECOND_WEEK;
    }

    public function isClassHour(): bool
    {
        return false;
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

    public function getNumber()
    {
        return $this->pair->getNumber();
    }

    public function getTime()
    {
        return $this->pair->getTime();
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getTeacher()
    {
        return $this->teacher;
    }

    public function getAuditory()
    {
        return $this->auditory;
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
     * @param string $rawCellValue
     * @return string
     */
    private static function formatClassHourLesson(string $rawCellValue): string
    {
        $lesson = trim($rawCellValue);

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
        // Resolve cell
        $this->cell = new Cell(
            $this->pair->getGroup()->getColumn() . $this->row,
            $this->pair->getSheet()
        );

        // Resolve "is valid":
        // lesson with invisible cell can't be a valid lesson.
        if ($this->cell->isInvisible()) {
            $this->isValid = false;
            return;
        }

        // Resolve "is empty"
        $this->isEmpty = $this->cell->isEmpty();

        $this->resolveSubjectTeacherAuditory();
    }

    private function resolveSubjectTeacherAuditory()
    {
        $this->subject = '';
        $this->teacher = '';
        $this->auditory = '';

        $value = $this->cell->getValue();

        $parts = explode("\n", $value);

        foreach ($parts as &$part) {
            $part = trim($part);
            $part = Str::replaceManySpacesWithOne($part);
        }
        unset($part); // prevent side-effects

        $firstPart = $parts[0];

        $this->subject = trim($firstPart ?? '');

        if (count($parts) >= 3) {
            $this->teacher = '';

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

        $lastSpace = mb_strrpos($string, ' ');
        if ($lastSpace !== false) {
            $result['teacher'] = trim(Str::substr($string, 0, $lastSpace));
            $result['auditory'] = trim(Str::substr($string, $lastSpace));
        }

        return $result;
    }
}