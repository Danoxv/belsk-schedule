<?php

use Src\Config\AppConfig;
use Src\Support\Security;
use Src\Support\Str;

$ip = Security::filterInputString(INPUT_SERVER, 'REMOTE_ADDR');

if (!$ip) {
    return;
}

$datetime = gmdate('d-m-Y H:i:s');
$ua = Security::filterInputString(INPUT_SERVER, 'HTTP_USER_AGENT');
$uri = Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');
$post = _getFormattedPost();

$visitsStorage = AppConfig::getInstance()->visitsStorageFile;

$fp = fopen($visitsStorage, 'a');
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