<?php

use Src\Support\Helpers;
use Src\Support\Path;
use Src\Support\Str;

if (!defined('ROOT')) {
    _printUsageExample();
}

if (!Helpers::isCli()) {
    var_dump('Must be executed from console');
    _printUsageExample();
}

$scriptName = $argv[1] ?? '';

if (empty($scriptName)) {
    _printUsageExample();
}

$scriptName = Str::finish($scriptName, '.php');

$scriptFile = ROOT . '/src/scripts/' . $scriptName;
$scriptFile = Path::normalize($scriptFile);

if ($scriptFile === Path::normalize(__FILE__)) {
    die('Cannot run self');
}

if (!file_exists($scriptFile) || !is_file($scriptFile)) {
    die("File $scriptFile is not exists");
}

require_once ROOT . '/src/scripts/' . $scriptName;

function _printUsageExample() {
    die('Usage example: php public/index.php group-list/generate.php');
}