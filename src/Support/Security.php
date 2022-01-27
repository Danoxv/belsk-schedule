<?php
declare(strict_types=1);

namespace Src\Support;

use Src\Config\AppConfig;
use voku\helper\UTF8;

class Security
{
    /**
     * Gets a specific external variable by name and filters it as string.
     * @link https://php.net/manual/function.filter-input.php
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
     * @param string $link
     * @return bool
     */
    public static function isScheduleLinkValid(string $link): bool
    {
        if ($link === Str::EMPTY) {
            return false;
        }

        $config = AppConfig::getInstance();

        return
            Str::endsWith($link, $config->allowedExtensions) &&
            Helpers::getHost($link) === Helpers::getHost($config->pageWithScheduleFiles);
    }

    /**
     * Walks the array while sanitizing the contents.
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
     * @param string $fileName
     * @return string
     */
    public static function sanitizeCsvFilename(string $fileName): string
    {
        if (!Str::endsWith($fileName, '.csv')) {
            return Str::EMPTY;
        }

        return self::normalizeFilename($fileName);
    }

    /**
     * Convert given string to safe filename (and keep string case).
     *
     * @param string $fileName
     * @return string
     */
    public static function normalizeFilename(string $fileName): string
    {
        return UTF8::to_filename($fileName, true);
    }

    /**
     * @param string $scheduleLink
     * @return string
     */
    public static function sanitizeScheduleLink(string $scheduleLink): string
    {
        // TODO Hacky, need to process other possible replacements.
        // urlencode() / rawurlencode() and many others doesn't work
        return Str::replace(Str::SPACE, '%20', $scheduleLink);
    }

    /**
     * 1) Cast input to string;
     * 2) normalizes to UTF-8 NFC, converting from WINDOWS-1252 when needed;
     * 3) strip HTML and PHP tags from a string;
     * 4) convert all applicable characters to HTML entities;
     * 5) remove invisible characters (like "\0");
     * 6) optionally apply >= 8-Bit safe trim().
     *
     * @param mixed $var
     * @param bool $applyTrim
     * @return string
     */
    public static function sanitizeString($var, bool $applyTrim = false): string
    {
        // 1) Cast input to string
        $var = (string) $var;

        $var =
            // 5) remove invisible characters (like "\0")
            Str::removeInvisibleCharacters(
            // 4) convert all applicable characters to HTML entities
                Str::htmlEscape(
                // 3) strip HTML and PHP tags from a string
                    Str::removeHtmlPhpTags(
                    // 2) normalizes to UTF-8 NFC, converting from WINDOWS-1252 when needed
                        Str::normalize($var)
                    )
                )
            );

        // 6) optionally apply multibyte-safe trim()
        if ($applyTrim) {
            $var = Str::trim($var);
        }

        return $var;
    }
}
