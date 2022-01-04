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
     * @return string|null Day or NULL if day is not recognized
     */
    public static function recognize(string $day): ?string
    {
        foreach (self::getAll() as $validDay) {
            if (Str::isSimilar($day, $validDay)) {
                return $validDay;
            }
        }

        return null;
    }

    /**
     * Format day for printing
     *
     * 'понедельник' -> 'Понедельник'
     *
     * @param string $day
     * @return string
     */
    public static function format(string $day): string
    {
        return Str::ucfirst($day);
    }
}