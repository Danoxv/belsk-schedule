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
}