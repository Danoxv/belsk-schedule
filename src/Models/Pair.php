<?php

namespace Src\Models;

use Src\Support\Collection;
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

        $this->init();
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

    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getDay(): string
    {
        return $this->day;
    }

    public function getLessons()
    {
        return $this->lessons;
    }

    private function init()
    {
        $this->resolveIsValid();

        if (!$this->isValid()) {
            return;
        }

        $this->resolveTimeAndNumber();
        $this->resolveDay();
    }

    private function resolveDay()
    {
        $dayCol = $this->getSheet()->getDayCol();
        $dayRow = $this->timeCell->getRow();

        $sheet = $this->getSheet();

        do {
            $day = $sheet->getCellValue($dayCol.$dayRow);

            // Hack: try to find day on the previous column also
            if ($dayCol !== 'A') {
                $dayPrevCol = $sheet->getCellValue(prevColumn($dayCol).$dayRow);
                if (!empty($dayPrevCol)) {
                    $day = $dayPrevCol;
                }
            }

            $dayRow--;
            if ($dayRow < 0) {
                break;
            }
        } while (empty($day));

        $this->day = Str::lower($day);
    }

    private function resolveTimeAndNumber()
    {
        $this->time = $this->number = '';

        $value = $this->timeCell->getValue();
        $value = Str::replaceManySpacesWithOne($value);

        if (empty($value)) {
            return;
        }

        $parts = explode(' ', $value);

        foreach ($parts as &$part) {
            $part = trim($part);
            $part = Str::replaceManySpacesWithOne($part);
        }

        if (!isset($parts[1])) {
            $this->time = formatTime($parts[0] ?? '');
            $this->number = '';
            return;
        }

        $this->time = formatTime($parts[1] ?? '');
        $this->number = $parts[0] ?? '';
    }

    private function resolveIsValid()
    {
        $firstLesson = new Lesson($this, $this->timeCell->getRow());

        // If Pair cell is empty (without pair start-end time)
        // and it's lesson is not "class hour"
        // then Pair is invalid (because without time).
        if (! $firstLesson->isValid() || ($this->timeCell->isEmpty() /*&& !$firstLesson->isClassHour()*/)) {
            $this->isValid = false;
            return;
        }

        $this->isValid = true;
    }

    /**
     * Find and add Lessons.
     */
    private function process()
    {
        if (!$this->isValid()) {
            return;
        }

        $row1 = $this->timeCell->getRow();
        $lesson1 = new Lesson($this, $row1);

        $row2 = nextRow($row1);
        $lesson2 = new Lesson($this, $row2);

        if ($lesson1->isValid() && $lesson2->isValid()) {
            $lesson1->setWeekPosition(Lesson::FIRST_WEEK);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);

            $lesson2->setWeekPosition(Lesson::SECOND_WEEK);
            $this->lessons->put($lesson2->getCoordinate(), $lesson2);
        } elseif (!$lesson2->isValid()) {
            $lesson1->setWeekPosition(Lesson::FIRST_AND_SECOND_WEEK);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);
        }
    }
}