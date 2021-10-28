<?php

$hitsStorage = Src\Config\AppConfig::getInstance()->hitsStorageFile;

@unlink($hitsStorage);

\Src\Support\Helpers::goToLocation('/status/hits');