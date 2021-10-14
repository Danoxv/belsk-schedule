<?php

namespace Src\Support;

class Str extends \Illuminate\Support\Str
{
    /**
     * @param string $string
     * @return string
     */
    public static function removeSpaces(string $string): string
    {
        return preg_replace('/\s+/', '', $string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function replaceManySpacesWithOne(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string);
    }

    /**
     * @param string $value
     * @param string $placeholder
     * @return string
     */
    public static function empty(string $value, string $placeholder = '-')
    {
        return empty(trim($value)) ? $placeholder : $value;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return false|int
     */
    public static function rpos(string $haystack, string $needle, int $offset = 0)
    {
        return mb_strrpos($haystack, $needle, $offset, 'UTF-8');
    }
}