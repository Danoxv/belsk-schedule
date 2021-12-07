<?php

namespace Src\Models;

use Src\Support\Str;

class Day
{
    private const MONDAY    = 'понедельник';
    private const TUESDAY   = 'вторник';
    private const WEDNESDAY = 'среда';
    private const THURSDAY  = 'четверг';
    private const FRIDAY    = 'пятница';
    private const SATURDAY  = 'суббота';
    private const SUNDAY    = 'воскресенье';

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
    public static function normalize(string $day): string
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