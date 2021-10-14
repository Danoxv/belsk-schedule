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
        //->setReadDataOnly(true) // Не считывать стили, размеры ячеек и т.д. - только их содержимое
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
        'forceMendeleeva4' => $forceMendeleeva
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

echo "<h3>$inputGroup</h3><hr />";

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
        /** @var Lesson $lesson */
        foreach ($pair->getLessons() as $lesson) {
            echo '<td>' . $lesson->getNumber()           . '</td>';
            echo '<td>' . $lesson->getTime()           . '</td>';
            echo '<td>' . nl2br($lesson->getSubject())        . '</td>';
            echo '<td>' . nl2br($lesson->getTeacher()) . '</td>';
            echo '<td>' . nl2br($lesson->getAuditory()). '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
}

echo '</div> <!-- /container-fluid -->
</body>
</html>';

die;

$scheduleData = [];

foreach ($worksheets as $sheet) {

    //$excelConfig = resolveExcelConfig($sheet, $config);

//    if (!isExcelConfigProcessable($excelConfig)) {
//        continue;
//    }

    $sheetTitle = trim($sheet->getTitle());

    $firstColumn = $excelConfig->firstGroupCol;
    $firstRow = $excelConfig->firstScheduleRow;

    $highestColRow = $sheet->getHighestRowAndColumn();
    $lastColumn = $highestColRow['column'];
    $lastRow = $highestColRow['row'];

    $columns = columnsRange($firstColumn, $lastColumn);
    $rows = rowsRange($firstRow, $lastRow);

    $hasMendeleeva4House = $forceMendeleeva || sheetHasMendeleeva4House($sheet);

    $groupColumnIsFound = false;
    foreach ($columns as $column) {
        // Optimization: we are already found and processed selected group.
        if ($groupColumnIsFound === true) {
            break(2);
        }

        $group = getCellValue($sheet->getCell($column.$excelConfig->groupNamesRow));

        if (empty($group) || $group !== $inputGroup) {
            continue;
        }

        $groupColumnIsFound = true;

        foreach ($rows as $row) {
            $cellCoordinate = $column.$row;
            $cell = $sheet->getCell($cellCoordinate);

            $cellValue = getCellValue($cell);

            $isInvisibleCell = isCellInvisible($cellCoordinate, $sheet);

            if (empty($cellValue) && !empty($excelConfig->classHourLessonColumn)) {
                // Hack: try to find any class hour lesson
                $cellValue = getCellValue($sheet->getCell($excelConfig->classHourLessonColumn.$row));
            }

            $isClassHour = isClassHourLesson($cellValue);
            if ($isClassHour) {
                $cellValue = formatClassHourLesson($cellValue);
            }

            if (Str::startsWith(trim($cellValue), $config->skipCellsThatStartsWith)) {
                $cellValue = '';
            }

            $timeRow = $row;
            do {
                $time = getCellValue($sheet->getCell($excelConfig->timeCol.$timeRow));

                if ((empty($time) && empty($cellValue) && $isInvisibleCell)) {
                    continue(2);
                }
                $timeRow--;
                if ($timeRow < 0) {
                    break;
                }
            } while (empty($time) || ($row - $timeRow > 2));

            if ($isClassHour) {
                $time = '';
            }

            if (!$isClassHour && !$time) {
                continue;
            }

            list('time' => $time, 'number' => $number) = parseTimeCellValue($time);

            $day = resolveDay($excelConfig->dayCol, $sheet, $row);

            list('subject' => $subject, 'teacher' => $teacher, 'auditory' => $auditory) = parseLessonCellValue($cellValue);

            $mendeleeva4House = false;
            if (!$isClassHour && $hasMendeleeva4House && $subject) {
                if ($forceMendeleeva) {
                    $mendeleeva4House = true;
                } else {
                    $cellColor = $sheet->getCell($cellCoordinate)->getStyle()->getFill()->getEndColor()->getRGB();
                    if (in_array($cellColor, $config->mendeleeva4HouseCellColors, true)) {
                        $mendeleeva4House = true;
                    }
                }
            }

            $nextLesson = [
                'sheetTitle'        => Security::sanitize($sheetTitle),
                'cell'              => Security::sanitize($cellCoordinate),

                'day'               => Security::sanitize($day),
                'number'            => Security::sanitize($number),
                'time'              => Security::sanitize($time),
                'subject'           => Security::sanitize($subject) . ($debug ? " [$cellCoordinate]" : ''),
                'teacher'           => Security::sanitize($teacher),
                'auditory'          => Security::sanitize($auditory),
                'mendeleeva4House'  => Security::sanitize($mendeleeva4House),
            ];

            if (!isset($scheduleData[$group])) {
                $scheduleData[$group] = [];
            }

            $lastKey = array_key_last($scheduleData[$group]);
            if ($lastKey !== null) {
                $prevLesson = &$scheduleData[$group][$lastKey];

                if ($prevLesson['time'] === $nextLesson['time'] && $prevLesson['number'] === $nextLesson['number']) {
                    $prevLesson['subject'] = Str::empty($prevLesson['subject']) . PHP_EOL . Str::empty($nextLesson['subject']). PHP_EOL . ($debug ? "[{$prevLesson['cell']}]-[$cellCoordinate]" : '');
                    $prevLesson['teacher'] = Str::empty($prevLesson['teacher']) . PHP_EOL . Str::empty($nextLesson['teacher']). PHP_EOL;
                    $prevLesson['auditory'] = Str::empty($prevLesson['auditory']) . PHP_EOL . Str::empty($nextLesson['auditory']). PHP_EOL;
                    $prevLesson['mendeleeva4House'] = $nextLesson['mendeleeva4House'];
                    continue;
                }
            }

            $scheduleData[$group][] = $nextLesson;
        }
    }
}