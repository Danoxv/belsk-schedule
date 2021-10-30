<?php

use Src\Support\Helpers;

$visitsStorage = Src\Config\AppConfig::getInstance()->visitsStorageFile;

@unlink($visitsStorage);

Helpers::goToLocation('/system/visits');