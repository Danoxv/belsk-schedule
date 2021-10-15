<?php

use Src\Config\Config;
use Src\Config\SheetProcessingConfig;
use Src\Enums\Day;
use Src\Models\Group;
use Src\Models\Lesson;
use Src\Models\Pair;
use Src\Models\Sheet;
use Src\Support\Security;
use Src\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Src\Exceptions\TerminateException;

$config = Config::getInstance();

$debug          = $config->debug;
$maxFileSize    = $config->maxFileSize;
$minFileSize    = $config->minFileSize;
$allowedMimes   = $config->allowedMimes;

$inputGroup = Security::safeFilterInput(INPUT_POST, 'group');

if (empty($inputGroup)) {
    throw new TerminateException('Группа обязательна');
}

if (!in_array($inputGroup, $config->groupsList, true)) {
    throw new TerminateException('Hack attempt', TerminateException::TYPE_DANGER);
}

$scheduleLink = Security::safeFilterInput(INPUT_POST, 'scheduleLink');
if ($scheduleLink && !isScheduleLinkValid($scheduleLink, $config->pageWithScheduleFiles, $config->allowedExtensions)) {
    throw new TerminateException('Hack attempt', TerminateException::TYPE_DANGER);
}

$inputScheduleFile = $_FILES['scheduleFile'] ?? [];

$originalFileName = '';
if ($scheduleLink) {
    $originalFileName = $scheduleLink;

    $scheduleLink = str_replace(' ', '%20', $scheduleLink); // TODO простой хак, нужен нормальный urlencode

    // Скачать файл во временную папку
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $scheduleLink);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $st = curl_exec($ch);
    curl_close($ch);

    $temp = tmpfile();
    fwrite($temp, $st);

    $filePath = stream_get_meta_data($temp)['uri'];
} elseif (!empty($inputScheduleFile['tmp_name'])) {
    if (
        !in_array($inputScheduleFile['type'], $allowedMimes, true)  // файл не эксель
        || empty($inputScheduleFile['size'])                      // файл с нулевым размером или отсутствующим размером
        || $inputScheduleFile['size'] > ($maxFileSize * 1024)     // файл с размером больше, чем $maxFileSize килобайт
        || $inputScheduleFile['size'] < ($minFileSize * 1024)     // файл с размером меньше, чем $minFileSize килобайт
    ) {
        throw new TerminateException('Выбран недопустимый файл');
    }

    $originalFileName = $inputScheduleFile['name'];
    $filePath = $inputScheduleFile['tmp_name'];
} else {
    throw new TerminateException('Файл обязателен');
}

$forceMendeleeva = false;
if (Str::contains($originalFileName, 'Менделеева')) {
    $forceMendeleeva = true;
}

try {
    $reader = IOFactory::createReaderForFile($filePath)
        ->setReadDataOnly(false) // Считывать стили, размеры ячеек и т.д. - для определения Менделеева 4
    ;

    $spreadsheet = $reader->load($filePath);
} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    throw new TerminateException('Ошибка чтения файла: ' . $e->getMessage());
}

if ($debug) {
    echo '<pre>';
}

/*
 * Parsing
 */

/** @var ?Group $group */
$group = null;
foreach ($spreadsheet->getAllSheets() as $worksheet) {
    $sheet = new Sheet($worksheet, new SheetProcessingConfig([
        'studentsGroup' => $inputGroup,
        'forceApplyMendeleeva4ToLessons' => $forceMendeleeva
    ]));

    if ($sheet->hasGroups()) {
        $group = $sheet->getFirstGroup();
        break;
    }
}

if ($group === null) {
    throw new TerminateException("Группа $inputGroup не найдена в документе", TerminateException::TYPE_INFO);
}

/*
 * Rendering
 */

echo '
<!doctype html>
<html lang="ru">
<head>
    <title>Расписание</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
    <style>
        td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="sticky-sm-top clearfix">
        <a class="btn btn-sm btn-success float-end" href="/" role="button">Выбрать другой файл</a>
    </div>
';

foreach ($config->messagesOnSchedulePage as $message) {
    $type = $message['type'] ?? 'primary';
    $content = trim($message['content'] ?? '');
    echo "
    <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        {$content}
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Закрыть'></button>
    </div>
    ";
}

echo "<h3>{$group->getName()}</h3><hr />";

foreach (Day::getAll() as $day) {
    $dayPairs = $group->getPairsByDay($day);

    if ($dayPairs->isEmpty()) {
        continue;
    }

    echo '<h4>' . Day::format($day) . '</h4>';

    echo '<table class="table table-bordered table-sm table-hover">';
    echo '
<thead class="table-light">
<tr>
    <td><b>#</b></td>
    <td><b>Время</b></td>
    <td><b>Предмет</b></td>
    <td><b>Учитель</b></td>
    <td><b>Аудитория</b></td>
</tr>
</thead>';

    echo '<tbody>';

    /** @var Pair $pair */
    foreach ($dayPairs as $pair) {
        $lessonsCount = $pair->getLessons()->count();

        $lessonNum = 0;

        /** @var Lesson $lesson */
        foreach ($pair->getLessons() as $lesson) {
            if ($debug) {
                dump($lesson);
            }

            $mendeleevaHint = sprintf(
                ' title="%s" class="%s" ',
                $lesson->isMendeleeva4() ? 'Занятие на Менделеева, д. 4' : '',
                $lesson->isMendeleeva4() ? 'table-success' : ''
            );

            echo '<tr>';

            if ($lessonsCount === 1 || ($lessonsCount >= 2 && $lessonNum === 0)) {
                echo "<td rowspan='$lessonsCount'>" . $lesson->getNumber()          .'</td>';
                echo "<td rowspan='$lessonsCount'>" . $lesson->getTime()            .'</td>';
            }

            echo "<td $mendeleevaHint>" . $lesson->getSubject() .'</td>';
            echo '<td>' . nl2br($lesson->getTeacher())  .'</td>';

            $technicalTitle = sprintf(' title="%s" ', $lesson->getTechnicalTitle());

            if (empty($lesson->getAuditory())) {
                echo "<td $technicalTitle style='color: white'>.</td>";
            } else {
                echo "<td $technicalTitle>" . nl2br($lesson->getAuditory()) . '</td>';
            }

            echo '</tr>';

            $lessonNum++;
        }
    }

    echo '</tbody>';
    echo '</table>';
}

echo '</div> <!-- /container-fluid -->
</body>
</html>';