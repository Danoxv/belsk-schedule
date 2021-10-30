<?php

use Src\Config\AppConfig;
use Src\Support\Security;

$ip = Security::filterInputString(INPUT_SERVER, 'REMOTE_ADDR');

if (!$ip) {
    return;
}

$datetime = gmdate('d-m-Y H:i:s');
$ua = Security::filterInputString(INPUT_SERVER, 'HTTP_USER_AGENT');
$uri = Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');

$visitsStorage = AppConfig::getInstance()->visitsStorageFile;

$fp = fopen($visitsStorage, 'a');
fputcsv($fp, [$datetime, $ip, $ua, $uri]);
fclose($fp);
