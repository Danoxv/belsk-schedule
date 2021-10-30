<?php

$hitsStorage = Src\Config\App::getInstance()->hitsStorageFile;

@unlink($hitsStorage);

\Src\Support\Helpers::goToLocation('/system/hits');