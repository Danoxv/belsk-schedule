<?php

namespace Src\Models;

class Lesson
{
    private Pair $pair;
    private int $row;

    public function __construct(Pair $pair, int $row)
    {
        $this->pair = $pair;
        $this->row = $row;
    }

    public function isFirstWeek(): bool
    {
        return false;
    }

    public function isSecondWeek(): bool
    {
        return false;
    }

    public function isFirstAndSecondWeek(): bool
    {
        return true;
    }

    public function isClassHour(): bool
    {
        return false;
    }
}