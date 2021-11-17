<?php

use Src\Config\AppConfig;

$config = AppConfig::getInstance();
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
    <h3>Пользовательское соглашение</h3>
    <p>Пользуясь данным сервисом (сайтом), Вы соглашаетесь с правилами данного пользовательского соглашения, описанными ниже.</p>
    <p>Если Вы не согласны с данным соглашением, прекратите использовать сервис (сайт).</p>
    <h5>Сбор данных</h5>
    <p>Вы даёте согласие на сбор следующей информации о Вас, как о пользователе:</p>
    <ul>
        <li>Время посещения страницы сайта пользователем;</li>
        <li>IP-адрес пользователя;</li>
        <li>User-agent пользователя;</li>
        <li>Адрес посещенной страницы;</li>
        <li>Данные о просмотренном расписании: учебная группа, имя файла расписания, настройки.</li>
    </ul>
    <p>Файлы, загруженные на сервис (сайт), удаляются после обработки (не сохраняются на сервере).</p>
    <h5>Cookies</h5>
    <p>Сайт использует "куки" исключительно для удобства: сохраняет последнюю выбранную пользователем группу и файл для того,
        чтобы при повторном входе пользователя на главную страницу они уже были подставлены в форму как значения по-умолчанию.</p>
    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>