<?php

use Src\Config\App;
use Src\Support\Security;

$ip = Security::filterInputString(INPUT_SERVER, 'REMOTE_ADDR');

if (!$ip) {
    return;
}

$datetime = date('d-m-Y H:i:s');
$ua = Security::filterInputString(INPUT_SERVER, 'HTTP_USER_AGENT');
$uri = Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');

$hitsStorage = App::getInstance()->hitsStorageFile;

$fp = fopen($hitsStorage, 'a');
fputcsv($fp, [$datetime, $ip, $ua, $uri]);
fclose($fp);
