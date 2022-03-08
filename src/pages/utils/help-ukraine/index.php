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
    <h3>Как помочь Украине в войне с Россией</h3>
    <p>
        Идёт полномасштабный геноцид Украинского народа. Жестоко и подло бомбят мирное население, в мой дом попал снаряд.
    </p>
    <h5>1) Присоединяйся к DDoS-атакам на сайты русской пропаганды и инфраструктуры:</h5>
    <p>Внимание! Безопаснее заходить с VPN.</p>
    <p>Координированные атаки: 🇺🇦 <a href="https://t.me/itarmyofukraine2022" target="_blank">IT ARMY of Ukraine</a> (Telegram)</p>
    <ul>
        <li>
            <a href="https://gist.github.com/NewEXE/a284a7ca0c3a2ddd2894907bb1787c63" target="_blank">Python-скрипт</a>, необходим установлен Docker
        </li>
        <li>
            <a href="http://www.notwar.ho.ua/" target="_blank">http://www.notwar.ho.ua</a> - оптимальный вариант
        </li>
        <li>
            <a href="https://ban-dera.com/" target="_blank">https://ban-dera.com</a> - сразу начинается сильная бомбардировка
        </li>
        <li>
            <a href="https://lookquizru.xyz/" target="_blank">https://lookquizru.xyz</a> - похоже, работает некорректно
        </li>
    </ul>
    <br />
    <a class="btn btn-primary" href="/utils" role="button">Утилиты</a>
    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>