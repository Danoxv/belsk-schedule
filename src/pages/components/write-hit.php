<?php

use Src\Config\AppConfig;
use Src\Support\Security;

$ip = Security::filterInputString(INPUT_SERVER, 'REMOTE_ADDR');

if (!$ip) {
    return;
}

$datetime = date('d-m-Y H:i:s');
$ua = Security::filterInputString(INPUT_SERVER, 'HTTP_USER_AGENT');
$uri = Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');

$hitsStorage = AppConfig::getInstance()->hitsStorageFile;

$fp = fopen($hitsStorage, 'a');
fputcsv($fp, [$datetime, $ip, $ua, $uri]);
fclose($fp);
