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

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function containsOne(string $haystack, string $needle): bool
    {
        return self::substrCount($haystack, $needle) === 1;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function lastChar(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return Str::substr($str, -1);
    }

    /**
     * @param string $str
     * @return bool
     */
    public static function isWhitespace(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        return trim($str) === '';
    }

    /**
     * @param string $search
     * @param string $value
     * @param string $subject
     * @return string
     */
    public static function insertBefore(string $search, string $value, string $subject): string
    {
        $pos = Str::rpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return Str::substrReplace($subject, $value, $pos, 0);
    }

    /**
     * Replace text within a portion of a string
     * (substr_replace for unicode characters)
     *
     * Source @link https://github.com/sallaizalan/mb-substr-replace/blob/master/mbsubstrreplace.php
     *
     * @param string $string
     * @param string $replacement
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function substrReplace(string $string, string $replacement, int $start, int $length = 1)
    {
        $startString = Str::substr($string, 0, $start);
        $endString = Str::substr($string, $start + $length, Str::length($string));

        return $startString . $replacement . $endString;
    }
}