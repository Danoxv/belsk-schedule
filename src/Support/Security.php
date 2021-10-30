<?php

namespace Src\Support;

class Security
{
    /**
     * Strip HTML and PHP tags from a string and
     * convert all applicable characters to HTML entities.
     *
     * Source: @link https://www.oreilly.com/library/view/learning-php-mysql/9781491979075/
     *
     * @param $var
     * @param bool $applyTrim
     * @return string
     */
    public static function sanitizeString($var, bool $applyTrim = false): string
    {
        $var = (string) $var;

        if ($applyTrim) {
            $var = trim($var);
        }

        return htmlentities(strip_tags($var));
    }

    /**
     * Gets a specific external variable by name and filters it as string.
     * @link https://php.net/manual/en/function.filter-input.php
     *
     * @param int $type
     * One of INPUT_GET, INPUT_POST,
     * INPUT_COOKIE, INPUT_SERVER, or
     * INPUT_ENV.
     *
     * @param string $varName
     * Name of a variable to get.
     */
    public static function filterInputString(int $type, string $varName): string
    {
        $input = filter_input($type, $varName, FILTER_SANITIZE_STRING);
        $input = self::sanitizeString($input, true);

        return $input;
    }
}
