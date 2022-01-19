<?php
declare(strict_types=1);

namespace Src\Models;

use Src\Support\Collection;
use Src\Support\Coordinate;
use Src\Support\Helpers;
use Src\Support\Str;

class Pair
{
    /** @var Cell */
    private Cell $timeCell;

    /** @var string */
    private string $time;

    /** @var string */
    private string $number;

    /** @var Group */
    private Group $group;

    /** @var Collection */
    private Collection $lessons;

    /** @var string */
    private string $day;

    /** @var bool */
    private bool $isValid;

    public function __construct(Cell $timeCell, Group $group)
    {
        $this->timeCell = $timeCell;
        $this->group = $group;
        $this->lessons = new Collection();

        $this->process();
    }

    /**
     * @return Sheet
     */
    public function getSheet(): Sheet
    {
        return $this->group->getSheet();
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * @return Collection
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    /**
     * Find and add Lessons.
     */
    private function process(): void
    {
        $row1 = $this->timeCell->getRow();
        $lesson1 = new Lesson($this, $row1);

        $this->isValid = true;

        // If Pair cell is empty (without pair start-end time)
        // and it's lesson is not "class hour"
        // then Pair is invalid (because without time).
        if (!$lesson1->isValid() || ($this->timeCell->isEmpty() && !$lesson1->isClassHour())) {
            $this->isValid = false;
            return;
        }

        $this->resolveDay();

        if (!$this->isValid()) {
            return;
        }

        $row2 = Coordinate::nextRow($row1);
        $lesson2 = new Lesson($this, $row2);

        Lesson::processResolving($lesson1, $lesson2);

        if (!$lesson1->isValid()) {
            $this->isValid = false;
            return;
        }

        $this->resolveTimeAndNumber($lesson1);

        if ($lesson2->isValid()) {
            $lesson1->setWeekPosition(Lesson::FIRST_WEEK);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);

            $lesson2->setWeekPosition(Lesson::SECOND_WEEK);
            $this->lessons->put($lesson2->getCoordinate(), $lesson2);
        } else {
            $lesson1->setWeekPosition(Lesson::BOTH_WEEKS);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);
        }
    }

    private function resolveDay(): void
    {
        $dayCol = $this->getSheet()->getDayCol();
        $dayRow = $this->timeCell->getRow();

        $sheet = $this->getSheet();

        do {
            $day = $sheet->getCellValue($dayCol.$dayRow);

            // Hack: try to find day on the previous column also
            if ($day === Str::EMPTY) {
                $dayPrevCol = Coordinate::prevColumn($dayCol);
                if ($dayPrevCol !== null) {
                    $dayPrevCell = $sheet->getCellValue($dayPrevCol.$dayRow);
                    if ($dayPrevCell !== Str::EMPTY) {
                        $day = $dayPrevCell;
                    }
                }
            }

            $dayRow = Coordinate::prevRow($dayRow);
            if ($dayRow === null) {
                break;
            }
        } while ($day === Str::EMPTY);

        $recognizedDay = Day::recognize($day);

        if ($recognizedDay === null) {
            $this->day = "*INVALID* ($day)";
            $this->isValid = false;
        } else {
            $this->day = $recognizedDay;
        }
    }

    private function resolveTimeAndNumber(Lesson $validFirstLesson): void
    {
        $this->time = $this->number = Str::EMPTY;

        if ($validFirstLesson->isClassHour()) {
            return;
        }

        $value = $this->timeCell->getValue();

        // Handle empty values
        if ($value === Str::EMPTY) {
            return;
        }

        $value = Str::collapseWhitespace($value);

        // Handle values like 'IV' or '15.05-16.40'
        if (Str::notContains($value, Str::SPACE)) {
            if ($this->isNumber($value)) {
                $this->number = $this->formatNumber($value);
            } else {
                $this->time = $this->formatTime($value);
            }

            return;
        }

        // Handle values like 'IV 15.05-16.40'
        $number = Str::before($value, Str::SPACE);
        $time = Str::after($value, Str::SPACE);

        if ($this->isNumber($number)) {
            $this->number = $this->formatNumber($number);
        }

        $this->time = $this->formatTime($time);
    }

    private function isNumber(string $string): bool
    {
        return Helpers::isRomanNumber($string);
    }

    private function formatNumber(string $number): string
    {
        return \strtoupper($number); // multibyte support is not necessary here
    }

    private function formatTime(string $time): string
    {
        return Str::replace([
            '.',
            '-'
        ], [
            ':',
            ' - '
        ],
            Str::stripWhitespace($time)
        );
    }
}
