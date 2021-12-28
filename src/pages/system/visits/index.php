<?php

use Src\Config\AppConfig;
use Src\Support\Str;

$config = AppConfig::getInstance();

$dir = dirname($config->visitsStorageFileTemplate);

$files = scandir($dir, SCANDIR_SORT_DESCENDING);

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
        /* Something like table grid layout */
        .tbl-15 {
            width: 15%;
            min-width: 15%;
            max-width: 15%;
        }
    </style>
</head>
<body>
<div class="container" id="main-container">
    <?php require ROOT . '/src/pages/components/dark-mode.php' ?>

    <table class="table table-bordered table-sm table-hover">
        <thead class="table-light">
        <tr>
            <td class="text-center"><b>Файл</b></td>
            <td class="text-center tbl-15"><b>Действия</b></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($files as $file): ?>
            <?php
                if (!Str::endsWith($file, '.csv')) {
                    continue;
                }

                $date = preg_replace('/[^0-9\-]+/', '', $file); // -2021-12-52
                $date = ltrim($date, '-');                              // 2021-12-52
                $date = explode('-', $date);                            // ['2021', '12', '52']

                $y = &$date[0];
                $w = &$date[2];

                $dt = new DateTime();
                $firstWeekDay = clone $dt->setISODate($y, $w, 0);
                $lastWeekDay = clone $dt->setISODate($y, $w, 6);

                $format = 'd.m.Y';
                $weekDaysRange = $firstWeekDay->format($format) . ' — ' . $lastWeekDay->format($format);
            ?>
            <tr>
                <td><a href="/system/visits/show?f=<?= $file ?>"><?= $file ?></a> (<?= $weekDaysRange ?>)</td>
                <td class="text-center"><a class='btn btn-danger' href='/system/visits/delete?f=<?= $file ?>' role='button'>x</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a class="btn btn-primary" href="/" role="button">На главную</a>
</div>
</body>
</html>