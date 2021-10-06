<?php

$_start = microtime(true);

define('ROOT', dirname(__FILE__, 2));

require_once ROOT . '/vendor/autoload.php';

$_config = \Src\Config\Config::getInstance();

if ($_config->debug ?? false) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('error_reporting', E_ALL);
    error_reporting(E_ALL);
}
require_once ROOT . '/src/functions.php';

$_requestUri = Src\Support\Security::safeFilterInput(INPUT_SERVER, 'REQUEST_URI');
$_requestUri = strtok($_requestUri, '?'); // Берём REQUEST_URI без GET-параметров

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