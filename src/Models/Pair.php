<?php

namespace Src\Models;

use Src\Support\Collection;
use Src\Support\Coordinate;
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

    private bool $isClassHour;

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

    public function setNumber(string $number)
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
     * @return bool
     */
    public function isClassHour(): bool
    {
        return $this->isClassHour;
    }

    /**
     * Find and add Lessons.
     */
    private function process()
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

        $this->isClassHour = $lesson1->isClassHour();

        $this->resolveTimeAndNumber();
        $this->resolveDay();

        if (!$this->isValid()) {
            return;
        }

        $row2 = Coordinate::nextRow($row1);
        $lesson2 = new Lesson($this, $row2);

        if (
            $lesson2->isValid() && $lesson2->hasSubject() &&
            $lesson1->isWithoutTeacherAuditory() && $lesson2->isWithoutTeacherAuditory()
        ) {
            Lesson::normalizeSubjectTeacherAuditory($lesson1, $lesson2);

            $teacherAndAuditory = Lesson::explodeTeacherAndAuditory($lesson2->getSubject());

            $lesson1
                ->setWeekPosition(Lesson::FIRST_AND_SECOND_WEEK)
                ->setTeacher($teacherAndAuditory['teacher'])
                ->setAuditory($teacherAndAuditory['auditory']);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);

            return;
        }

        if ($lesson2->isValid()) {
            $lesson1->setWeekPosition(Lesson::FIRST_WEEK);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);

            $lesson2->setWeekPosition(Lesson::SECOND_WEEK);
            $this->lessons->put($lesson2->getCoordinate(), $lesson2);
        } else {
            $lesson1->setWeekPosition(Lesson::FIRST_AND_SECOND_WEEK);
            $this->lessons->put($lesson1->getCoordinate(), $lesson1);
        }
    }

    private function resolveDay()
    {
        $dayCol = $this->getSheet()->getDayCol();
        $dayRow = $this->timeCell->getRow();

        $sheet = $this->getSheet();

        do {
            $day = $sheet->getCellValue($dayCol.$dayRow);

            // Hack: try to find day on the previous column also
            if (empty($day) && $dayCol !== 'A') {
                $dayPrevCol = $sheet->getCellValue(Coordinate::prevColumn($dayCol).$dayRow);
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

        if ($this->isClassHour()) {
            return;
        }

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
            $this->time = $this->formatTime($parts[0] ?? '');
            $this->number = '';
            return;
        }

        $this->time = $this->formatTime($parts[1] ?? '');
        $this->number = $parts[0] ?? '';
    }

    private function formatTime(string $time): string
    {
        $time = str_replace([
            '.',
            '-'
        ], [
            ':',
            ' - '
        ], $time);

        $time = Str::replaceManySpacesWithOne($time);

        return trim($time);
    }
}