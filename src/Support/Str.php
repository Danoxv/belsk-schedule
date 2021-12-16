<?php

namespace Src\Support;

class Str extends \Illuminate\Support\Str
{
    private const UTF_8 = 'UTF-8';

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
    public static function empty(string $value, string $placeholder = '-'): string
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
        return mb_strrpos($haystack, $needle, $offset, self::UTF_8);
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

        return self::substr($str, -1);
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
        $pos = self::rpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return self::substrReplace($subject, $value, $pos, 0);
    }

    /**
     * Replace text within a portion of a string
     * (substr_replace for unicode characters)
     *
     * Source: @link https://github.com/sallaizalan/mb-substr-replace/blob/master/mbsubstrreplace.php
     *
     * @param string $string
     * @param string $replacement
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function substrReplace(string $string, string $replacement, int $start, int $length = 1): string
    {
        $startString = self::substr($string, 0, $start);
        $endString = self::substr($string, $start + $length, self::length($string));

        return $startString . $replacement . $endString;
    }

    /**
     * Source: @link https://stackoverflow.com/a/4517270
     *
     * @param string $string
     * @param string $prefix
     * @return string
     */
    public static function removePrefix(string $string, string $prefix): string
    {
        $prefixLen = self::length($prefix);
        if (self::substr($string, 0, $prefixLen) === $prefix) {
            $string = self::substr($string, $prefixLen);
        }

        return $string;
    }

    /**
     * @param string $string
     * @param string $char
     * @return int
     */
    public static function maxConsecutiveCharsCount(string $string, string $char): int
    {
        $max = 0;

        $current = 0;
        foreach(self::split($string) as $val) {
            if($val === $char) {
                $current++;
                $max = max($current, $max);
            } else {
                $current = 0;
            }
        }

        return $max;
    }

    /**
     * @param string $string
     * @param int $length
     * @return array|false|string[]|null
     */
    public static function split(string $string, int $length = 1)
    {
        return mb_str_split($string, $length, self::UTF_8);
    }

    /**
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit($value, $limit = 255, $end = ''): string
    {
        return parent::limit($value, $limit, $end);
    }

    /**
     * Returns char that string not contains.
     *
     * @param string $string
     * @return string|null
     */
    public static function getNotExistingChar(string $string): ?string
    {
        // 33 and 126 - ASCII symbol codes ('!' and '~' accordingly)
        // see https://www.man7.org/linux/man-pages/man7/ascii.7.html
        for ($ascii = 33; $ascii <= 126; $ascii++) {
            // Convert ASCII symbol code to char
            $char = sprintf('%c', $ascii);

            if (!self::contains($string, $char)) {
                return $char;
            }
        }

        return null;
    }
}