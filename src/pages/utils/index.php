<?php
declare(strict_types=1);

use Src\Config\AppConfig;

$config = AppConfig::getInstance();
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
    <h3>Утилиты</h3>
    <table class="table table-bordered table-sm table-hover">
        <tbody>
        <tr>
            <td>
                <a href="/utils/loveread-downloader">LoveRead.ec downloader</a>
            </td>
            <td>
                Скачать любую книгу из <a href="http://loveread.ec/">LoveRead</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="/utils/markdown-visualizer">Markdown visualizer</a>
            </td>
            <td>
                Визуализация и преобразование
                <a href="https://htmlacademy.ru/blog/boost/frontend/markdown">Markdown</a> в HTML
            </td>
        </tr>
        </tbody>
    </table>

    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>