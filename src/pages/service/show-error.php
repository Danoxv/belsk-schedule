<?php

use Src\Config\AppConfig;
use Src\Support\Str;
use Src\Exceptions\TerminateException;

/** @var TerminateException $exception */
$message = $exception->getMessage();

if (!$message) {
    $message = 'Что-то пошло не так.';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="/js/common.js?v=<?= $config->version['number'] ?>"></script>
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