<?php

use Src\Support\Helpers;

$_start = microtime(true);

define('ROOT', dirname(__FILE__, 2));

require_once ROOT . '/vendor/autoload.php';

$_config = Src\Config\Config::getInstance();

$isCli = Helpers::isCli();

if ($_config->debug || $isCli) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('error_reporting', E_ALL);
    error_reporting(E_ALL);
}

if ($isCli) {
    // Is console request.
    require ROOT . '/src/scripts/index-console.php';
    die(0);
}

$_requestUri = Src\Support\Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');
$_requestUri = Src\Support\Helpers::uriWithoutGetPart($_requestUri);

$_routes = require ROOT . '/src/Config/routes.php';

try {
    if (!isset($_routes[$_requestUri])) {
        throw new Src\Exceptions\TerminateException('Страница не найдена (404)');
    }

    require_once ROOT . '/src/' . $_routes[$_requestUri];
} catch (Src\Exceptions\TerminateException $exception) {
    require ROOT . '/src/pages/show-error.php';
}

$_finish = round(microtime(true) - $_start, 2);

echo "<div class='card'>
  <div class='card-body'>
    <code>v{$_config->version['number']} {$_config->version['stability']} / Сгенерировано за $_finish сек.</code>
  </div>
</div>";
