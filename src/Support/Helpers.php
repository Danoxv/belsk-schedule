<?php

namespace Src\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
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

        if (empty($urlParts['host'])) {
            return '';
        }

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

    /**
     * Convert integer to Roman number.
     *
     * Source: @link https://stackoverflow.com/a/26298774
     *
     * @param int $num Ex.: 4
     * @return string Ex.: IV
     */
    public static function intToRoman(int $num): string
    {
        $res = '';

        $romanNumberMap = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1];

        foreach ($romanNumberMap as $roman => $number){
            //divide to get  matches
            $matches = intval($num / $number);

            //assign the roman char * $matches
            $res .= str_repeat($roman, $matches);

            //substract from the number
            $num = $num % $number;
        }

        return $res;
    }

    /**
     * @return array
     */
    public static function getScheduleFilesLinks(): array
    {
        $config = Config::getInstance();

        $pageWithFiles = $config->pageWithScheduleFiles;
        $html = self::httpGet($pageWithFiles);

        $links = [];

        if (!empty($html)) {
            $doc = new DOMDocument;

            @$doc->loadHTML($html);

            $xpath = new DOMXPath($doc);

            $entries = $xpath->query('//body//a');
            $host = Helpers::getHost($pageWithFiles);

            /** @var DOMElement[] $entries */
            foreach ($entries as $entry) {
                $linkUri = Security::sanitizeString($entry->getAttribute('href'));

                if (!Str::endsWith($linkUri, $config->allowedExtensions)) {
                    continue;
                }

                $linkUri = "$host/$linkUri";

                $linkText = Security::sanitizeString($entry->textContent);

                $links[] = [
                    'uri' => $linkUri,
                    'text' => $linkText,
                ];
            }
        }

        return $links;
    }

    /**
     * @return bool
     */
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @param string $str
     * @return bool
     */
    public static function isExternalLink(string $str): bool
    {
        return !empty(self::getHost($str));
    }

    /**
     * Source: @link https://www.php.net/manual/ru/function.memory-get-usage.php#96280
     *
     * @param int $size
     * @return string
     */
    public static function formatBytes(int $size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');

        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}