<?php
declare(strict_types=1);

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
     * @param string $str
     * @return string
     */
    public static function removeSymbols(string $str): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', $str);
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
     * WARNING: beta version of method
     *
     * Returns true when strings "similar"
     * (case-insensitive, whitespace-less and symbol-less comparison of strings).
     * Allow 85% of similarity and max 3 typos.
     *
     * isSimilar('понедельник', ' О Не деЛЬ НИк!!! '); // true
     * isSimilar('понедельник', 'поне"del"nik(:'); // true
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public static function isSimilar(string $str1, string $str2): bool
    {
        // Maybe we are lucky
        if ($str1 === $str2) {
            return true;
        }

        // Replace any symbol and convert strings to lowercase
        $str1 = self::removeSymbols(self::lower($str1));
        $str2 = self::removeSymbols(self::lower($str2));

        if ($str1 === $str2) {
            return true;
        }

        /*
         * Try to decide Levenshtein distance.
         */

        // levenshtein() can accept only small strings
        $str1Len = self::length($str1);
        $str2Len = self::length($str2);

        if ($str1Len <= 255 && $str2Len <= 255) {
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
         * Try to decide by ASCII-comparing
         * ('понедельник' == 'ponedelnik')
         */

        if ($str1 === '' || $str2 === '') {
            return false;
        }

        $firstChar1 = self::firstChar($str1);
        $firstChar2 = self::firstChar($str2);

        // Seems like first chars already in one language, so no need to ASCII-fy
        if ($firstChar1 === $firstChar2) {
            return false;
        }

        // First letter is not equal already
        if (self::ascii($firstChar1) !== self::ascii($firstChar2)) {
            return false;
        }

        // Compare all other letter
        return self::ascii(self::after($str1,$firstChar1)) === self::ascii(self::after($str2,$firstChar2));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function firstChar(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return self::substr($str, 0, 1);
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
        if ($str1 === $str2) {
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
     * Helper for self::levenshtein().
     * Convert an UTF-8 encoded string to a single-byte string.
     *
     * @see https://github.com/KEINOS/mb_levenshtein/blob/master/mb_levenshtein.php
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
        if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            return; // plain ascii string
        }

        // update the encoding map with the characters not already met
        $count = count($map);
        foreach ($matches[0] as $mbc) {
            if (!isset($map[$mbc])) {
                $map[$mbc] = chr(128 + $count);
                $count++;
            }
        }

        // finally remap non-ascii characters
        $str = strtr($str, $map);
    }
}