<?php

use Src\Support\Str;

function isScheduleLinkValid(string $link, string $pageWithScheduleFiles, array $allowedExtensions): bool
{
    return Str::endsWith($link, $allowedExtensions) && getHost($link) === getHost($pageWithScheduleFiles);
}

function getHost(string $link): string
{
    $urlParts = parse_url($link);
    return $urlParts['scheme'] . '://' . $urlParts['host'];
}