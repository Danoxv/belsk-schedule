<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use Src\Exceptions\TerminateException;

$config = require ROOT . '/src/config/config.php';

$debug          = $config['debug'];
$maxFileSize    = $config['maxFileSize'];
$minFileSize    = $config['minFileSize'];
$allowedMimes   = $config['allowedMimes'];

$inputGroup = safeFilterInput(INPUT_POST, 'group');

if (empty($inputGroup)) {
    throw new TerminateException('Группа обязательна');
}

if (!in_array($inputGroup, $config['groupsList'], true)) {
    throw new TerminateException('Hack attempt', TerminateException::TYPE_DANGER);
}

$scheduleLink = safeFilterInput(INPUT_POST, 'scheduleLink');
if ($scheduleLink && !isScheduleLinkValid($scheduleLink, $config['pageWithScheduleFiles'], $config['allowedExtensions'])) {
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
if (strContains($originalFileName, 'Менделеева')) {
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

$worksheets = $spreadsheet->getAllSheets();

$scheduleData = [];

if ($debug) {
    echo '<pre>';
}

foreach ($worksheets as $sheet) {
    if(strStartsWith($sheet->getTitle(), 'МЕХАНИКИ,')) {
//        $a = $sheet->getCell('C46');
//        var_dump(getCellValue($a));
//        die;
    }

    $excelConfig = resolveExcelConfig($sheet, $config);

    if (!isExcelConfigProcessable($excelConfig)) {
        continue;
    }

    $sheetTitle = trim($sheet->getTitle());

    $firstColumn = $excelConfig['firstGroupCol'];
    $firstRow = $excelConfig['firstScheduleRow'];

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

        $group = getCellValue($sheet->getCell($column.$excelConfig['groupNamesRow']));

        if (empty($group) || $group !== $inputGroup) {
            continue;
        }

        $groupColumnIsFound = true;

        foreach ($rows as $row) {
            $cellCoordinate = $column.$row;
            $cell = $sheet->getCell($cellCoordinate);

            $cellValue = getCellValue($cell);

            $isInvisibleCell = isCellInvisible($cellCoordinate, $sheet);

            if (empty($cellValue) && !empty($excelConfig['classHourLessonColumn'])) {
                // Hack: try to find any class hour lesson
                $cellValue = getCellValue($sheet->getCell($excelConfig['classHourLessonColumn'].$row));
            }

            $isClassHour = isClassHourLesson($cellValue);
            if ($isClassHour) {
                $cellValue = formatClassHourLesson($cellValue);
            }

            if (strStartsWith(trim($cellValue), $config['skipCellsThatStartsWith'])) {
                $cellValue = '';
            }

            $timeRow = $row;
            do {
                $time = getCellValue($sheet->getCell($excelConfig['timeCol'].$timeRow));

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

            list('time' => $time, 'number' => $number) = parseTimeCellValue($time, $cellCoordinate);

            $day = resolveDay($excelConfig['dayCol'], $sheet, $row);

            list('subject' => $subject, 'teacher' => $teacher, 'auditory' => $auditory) = parseLessonCellValue($cellValue);

            $mendeleeva4House = false;
            if (!$isClassHour && $hasMendeleeva4House && $subject) {
                if ($forceMendeleeva) {
                    $mendeleeva4House = true;
                } else {
                    $cellColor = $sheet->getCell($cellCoordinate)->getStyle()->getFill()->getEndColor()->getRGB();
                    if (in_array($cellColor, $config['mendeleeva4HouseCellColors'], true)) {
                        $mendeleeva4House = true;
                    }
                }
            }

            $nextLesson = [
                'sheetTitle'        => sanitize($sheetTitle),
                'cell'              => sanitize($cellCoordinate),

                'day'               => sanitize($day),
                'number'            => sanitize($number),
                'time'              => sanitize($time),
                'subject'           => sanitize($subject) . ($debug ? " [$cellCoordinate]" : ''),
                'teacher'           => sanitize($teacher),
                'auditory'          => sanitize($auditory),
                'mendeleeva4House'  => sanitize($mendeleeva4House),
            ];

            if (!isset($scheduleData[$group])) {
                $scheduleData[$group] = [];
            }

            $lastKey = array_key_last($scheduleData[$group]);
            if ($lastKey !== null) {
                $prevLesson = &$scheduleData[$group][$lastKey];

                if ($prevLesson['time'] === $nextLesson['time'] && $prevLesson['number'] === $nextLesson['number']) {
                    $prevLesson['subject'] = showEmpty($prevLesson['subject']) . PHP_EOL . showEmpty($nextLesson['subject']). PHP_EOL . ($debug ? "[{$prevLesson['cell']}]-[$cellCoordinate]" : '');
                    $prevLesson['teacher'] = showEmpty($prevLesson['teacher']) . PHP_EOL . showEmpty($nextLesson['teacher']). PHP_EOL;
                    $prevLesson['auditory'] = showEmpty($prevLesson['auditory']) . PHP_EOL . showEmpty($nextLesson['auditory']). PHP_EOL;
                    $prevLesson['mendeleeva4House'] = $nextLesson['mendeleeva4House'];
                    continue;
                }
            }

            $scheduleData[$group][] = $nextLesson;
        }
    }
}

$lessons = $scheduleData[$inputGroup] ?? [];

if (empty($lessons)) {
    throw new TerminateException("Не найдено занятий для группы $inputGroup", TerminateException::TYPE_INFO);
}

$days = [
    'Понедельник',
    'Вторник',
    'Среда',
    'Четверг',
    'Пятница',
    'Суббота',
];

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

foreach ($config['messagesOnSchedule'] as $message) {
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

foreach ($days as $day) {
    $dayLessons = findAllWhere($lessons, ['day' => $day]);

    echo '<h4>' . $day . '</h4>';

    if (empty($dayLessons)) {
        continue;
    }

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
    foreach ($dayLessons as $lesson) {
        $technicalTitle = sprintf('%s [%s]', $lesson['cell'], $lesson['sheetTitle']);
        echo sprintf(
            '<tr title="%s" class="%s">',
            $lesson['mendeleeva4House'] ? 'Занятие на Менделеева, д. 4' : '',
            $lesson['mendeleeva4House'] ? 'table-success' : ''
        );
        echo "<td title='$technicalTitle'>" . sanitize($lesson['number']) . '</td>';
        echo "<td>" . $lesson['time']           . '</td>';
        echo "<td>" . nl2br($lesson['subject'])        . '</td>';
        echo "<td>" . nl2br($lesson['teacher']) . '</td>';
        echo "<td>" . nl2br($lesson['auditory']). '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

echo '</div> <!-- /container-fluid -->
</body></html>';