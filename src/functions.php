<?php

use Src\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Find all elements in array matched to predicate.
 *
 * @param array $collection
 * @param array $predicate
 * @param bool $useStrictCompare
 * @return array
 */
function findAllWhere(array $collection, array $predicate = [], bool $useStrictCompare = true): array
{
    if (count($predicate) > 1) {
        throw new RuntimeException(
            'BUG HERE: incorrect work with several predicates! Find items when at least one predicate pass check'
        );
    }

    $found = [];

    $predicateKeys = array_keys($predicate);
    if (empty($collection) || empty($predicate) || empty($predicateKeys)) {
        return $found;
    }

    foreach ($collection as $model) {
        foreach ($predicateKeys as $predicateKey) {
            if(!$useStrictCompare && !$predicate[$predicateKey] && !($model[$predicateKey] ?? null) ) {
                $found[] = $model;
                continue;
            }

            if (
                array_key_exists($predicateKey, $model) &&
                (!$useStrictCompare || $model[$predicateKey] === $predicate[$predicateKey]) &&
                ($useStrictCompare || $model[$predicateKey] == $predicate[$predicateKey])
            ) {
                $found[] = $model;
            }
        }
    }

    return $found;
}

function getCellValue(Cell $cell, bool $rawValue = false): string
{
    $cellValue = (string) $cell;

    if ($rawValue) {
        return $cellValue;
    }

    return trim($cellValue);
}

function parseLessonCellValue(string $value): array
{
    $result = [
        'subject' => '',
        'teacher' => '',
        'auditory' => '',
    ];

    $parts = explode("\n", $value);

    foreach ($parts as &$part) {
        $part = trim($part);
        $part = Str::replaceManySpacesWithOne($part);
    }
    unset($part); // предотвратим сайд-эффекты (если не убрать ссылку)

    $firstPart = $parts[0];
    $result['subject'] = trim($firstPart ?? '');

    if (count($parts) >= 3) {
        $result['teacher'] = '';

        foreach ($parts as $k => $part) {
            if ($k === 0) continue; // уже обработано (как 'subject')

            $teacherAndAuditory = explodeTeacherAndAuditory($part);
            $result['auditory'] .= ($teacherAndAuditory['auditory'] . PHP_EOL);
            $result['teacher'] .= ($teacherAndAuditory['teacher'] . PHP_EOL);
        }

        return $result;
    }

    $result = array_merge(
        $result,
        explodeTeacherAndAuditory($parts[1] ?? '')
    );

    return $result;
}

function explodeTeacherAndAuditory(string $string): array
{
    $result = ['teacher' => '', 'auditory' => ''];

    $lastSpace = mb_strrpos($string, ' ');
    if ($lastSpace !== false) {
        $result['teacher'] = trim(mb_substr($string, 0, $lastSpace));
        $result['auditory'] = trim(mb_substr($string, $lastSpace));
    }

    return $result;
}

function parseTimeCellValue(string $value, $coord) {
    $value = Str::replaceManySpacesWithOne($value);
    $value = trim($value);

    $result = [
        'time' => '',
        'number' => '',
    ];

    if (empty($value)) {
        return $result;
    }

    $parts = explode(' ', $value);

    foreach ($parts as &$part) {
        $part = trim($part);
        $part = Str::replaceManySpacesWithOne($part);
    }

    if (!isset($parts[1])) {
        $result['time'] = formatTime($parts[0] ?? '');
        $result['number'] = '';
        return $result;
    }

    $result['time'] = formatTime($parts[1] ?? '');
    $result['number'] = $parts[0] ?? '';

    return $result;
}

function formatTime(string $time)
{
    $time = str_replace([
        '.',
        '-'
    ], [
        ':',
        ' - '
    ], $time);

    $time = Str::replaceManySpacesWithOne($time);

    return trim($time);
}

function formatDay(string $day)
{
    $day = mb_strtolower($day);
    return mb_strtoupper(mb_substr($day, 0, 1)).mb_substr($day, 1);
}

function columnsRange(string $start, string $end): array
{
    $end++;
    $letters = [];
    while ($start !== $end) {
        $letters[] = $start++;
    }
    return $letters;
}

function columnsRangeGenerator($lower, $upper) {
    ++$upper;
    for ($i = $lower; $i !== $upper; ++$i) {
        yield $i;
    }
}

function rowsRange(int $start, int $end): array
{
    return range($start, $end);
}

function nextColumn(string $column): string
{
    return ++$column;
}

function prevColumn(string $column): string
{
    return chr(ord($column)-1);
}

function nextRow(int $row): int
{
    return $row + 1;
}

function prevRow(int $row): int
{
    return $row - 1;
}

