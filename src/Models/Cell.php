<?php

namespace Src\Models;

class Cell
{
    private string $coordinate;
    private Sheet $sheet;
    private ?Lesson $lesson = null;

    private bool $isInvisible = false;

    /**
     * @param string $coordinate
     * @param $sheet
     */
    public function __construct(string $coordinate, $sheet)
    {
        $this->coordinate = $coordinate;
        $this->sheet = $sheet;
    }

    /**
     * @return Sheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param Lesson $lesson
     */
    public function setLesson(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    /**
     * @return Lesson|null
     */
    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    /**
     * @return bool
     */
    public function isLesson(): bool
    {
        return $this->lesson !== null;
    }

    public function isInvisible(): bool
    {
        return $this->isInvisible;
    }
}