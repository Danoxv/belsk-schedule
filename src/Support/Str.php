<?php

namespace Src\Support;

class Str extends \Illuminate\Support\Str
{
    private const UTF_8 = 'UTF-8';

    /**
     * @param string $string
     * @return string
     */
    public static function removeWhiteSpace(string $string): string
    {
        $string = preg_replace('/\s+/', '', $string);

        return self::removeFunkyWhiteSpace($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function replaceManyWhiteSpacesWithOne(string $string): string
    {
        $string = preg_replace('/\s+/', ' ', $string);

        return self::removeFunkyWhiteSpace($string);
    }

    /**
     * Remove unprintable characters and invalid unicode characters.
     *
     * Remove any next entity:
     * \p{C} or \p{Other}: invisible control characters and unused code points.
     * - \p{Cc} or \p{Control}: an ASCII or Latin-1 control character: 0x00–0x1F and 0x7F–0x9F.
     * - \p{Cf} or \p{Format}: invisible formatting indicator.
     * - \p{Co} or \p{Private_Use}: any code point reserved for private use.
     * - \p{Cs} or \p{Surrogate}: one half of a surrogate pair in UTF-16 encoding.
     * - \p{Cn} or \p{Unassigned}: any code point to which no character has been assigned.
     *
     * Result examples:
     * "my\x00string"       => "mystring"      ("\x00" was replaced)
     * "s\ti.php"           => "si.php"        ("\ti" was replaced)
     * "some\x00/path.txt"  => "some/path.txt" ("\x00" was replaced)
     *
     * Source: @link https://gist.github.com/NewEXE/05c2cb337218d562133e9c715334972f
     * @param string $string
     * @return string
     */
    public static function removeFunkyWhiteSpace(string $string): string
    {
        // We do this check in a loop, since removing invalid unicode characters
        // can lead to new characters being created.
        $pattern = '#\p{C}+#u';
        while (preg_match($pattern, $string)) {
            $string = (string) preg_replace($pattern, '', $string);
        }

        return $string;
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
     * @param string|null $fallbackChar
     * @return string|null Fallback char or NULL (by default)
     */
    public static function getNotExistingChar(string $string, ?string $fallbackChar = null): ?string
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

        return $fallbackChar;
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
     * @param string $replace
     * @param int $offset
     * @param int $length
     * @return string
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = NULL): string
    {
        if ($length === null) {
            $length = self::length($string);
        }

        $startString = self::substr($string, 0, $offset);
        $endString = self::substr($string, $offset + $length, self::length($string));

        return $startString . $replace . $endString;
    }

    /**
     * Returns true when strings "semantically equals"
     * (case-insensitive, whitespaces-less comparison of strings).
     *
     * Equal stings example:
     * 'понедельник', ' П О Н е д е ЛЬ НИк ', ''
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public static function isSemanticallyEquals(string $str1, string $str2): bool
    {
        if ($str1 === $str2) {
            return true;
        }

        return self::lower(self::removeWhiteSpace($str1)) === self::lower(self::removeWhiteSpace($str2));
    }

    /**
     * Returns true when strings equals or "almost equals" (two characters inaccuracy allowed)
     *
     * @param string $str1
     * @param string $str2
     * @param float $minPercentForSimilarity
     * @return bool
     */
    public static function isSimilar(string $str1, string $str2, float $minPercentForSimilarity = 80.0): bool
    {
        if ($str1 === $str2) {
            return true;
        }

        if (self::isSemanticallyEquals($str1, $str2)) {
            return true;
        }

        $str1Len = self::length($str1);
        $str2Len = self::length($str2);

        // \levenshtein() can accept only small strings
        if ($str1Len > 255 || $str2Len > 255) {
            return false;
        }

        $str1 = self::lower(self::replaceManyWhiteSpacesWithOne($str1));
        $str2 = self::lower(self::replaceManyWhiteSpacesWithOne($str2));

        $maxStrLen = max($str1Len, $str2Len);

        $minSimilarChars = (int) round($minPercentForSimilarity * $maxStrLen / 100);

        $distance = self::levenshtein($str1, $str2);

        return ($maxStrLen - $minSimilarChars) > $distance;
    }

    /**
     * Calculate Levenshtein distance between two strings. Supports multibyte.
     *
     * @see https://php.net/manual/en/function.levenshtein.php
     * Source: @link https://github.com/KEINOS/mb_levenshtein
     *
     * @param string $str1
     * @param string $str2
     * @return int
     */
    public static function levenshtein(string $str1, string $str2): int
    {
        if ($str1 === '' && $str2 === '') {
            return 0;
        }

        $map = [];
        self::convertMbAscii($str1, $map);
        self::convertMbAscii($str2, $map);

        $distance = @\levenshtein($str1, $str2);

        if ($distance === -1) {
            throw new \RuntimeException('levenshtein(): Argument string(s) too long');
        }

        return $distance;
    }

    /**
     * Helper for self::levenshtein()
     *
     * @param string $str
     * @param array $map
     */
    private static function convertMbAscii(string &$str, array &$map): void
    {
        if ($str === '') {
            return;
        }

        // find all utf-8 characters
        $matches = [];
        if (! preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            return; // plain ascii string
        }

        // update the encoding map with the characters not already met
        foreach ($matches[0] as $mbc) {
            if (!isset($map[$mbc])) {
                $map[$mbc] = chr(128 + count($map));
            }
        }

        // finally remap non-ascii characters
        $str = strtr($str, $map);
    }
}