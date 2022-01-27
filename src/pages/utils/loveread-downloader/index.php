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
    <h3>LoveRead.ec downloader</h3>
    <form method="post" action="/utils/loveread-downloader/download" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="link" class="form-label">Ссылка на книгу</label>
            <input name="link" type="text" class="form-control" id="link" aria-describedby="linkHelp" required="required" />
            <div id="linkHelp" class="form-text">
                Вставьте ссылку на книгу из <a href="http://loveread.ec/">loveread.ec</a>. Например, <pre>http://loveread.ec/view_global.php?id=2555&p=1</pre>.
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Скачать</button>
    </form>

    <a class="btn btn-primary" href="/utils" role="button">Утилиты</a>
    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>