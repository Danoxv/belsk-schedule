<?php
declare(strict_types=1);

namespace Src\Support;

use voku\helper\ASCII;
use voku\helper\UTF8;

/**
 * String helper. All methods have unicode support.
 * @see https://github.com/voku/portable-utf8
 *
 * Based on Laravel's Illuminate\Support\Str class.
 *
 * @package Src\Support
 */
class Str
{
    public const EMPTY = '';
    public const SPACE = ' ';

    private const MAX_LEVENSHTEIN_STR_LEN = 255;

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public static function after(string $subject, string $search): string
    {
        return UTF8::str_substr_after_first_separator($subject, $search);
    }

    /**
     * Convert a string into ASCII.
     *
     * @param  string  $value
     * @return string
     */
    public static function ascii(string $value): string
    {
        return UTF8::to_ascii($value);
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public static function before(string $subject, string $search): string
    {
        return UTF8::str_substr_before_first_separator($subject, $search);
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a
     * single space. This includes tabs and newline characters, as well as
     * multibyte whitespace such as the thin space and ideographic space.
     *
     * @param string $string
     * @return string
     */
    public static function collapseWhitespace(string $string): string
    {
        return UTF8::collapse_whitespace($string);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles): bool
    {
        if (is_array($needles)) {
            return UTF8::str_contains_any($haystack, $needles);
        }

        return UTF8::str_contains($haystack, $needles);
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @param  string  $haystack
     * @param  string[]  $needles
     * @return bool
     */
    public static function containsAll(string $haystack, array $needles): bool
    {
        return UTF8::str_contains_all($haystack, $needles);
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        if (is_array($needles)) {
            return UTF8::str_ends_with_any($haystack, $needles);
        }

        return UTF8::str_ends_with($haystack, $needles);
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    public static function finish(string $value, string $cap): string
    {
        return UTF8::str_ensure_right($value, $cap);
    }

    /**
     * Returns the first character of the string.
     *
     * @param string $string The input string.
     * @return string
     */
    public static function firstChar(string $string): string
    {
        return UTF8::first_char($string);
    }

    /**
     * Reads entire file into a string.
     *
     * WARNING: Do not use UTF-8 Option ($convertToUtf8) for binary files (e.g.: images)!!!
     *
     * @see http://php.net/manual/en/function.file-get-contents.php
     *
     * @param string $fileName
     * @param bool $convertToUtf8
     * @param string $fromEncoding
     * @return false|string
     */
    public static function fileGetContents(string $fileName, bool $convertToUtf8 = true, $fromEncoding = '')
    {
        $fromEncoding = self::normalizeEncoding($fromEncoding);

        return UTF8::file_get_contents(
            $fileName,
            false,
            null,
            null,
            null,
            5,
            $convertToUtf8,
            $fromEncoding
        );
    }

    /**
     * Create a escape html version of the string.
     *
     * @param string $string
     * @return string
     */
    public static function htmlEscape(string $string): string
    {
        return UTF8::html_escape($string);
    }

    /**
     * Insert string ($value) before specified substring ($search) in $subject.
     *
     * Str::insertBefore('csv', '.', 'my-filecsv'); // 'my-file.csv'
     *
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
     * WARNING: beta version of method
     *
     * Returns true when strings "similar"
     * (by case-insensitive, whitespace-less and symbol-less comparison).
     * Allow 85% of similarity and max 3 typos.
     *
     * Str::isSimilar('понедельник', ' О Не деЛЬ НИк!!! '); // true
     * Str::isSimilar('понедельник', 'pоне"del"nik(:'); // true
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public static function isSimilar(string $str1, string $str2): bool
    {
        /*
         * Handle equal strings ('строка' == 'строка')
         */
        if ($str1 === $str2) {
            return true;
        }

        /*
         * Handle case-insensitive comparison ('строка' == 'СТРОКА')
         */
        $str1 = self::lower($str1);
        $str2 = self::lower($str2);

        if ($str1 === $str2) {
            return true;
        }

        /*
         * Handle whitespace-less and symbol-less comparison ('строка' == 'с.трока! ~ ~`')
         */
        $str1 = self::stripSymbols($str1);
        $str2 = self::stripSymbols($str2);

        if ($str1 === $str2) {
            return true;
        }

        /*
         * Handle 'строка' == 'строк' (with few typos)
         */

        // Try to decide Levenshtein distance.
        // levenshtein() can accept only small strings.
        $str1Len = self::length($str1);
        $str2Len = self::length($str2);

        if ($str1Len <= self::MAX_LEVENSHTEIN_STR_LEN && $str2Len <= self::MAX_LEVENSHTEIN_STR_LEN) {
            $distance = self::levenshtein($str1, $str2);

            // Strings are equal
            if ($distance === 0) {
                return true;
            }

            // Just few typos
            if ($distance <= 3) {
                // Decide how many typos are allowed
                $minStrLen = min($str1Len, $str2Len);
                $minSimilarChars = (int) round(85.0 * $minStrLen / 100);

                // Distance is small
                if (($minStrLen - $minSimilarChars) >= $distance) {
                    return true;
                }
            }
        }

        /*
         * Handle (partially) transliterated strings ('строка' == 'sтроkа')
         */

        if ($str1 === self::EMPTY || $str2 === self::EMPTY) {
            return false;
        }

        $firstChar1 = self::firstChar($str1);
        $firstChar2 = self::firstChar($str2);

        // Seems like first chars already in one language, so no need to ASCII-fy,
        // because compared via lower() + stripSymbols() or levenshtein() comparison
        if ($firstChar1 === $firstChar2) {
            return false;
        }

        // ASCII of first letters is not equal already
        if (self::ascii($firstChar1) !== self::ascii($firstChar2)) {
            return false;
        }

        $ascii1 = self::replace("'", self::EMPTY, self::ascii(self::after($str1, $firstChar1)));
        $ascii2 = self::replace("'", self::EMPTY, self::ascii(self::after($str2, $firstChar2)));

        // Compare all other letters
        return $ascii1 === $ascii2;
    }

    /**
     * Return the length of the given string.
     *
     * @param  string  $value
     * @return int|false Can return FALSE, if e.g. mbstring is not installed and we process invalid chars.
     */
    public static function length(string $value)
    {
        return UTF8::strlen($value);
    }

    /**
     * Calculate Levenshtein distance between two strings.
     *
     * @see https://php.net/manual/en/function.levenshtein.php
     *
     * @param string $str1
     * @param string $str2
     * @param int $insertionCost
     * @param int $replacementCost
     * @param int $deletionCost
     * @return int
     */
    public static function levenshtein(string $str1, string $str2, int $insertionCost = 1, int $replacementCost = 1, int $deletionCost = 1): int
    {
        return UTF8::levenshtein($str1, $str2, $insertionCost, $replacementCost, $deletionCost);
    }

    /**
     * Limit the number of characters in a string.
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit(string $value, int $limit = 255, string $end = self::EMPTY): string
    {
        return UTF8::str_limit($value, $limit, $end);
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower(string $value): string
    {
        return UTF8::strtolower($value);
    }

    /**
     * Strip whitespace (or other characters) from the beginning of a UTF-8 string.
     *
     * @param string $string
     * @param string|null $characters
     * @return string
     */
    public static function ltrim(string $string, string $characters = null): string
    {
        if ($characters === null) {
            return \ltrim($string);
        }

        return UTF8::ltrim($string, $characters);
    }

    /**
     * Normalizes to UTF-8 NFC, converting from WINDOWS-1252 when needed.
     *
     * @param string $string
     * @return string
     */
    public static function normalize(string $string): string
    {
        return UTF8::filter($string);
    }

    /**
     * Determine if a given string not contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    public static function notContains(string $haystack, $needles): bool
    {
        return !self::contains($haystack, $needles);
    }

    /**
     * Generate a "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     */
    public static function random(int $length = 16): string
    {
        return UTF8::get_random_string($length);
    }

    /**
     * Strip HTML and PHP tags from a string.
     *
     * @see http://php.net/manual/function.strip-tags.php
     *
     * @param string $string
     * @return string
     */
    public static function removeHtmlPhpTags(string $string): string
    {
        return UTF8::remove_html($string);
    }

    /**
     * Remove invisible characters from a string.
     *
     * Str::removeInvisibleCharacters("κόσ\0με"); // 'κόσμε'
     *
     * @param string $string
     * @return string
     */
    public static function removeInvisibleCharacters(string $string): string
    {
        return UTF8::remove_invisible_characters($string);
    }

    /**
     * Removes a prefix from the beginning of the string.
     *
     * @param string $string
     * @param string $prefix
     * @return string
     */
    public static function removePrefix(string $string, string $prefix): string
    {
        return UTF8::substr_left($string, $prefix);
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     * The original \str_replace() is already UTF-8 safe.
     *
     * @see https://php.net/manual/function.str-replace.php
     *
     * @param string|string[] $search
     * @param string|string[] $replace
     * @param string|string[] $subject
     * @param int|null &$count
     * @return string|string[]
     */
    public static function replace($search, $replace, $subject, int &$count = null)
    {
        return \str_replace($search, $replace, $subject, $count);
    }

    /**
     * Find the position of the last occurrence of a substring in a string.
     *
     * @see http://php.net/manual/function.mb-strrpos.php
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return false|int
     */
    public static function rpos(string $haystack, string $needle, int $offset = 0)
    {
        return UTF8::strrpos($haystack, $needle, $offset);
    }

    /**
     * Strip whitespace (or other characters) from the end of a UTF-8 string.
     *
     * @param string $string
     * @param string|null $characters
     * @return string
     */
    public static function rtrim(string $string, string $characters = null): string
    {
        if ($characters === null) {
            return \rtrim($string);
        }

        return UTF8::rtrim($string, $characters);
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $title
     * @param  string  $separator
     * @param  string  $language
     * @return string
     */
    public static function slug(string $title, string $separator = '-', string $language = 'en'): string
    {
        return ASCII::to_slugify($title, $separator, $language);
    }

    /**
     * Convert a string to an array of unicode characters.
     *
     * @see https://php.net/manual/function.str-split.php
     *
     * @param string $string
     * @param int $length
     * @return string[]
     */
    public static function split(string $string, int $length = 1): array
    {
        return UTF8::str_split($string, $length);
    }

    /**
     * Begin a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $prefix
     * @return string
     */
    public static function start(string $value, string $prefix): string
    {
        return UTF8::str_ensure_left($value, $prefix);
    }

    /**
     * Remove all symbol characters (only letters and numbers will remain).
     *
     * @param string $str
     * @return string
     */
    public static function stripSymbols(string $str): string
    {
        return \preg_replace('/[^\pL\pN]+/u', self::EMPTY, $str);
    }

    /**
     * Strip all whitespace characters. This includes tabs and newline
     * characters, as well as multibyte whitespace such as the thin space
     * and ideographic space.
     *
     * @param string $string
     * @return string
     */
    public static function stripWhitespace(string $string): string
    {
        return UTF8::strip_whitespace($string);
    }

    /**
     * Get part of a string.
     *
     * @see http://php.net/manual/function.mb-substr.php
     *
     * @param string   $string     The string being checked.
     * @param int      $offset     The first position used in str.
     * @param int|null $length     [optional] The maximum length of the returned string.
     *
     * @return string|false The portion of $string specified by the offset and length parameters.
     *                      If $string is shorter than offset characters long, FALSE will be returned.
     */
    public static function substr(string $string, int $offset = 0, int $length = null)
    {
        return UTF8::substr($string, $offset, $length);
    }

    /**
     * Count the number of substring occurrences.
     *
     * @see http://php.net/manual/function.substr-count.php
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @param int|null $length
     * @return false|int
     */
    public static function substrCount(string $haystack, string $needle, int $offset = 0, int $length = null)
    {
        return UTF8::substr_count($haystack, $needle, $offset, $length);
    }

    /**
     * Replace text within a portion of a string.
     *
     * @see https://www.php.net/manual/function.substr-replace.php
     *
     * @param  string|string[] $string
     * @param  string|string[] $replace
     * @param  int|int[]       $offset
     * @param  int|int[]|null  $length
     * @return string|string[]
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        return UTF8::substr_replace($string, $replace, $offset, $length);
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a UTF-8 string.
     * This is slower then "trim()", but safe for >= 8-Bit strings.
     *
     * @see https://php.net/manual/function.trim.php
     *
     * @param string $string
     * @param string|null $characters
     * @return string
     */
    public static function trim(string $string, string $characters = null): string
    {
        if ($characters === null) {
            return \trim($string);
        }

        return UTF8::trim($string, $characters);
    }

    /**
     * Returns the given string as an integer, or $fallback if the string isn't numeric.
     *
     * @param string $string
     * @param null $fallback
     * @return int|mixed
     */
    public static function toInt(string $string, $fallback = null)
    {
        return UTF8::to_int($string) ?? $fallback;
    }

    /**
     * Returns the given input as string, or $fallback if the input isn't int|float|string
     * and do not implement the "__toString()" method.
     *
     * @param mixed $var
     * @param null $fallback
     * @return string|mixed
     */
    public static function toString($var, $fallback = null)
    {
        return UTF8::to_string($var) ?? $fallback;
    }

    /**
     * Make a string's first character uppercase.
     *
     * @param  string  $string
     * @return string
     */
    public static function ucfirst(string $string): string
    {
        return UTF8::ucfirst($string);
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function upper(string $value): string
    {
        return UTF8::strtoupper($value);
    }

    /**
     * @param string $encoding Ex. windows-1251
     * @return string Ex. WINDOWS-1251
     */
    private static function normalizeEncoding(string $encoding): string
    {
        if ($encoding === '') {
            return '';
        }

        return \strtoupper(\trim($encoding));
    }
}
