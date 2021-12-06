<?php

namespace Src\Support;

use PhpOffice\PhpSpreadsheet\Exception;

class Coordinate extends \PhpOffice\PhpSpreadsheet\Cell\Coordinate
{
    public const FIRST_COL = 'A';
    private const LAST_COL = 'ZZZ';
    private const COL_CHARS_MAXLENGTH = 3;

    public const FIRST_ROW = 1;

    /**
     * @param string $column
     * @return string|null
     * @throws Exception
     */
    public static function nextColumn(string $column): ?string
    {
        if ($column === self::LAST_COL) {
            return null;
        }

        $nextColumn = self::stringFromColumnIndex(self::columnIndexFromString($column) + 1);

        // Column string index can not be longer than 3 characters
        if (Str::length($nextColumn) > self::COL_CHARS_MAXLENGTH) {
            return null;
        }

        return $nextColumn;
    }

    /**
     * @param string $column
     * @return string|null
     * @throws Exception
     */
    public static function prevColumn(string $column): ?string
    {
        if ($column === self::FIRST_COL) {
            return null;
        }

        return self::stringFromColumnIndex(self::columnIndexFromString($column) - 1);
    }

    /**
     * @param int $row
     * @return int
     */
    public static function nextRow(int $row): int
    {
        return $row + 1;
    }

    /**
     * @param int $row
     * @return int|null
     * @throws Exception
     */
    public static function prevRow(int $row): ?int
    {
        if ($row === self::FIRST_ROW) {
            return null;
        }

        $prevRow = $row - 1;

        if ($prevRow < 1) {
            throw new Exception('Row can not be less than 1');
        }

        return $prevRow;
    }

    /**
     * @param string $start
     * @param string $end
     * @return string[]
     * @throws Exception
     */
    public static function generateColumnsRange(string $start, string $end): array
    {
        $letters = [];

        $startIndex = self::columnIndexFromString($start);
        $endIndex = self::columnIndexFromString($end);

        do {
            $letters[] = self::stringFromColumnIndex($startIndex);
            $startIndex++;
        } while ($startIndex <= $endIndex);

        return $letters;
    }

    /**
     * @param int $start
     * @param int $end
     * @return int[]
     */
    public static function generateRowsRange(int $start, int $end): array
    {
        return range($start, $end);
    }

    /**
     * @param string $coordinate 'A1'
     * @return array ['A', 1]
     */
    public static function explodeCoordinate(string $coordinate): array
    {
        $col = '';
        $row = 0;

        sscanf($coordinate, '%[A-Z]%d', $col, $row);

        return [$col, $row];
    }
}