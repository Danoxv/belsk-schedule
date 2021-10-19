<?php

use Src\Config\AppConfig;
use Src\Config\SheetProcessingConfig;
use Src\Enums\Day;
use Src\Models\Group;
use Src\Models\Lesson;
use Src\Models\Pair;
use Src\Models\Sheet;
use Src\Support\Helpers;
use Src\Support\Security;
use Src\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Src\Exceptions\TerminateException;

/*
 * Take settings from app config and user's input.
 */

$config = AppConfig::getInstance();

$debug          = $config->debug;
$maxFileSize    = $config->maxFileSize;
$minFileSize    = $config->minFileSize;
$allowedMimes   = $config->allowedMimes;

$inputGroup = Security::filterInputString(INPUT_POST, 'group');

if (empty($inputGroup)) {
    throw new TerminateException('Выберите группу');
}

if (!in_array($inputGroup, $config->groupsList, true)) {
    throw new TerminateException('Недопустимая группа', TerminateException::TYPE_DANGER);
}

$scheduleLink = Security::filterInputString(INPUT_POST, 'scheduleLink');
$scheduleLink = Helpers::sanitizeScheduleLink($scheduleLink);

if ($scheduleLink && !Helpers::isScheduleLinkValid($scheduleLink)) {
    throw new TerminateException('Недопустимая ссылка на файл расписания', TerminateException::TYPE_DANGER);
}

$inputScheduleFile = $_FILES['scheduleFile'] ?? [];

if ($scheduleLink && !empty($inputScheduleFile['tmp_name'])) {
    throw new TerminateException('Выберите либо файл с сервера техникума, либо с компьютера, но не оба сразу');
}

$detectMendeleeva4 = Security::filterInputString(INPUT_POST, 'detectMendeleeva4');
$detectMendeleeva4 = (bool) $detectMendeleeva4;

$originalFileName = '';
if ($scheduleLink) {
    $originalFileName = $scheduleLink;

    // Get file and save to temp dir
    $data = Helpers::httpGet($scheduleLink, $curlError);

    if ($data === null) {
        throw new TerminateException("Ошибка при получении файла с сервера. Ссылка: '$scheduleLink', ошибка '$curlError'");
    }

    $temp = tmpfile();
    fwrite($temp, $data);

    $filePath = stream_get_meta_data($temp)['uri'];
} elseif (!empty($inputScheduleFile['tmp_name'])) {
    if (
        !in_array($inputScheduleFile['type'], $allowedMimes, true)  // is not Excel file
        || empty($inputScheduleFile['size'])                      // empty size
        || $inputScheduleFile['size'] > ($maxFileSize * 1024)     // file biggest that $maxFileSize kb
        || $inputScheduleFile['size'] < ($minFileSize * 1024)     // file smallest that $minFileSize kb
    ) {
        throw new TerminateException('Выбран недопустимый файл');
    }

    $originalFileName = $inputScheduleFile['name'];
    $filePath = $inputScheduleFile['tmp_name'];
} else {
    throw new TerminateException('Выберите файл');
}

$forceMendeleeva = false;
if (Str::contains(Str::lower($originalFileName), $config->mendeleeva4KeywordInFilename)) {
    $forceMendeleeva = true;
}

/*
 * Parsing
 */

// Load Excel file into PHPSpreadsheet

try {
    $reader = IOFactory::createReaderForFile($filePath);

    if (!$detectMendeleeva4) {
        $reader->setReadDataOnly(true); // Disable parsing of cell's color, formatting etc.
    }

    $spreadsheet = $reader->load($filePath);
} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    throw new TerminateException('Ошибка чтения файла: ' . $e->getMessage());
}

if ($debug) {
    echo '<pre>';
}

// Parse Sheets, Groups, Pairs and Lessons

/** @var ?Group $group */
$group = null;
foreach ($spreadsheet->getAllSheets() as $worksheet) {
    $sheet = new Sheet($worksheet, new SheetProcessingConfig([
        'studentsGroup' => $inputGroup,
        'forceApplyMendeleeva4ToLessons' => $forceMendeleeva,
        'detectMendeleeva4' => $detectMendeleeva4
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js" integrity="sha512-YcsIPGdhPK4P/uRW6/sruonlYj+Q7UHWeKfTAkBW+g83NKM+jMJFJ4iAPfSnVp7BKD4dKMHmVSvICUbE/V1sSw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="/js/functions.js"></script>
    <style>
        td {
            vertical-align: middle;
        }
        
        /* Something like table grid layout */
        .tbl1 {
            width: 5%;
            min-width: 5%;
            max-width: 5%;
        }
        .tbl2 {
            width: 10%;
            min-width: 10%;
            max-width: 10%;
        }
        .tbl3 {
            width: 15%;
            min-width: 15%;
            max-width: 15%;
        }
    </style>
</head>
<body>
<div class="container-fluid" id="schedule-page-content">
    <div class="sticky-sm-top clearfix">
        <a class="btn btn-sm btn-success float-end" href="/" role="button">Выбрать другой файл</a>
    </div>
';

if (!$debug) {
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
}

echo "
<div class='row'>
    <div class='col-2'>
        <h3>{$group->getName()}</h3>
    </div>
    <div class='col-10'>
        <button class='btn btn-sm btn-secondary' onclick='saveSchedulePageAsPdf(\"{$group->getName()}\")'>Скачать PDF</button>
        <span class='form-text' id='orientation-info'></span>
    </div>
</div>
<div class='row'>
<hr />
</div>
";

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
    <td class="text-center tbl1"><b>#</b></td>
    <td class="text-center tbl2"><b>Время</b></td>
    <td class="text-center"><b>Предмет</b></td>
    <td class="text-center tbl3"><b>Учитель</b></td>
    <td class="text-center tbl2"><b>Аудитория</b></td>
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

            $hint = ' title="%s" class="%s" ';

            if ($lesson->isMendeleeva4()) {
                $hint = sprintf($hint,
                    'Занятие на Менделеева, д. 4',
                    'table-success',
                );
            } elseif ($lesson->isClassHour()) {
                $hint = sprintf($hint,
                    'Классный час',
                    'table-warning',
                );
            } else {
                $hint = '';
            }

            echo '<tr>';

            if ($lessonsCount === 1 || ($lessonsCount >= 2 && $lessonNum === 0)) {
                echo "<td rowspan='$lessonsCount' class='text-center'>" . $lesson->getNumber()          .'</td>';
                echo "<td rowspan='$lessonsCount' class='text-center'>" . $lesson->getTime()            .'</td>';
            }

            echo "<td $hint>" . $lesson->getSubject() .'</td>';
            echo "<td>" . $lesson->getTeachersAsString('<br />')  .'</td>';

            $technicalTitle = sprintf(' title="%s" ', $lesson->getTechnicalTitle());

            if (!$lesson->hasAuditories()) {
                echo "<td $technicalTitle style='color: white'>.</td>";
            } else {
                echo "<td $technicalTitle>" . $lesson->getAuditoriesAsString('<br />') . '</td>';
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