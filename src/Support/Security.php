<?php

namespace Src\Support;

class Security
{
    /**
     * Strip HTML and PHP tags from a string and
     * convert all applicable characters to HTML entities.
     *
     * @param $var
     * @return string
     */
    public static function sanitize($var): string
    {
        $var = (string) $var;

        return htmlentities(strip_tags($var));
    }

    /**
     * @param int $type
     * @param string $varName
     * @return string
     */
    public static function safeFilterInput(int $type, string $varName): string
    {
        return trim(self::sanitize(filter_input($type, $varName, FILTER_SANITIZE_STRING)));
    }
}