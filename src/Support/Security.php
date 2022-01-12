<?php
declare(strict_types=1);

namespace Src\Support;

use Src\Config\AppConfig;
use voku\helper\UTF8;

class Security
{
    /**
     * Strip HTML and PHP tags from a string and
     * convert all applicable characters to HTML entities.
     *
     * Source: @link https://www.oreilly.com/library/view/learning-php-mysql/9781491979075/
     *
     * @param mixed $var
     * @param bool $applyTrim
     * @return string
     */
    public static function sanitizeString($var, bool $applyTrim = false): string
    {
        $var = (string) $var;

        $var = UTF8::html_escape(UTF8::strip_tags($var));
        $var = UTF8::remove_invisible_characters($var);

        if ($applyTrim) {
            $var = UTF8::trim($var);
        }

        return $var;
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
     * @return string
     */
    public static function filterInputString(int $type, string $varName): string
    {
        $input = filter_input($type, $varName);
        return self::sanitizeString($input, true);
    }

    /**
     * Walks the array while sanitizing the contents.
     *
     * Source: @link https://github.com/WordPress/WordPress/blob/master/wp-includes/functions.php#L1253 (add_magic_quotes())
     *
     * @param array $array Array to walk while sanitizing contents.
     * @return array Sanitized $array.
     */
    public static function sanitizeArray(array $array): array
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = self::sanitizeArray($v);
            } elseif (is_string($v)) {
                $array[$k] = self::sanitizeString($v);
            }
        }

        return $array;
    }

    /**
     * @param string $link
     * @return bool
     */
    public static function isScheduleLinkValid(string $link): bool
    {
        if ($link === '') {
            return false;
        }

        $config = AppConfig::getInstance();

        return
            Str::endsWith($link, $config->allowedExtensions) &&
            Helpers::getHost($link) === Helpers::getHost($config->pageWithScheduleFiles);
    }

    /**
     * @param string $scheduleLink
     * @return string
     */
    public static function sanitizeScheduleLink(string $scheduleLink): string
    {
        // TODO Hacky, need to process other possible replacements.
        // urlencode / rawurlencode and many others doesn't work
        return str_replace(' ', '%20', $scheduleLink);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public static function sanitizeCsvFilename(string $fileName): string
    {
        // Must contains one "dot" (before extension)
        if (!Str::containsOne($fileName, '.')) {
            return '';
        }

        // Remove any funky symbols (including "dot")
        $fileName = Str::slug($fileName);

        if ($fileName === '') {
            return '';
        }

        // Revert "dot" symbol
        return Str::insertBefore('csv', '.', $fileName);
    }
}
