<?php

namespace Src\Enums;

use Src\Support\Str;

class Day
{
    const MONDAY    = 'понедельник';
    const TUESDAY   = 'вторник';
    const WEDNESDAY = 'среда';
    const THURSDAY  = 'четверг';
    const FRIDAY    = 'пятница';
    const SATURDAY  = 'суббота';
    const SUNDAY    = 'воскресенье';

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }

    /**
     * @param string $day
     * @return string
     */
    public static function normalize(string $day)
    {
        return Str::lower($day);
    }

    /**
     * @param string $day
     * @return string
     */
    public static function format(string $day): string
    {
        return Str::ucfirst(Str::lower($day));
    }
}