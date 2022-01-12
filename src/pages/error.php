<?php
declare(strict_types=1);

use Src\Config\AppConfig;
use Src\Exceptions\TerminateException;
use Src\Support\Str;

/** @var TerminateException $exception */
$message = $exception->getMessage();

if (!$message) {
    $message = TerminateException::ABSTRACT_ERROR_MSG;
}

$message = Str::finish($message, '.');

$type = $exception->getType();
$config = AppConfig::getInstance();

$code = $exception->getCode();
if ($code) {
    http_response_code($code);
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Просмотр расписания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require ROOT . '/src/pages/components/common-js-css.php' ?>
    <style>
        #main-container {
            padding-top: 6px;
            padding-bottom: 6px;
        }
    </style>
</head>
<body>
<div class="container" id="main-container">
    <?php require ROOT . '/src/pages/components/dark-mode.php' ?>
    <div class='alert alert-<?= $type ?>' role='alert'>
        <?= $message ?>
    </div>
    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>