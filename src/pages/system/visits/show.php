<?php
declare(strict_types=1);

use Src\Config\AppConfig;
use Src\Exceptions\TerminateException;
use Src\Support\Security;
use Src\Support\Str;

$fileName = Security::filterInputString(INPUT_GET, 'f');
$fileName = Security::sanitizeCsvFilename($fileName);

if ($fileName === Str::EMPTY) {
    throw new TerminateException('GET param "f" is required');
}

$config = AppConfig::getInstance();

$visitsStorageFile = dirname($config->visitsStorageFileTemplate)."/$fileName";

@$handle = fopen($visitsStorageFile, 'rb');
if ($handle === false) {
    throw new TerminateException('Отсутствует файл '.$fileName, TerminateException::TYPE_WARNING);
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
            <td class="text-center tbl-15"><b>POST</b></td>
        </tr>
        </thead>
        <tbody>
            <?php while (($row = fgetcsv($handle, 10000)) !== false): ?>
                <tr>
                <?php foreach ($row as $col): ?>
                    <td><?= nl2br($col) ?></td>
                <?php endforeach; ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a class="btn btn-primary" href="/system/visits" role="button">Назад</a>
    <a class='btn btn-danger' href='/system/visits/delete?f=<?= $fileName ?>' role='button'>Удалить</a>
</div>
</body>
</html>

<?php
fclose($handle);