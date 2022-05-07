<?php
declare(strict_types=1);

use Src\Exceptions\TerminateException;
use Src\Support\Helpers;

$_start = microtime(true);

define('IS_CONSOLE', in_array(PHP_SAPI, ['cli', 'phpdbg'], true));

define('ROOT', dirname(__FILE__, 2));

require_once ROOT . '/vendor/autoload.php';

$_config = Src\Config\AppConfig::getInstance();

$_isCli = Helpers::isCli();

if (!$_isCli) {
    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
    header('X-XSS-Protection: 1; mode=block');
    // See https://www.w3.org/International/articles/http-charset/index
    header('Content-type: text/html; charset=utf-8');
}

if ($_config->debug || $_isCli) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

if ($_isCli) {
    // Is console request.
    require ROOT . '/src/console/index-console.php';
    die(0);
}

$_requestUri = Src\Support\Security::filterInputString(INPUT_SERVER, 'REQUEST_URI');
$_requestUri = Src\Support\Helpers::uriWithoutGetPart($_requestUri);

$_routes = require ROOT . '/src/Config/routes.php';

if (!$_isCli) {
    echo '<div class="card">
  <div class="card-body text-center navbar-brand">
    🇺🇦🇺🇦🇺🇦 <a href="https://ua-help.pp.ua/ru/" target="_blank">Помочь Украине</a> 🇺🇦🇺🇦🇺🇦
  </div>
</div>';
}

try {
    if (!isset($_routes[$_requestUri])) {
        throw new Src\Exceptions\TerminateException('Страница не найдена (404)', TerminateException::TYPE_WARNING, 404);
    }

    require_once ROOT . '/src/' . $_routes[$_requestUri];
} catch (Throwable $exception) {
    if (!$exception instanceof TerminateException) {
        $exception = Src\Exceptions\TerminateException::fromThrowable($exception);
    }

    require ROOT . '/src/pages/error.php';
}

require_once ROOT . '/src/pages/components/write-visit.php';
require_once ROOT . '/src/pages/components/cookie-alert.php';

$_memoryUsage = Helpers::formatBytes(memory_get_usage());

$_finish = round(microtime(true) - $_start, 2);

echo "<div class='card'>
  <div class='card-body text-end'>
    <code>v{$_config->version['number']} {$_config->version['stability']} | $_finish sec | $_memoryUsage | <a href='https://github.com/NewEXE/belsk-schedule'>GitHub</a></code>
  </div>
</div>";