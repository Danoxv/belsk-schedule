<?php

use Src\Config\AppConfig;
use Src\Exceptions\TerminateException;

$config = AppConfig::getInstance();

$hitsStorage = $config->hitsStorageFile;

@$handle = fopen($hitsStorage, 'r');
if ($handle === false) {
    throw new TerminateException('Отсутствует лог файлов', TerminateException::TYPE_INFO);
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Просмотр расписания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="/js/functions.js?v=<?= $config->version['number'] ?>"></script>
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
            <td class="text-center tbl-15"><b>Datetime (UTC)</b></td>
            <td class="text-center tbl-15"><b>IP</b></td>
            <td class="text-center"><b>User agent</b></td>
            <td class="text-center tbl-15"><b>URI</b></td>
        </tr>
        </thead>
        <tbody>
            <?php while (($row = fgetcsv($handle, 10000)) !== false): ?>
                <tr>
                <?php foreach ($row as $col): ?>
                    <td><?= $col ?></td>
                <?php endforeach; ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a class="btn btn-primary" href="/" role="button">На главную</a>
    <a class='btn btn btn-danger' href='/system/hits/clean' role='button'>Очистить</a>
</div>
</body>
</html>

<?php
fclose($handle);