function resolveDay($dayCol, $sheet, $row)
{
    $dayRow = $row;
    do {
        $day = getCellValue($sheet->getCell($dayCol.$dayRow));

        // Hack: try to find day on the previous column also
        if ($dayCol !== 'A') {
            $dayPrevCol = getCellValue($sheet->getCell(prevColumn($dayCol).$dayRow));
            if (!empty($dayPrevCol)) {
                $day = $dayPrevCol;
            }
        }

        $dayRow--;
        if ($dayRow < 0) {
            break;
        }
    } while (empty($day));

    return formatDay($day);
}

function isClassHourLesson(string $lesson)
{
    if (empty($lesson)) {
        return false;
    }

    return formatClassHourLesson($lesson) === 'Классный час';
}

function formatClassHourLesson(string $lesson): string
{
    $lesson = trim($lesson);

    if (empty($lesson)) {
        return '';
    }

    $space = ' ';
    $uniqueChar = '|';

    $spacesCount = 10;
    $replacementPerformed = false;
    do {
        $lesson = str_replace(str_repeat($space, $spacesCount), $uniqueChar, $lesson, $count);

        if ($count > 0) {
            $replacementPerformed = true;
        }

        $spacesCount--;
    } while($count === 0 && $spacesCount > 0);

    $lesson = Str::removeSpaces($lesson);

    if ($replacementPerformed) {
        $lesson = str_replace($uniqueChar, $space, $lesson);
    }

    $lesson = mb_strtolower($lesson);
    return mb_strtoupper(mb_substr($lesson, 0, 1)).mb_substr($lesson, 1);
}

function getRowFromCoordinate(string $coordinate)
{
    [, $row] = Coordinate::coordinateFromString($coordinate);

    return $row;
}

function getColumnFromCoordinate(string $coordinate)
{
    [$column,] = Coordinate::coordinateFromString($coordinate);

    return $column;
}

function sheetHasMendeleeva4House(Worksheet $sheet):bool
{
    $firstColumn = 'A';
    $firstRow = 1;

    $highestColRow = $sheet->getHighestRowAndColumn();
    $lastColumn = $highestColRow['column'];
    $lastRow = $highestColRow['row'];

    $columns = columnsRange($firstColumn, $lastColumn);
    $rows = rowsRange($firstRow, $lastRow);
    $rows = array_reverse($rows);

    foreach ($columns as $column) {
        foreach ($rows as $row) {
            $cellValue = getCellValue($sheet->getCell($column.$row));

            if (empty($cellValue)) {
                continue;
            }

            $cellValue = mb_strtolower($cellValue);

            if (Str::contains($cellValue, 'менделеева') && strContains($cellValue, '4')) {
                return true;
            }
        }
    }

    return false;
}

/**
 *
 * @param string $coordinate
 * @param Worksheet $sheet
 * @return bool
 */
function isCellInvisible(string $coordinate, Worksheet $sheet): bool
{
    $cell = $sheet->getCell($coordinate);

    // В ячейке есть значение
    if (getCellValue($cell, true) !== '') {
        return false;
    }

    $range = $cell->getMergeRange();

    // Ячейка не объединена
    if (!$range) {
        return false;
    }

    $row = getRowFromCoordinate($coordinate);
    $column = getColumnFromCoordinate($coordinate);

    $prevRowCell = $sheet->getCell($column.prevRow($row));
    $prevRowRange = $prevRowCell->getMergeRange();

    // Ячейка объединена не с ячейкой на предыдущей строке
    if ($range !== $prevRowRange) {
        return false;
    }

    // Похоже, что ячейка невидима... Но это неточно.
    return true;
}

function nextRowHasFirstPairTime(string $coordinate, Worksheet $sheet, string $timeCol): bool
{
    $row = getRowFromCoordinate($coordinate);

    $time = getCellValue($sheet->getCell($timeCol.nextRow($row)));

    return isFirstPairTime($time);
}

function isFirstPairTime(string $cellValue)
{
    if (empty($cellValue)) {
        return false;
    }

    $parsed = parseTimeCellValue($cellValue, '');

    $pairNum = $parsed['number'];

    if ($pairNum && in_array($pairNum, ['I', '1'])) {
        return true;
    }

    $time = $parsed['time'];

    return Str::startsWith($time, '09:00 - 10:');
}

function isScheduleLinkValid(string $link, string $pageWithScheduleFiles, array $allowedExtensions): bool
{
    return Str::endsWith($link, $allowedExtensions) && getHost($link) === getHost($pageWithScheduleFiles);
}

function getHost(string $link): string
{
    $urlParts = parse_url($link);
    return $urlParts['scheme'] . '://' . $urlParts['host'];
}