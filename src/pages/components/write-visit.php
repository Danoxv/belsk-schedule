<?php
declare(strict_types=1);

use Src\Config\AppConfig;
use Src\Support\Security;
use Src\Support\Str;

$ip = Security::filterInputString(INPUT_SERVER, 'REMOTE_ADDR');

if (!$ip) {
    return;
}

$datetime = gmdate('d.m.Y H:i:s');
$ua = Security::filterInputString(INPUT_SERVER, 'HTTP_USER_AGENT');
$uri = Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');
$post = _getFormattedPost();

$visitsStorageFileTemplate = AppConfig::getInstance()->visitsStorageFileTemplate;

// Write visit to file by current ISO week number (W)
$visitsStorageFile = str_replace('{date}', gmdate('Y-m-W'), $visitsStorageFileTemplate);

$fp = fopen($visitsStorageFile, 'ab');
fputcsv($fp, [$datetime, $ip, $ua, $uri, $post]);
fclose($fp);

function _getFormattedPost(): string
{
    $post = Security::sanitizeArray($_POST);

    if (empty($post)) {
        return '';
    }

    $post = print_r($post, true);
    $post = Str::removePrefix($post, 'Array');
    $post = trim($post);
    $post = ltrim($post, '(');
    $post = rtrim($post, ')');

    return trim($post);
}