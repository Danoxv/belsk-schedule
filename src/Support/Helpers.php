<?php

namespace Src\Support;

use Src\Config\Config;

class Helpers
{
    /**
     * @param string $link
     * @return bool
     */
    public static function isScheduleLinkValid(string $link): bool
    {
        $config = Config::getInstance();

        return
            Str::endsWith($link, $config->allowedExtensions) &&
            self::getHost($link) === self::getHost($config->pageWithScheduleFiles);
    }

    /**
     * @param string $link
     * @return string
     */
    public static function getHost(string $link): string
    {
        $urlParts = parse_url($link);
        return $urlParts['scheme'] . '://' . $urlParts['host'];
    }

    /**
     * Get part before GET-params in URI.
     * So from "https://site.com/page?p1=v1&p2=v2"
     * "https://site.com/page" was returned.
     *
     * @param string $uri
     * @return string
     */
    public static function uriWithoutGetPart(string $uri): string
    {
        return strtok($uri, '?');
    }

    /**
     * @param string $link
     * @param int $timeout In seconds
     * @return ?string
     */
    public static function httpGet(string $link, int $timeout = 3): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = @curl_exec($ch);
        curl_close($ch);

        if (empty($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param string $scheduleLink
     * @return string
     */
    public static function sanitizeScheduleLink(string $scheduleLink): string
    {
        return str_replace(' ', '%20', $scheduleLink); // TODO простой хак, нужен нормальный urlencode
    }
}