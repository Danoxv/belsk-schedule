<?php
declare(strict_types=1);

use Src\Support\Helpers;
use Src\Support\Str;

if (!defined('ROOT')) {
    _printUsageExample();
}

if (!Helpers::isCli()) {
    var_dump('Must be executed from console');
    _printUsageExample();
}

$scriptName = $argv[1] ?? Str::EMPTY;

if (empty($scriptName)) {
    _printUsageExample();
}

$scriptName = Str::finish($scriptName, '.php');

$scriptFile = ROOT . '/src/console/scripts/' . $scriptName;

if (!file_exists($scriptFile) || !is_file($scriptFile)) {
    echo "File $scriptFile is not exists";
    die(2);
}

require_once $scriptFile;

function _printUsageExample() {
    echo 'Usage example: php public/index.php generate-group-list.php';
    die(1);
}