<?php

namespace Src\Support;

class Coordinate extends \PhpOffice\PhpSpreadsheet\Cell\Coordinate
{
    public const FIRST_COL = 'A';
    public const FIRST_ROW = 1;

    /**
     * @param string $column
     * @return string
     */
    public static function nextColumn(string $column): string
    {
        return ++$column;
    }

    /**
     * @param string $column
     * @return string
     */
    public static function prevColumn(string $column): string
    {
        return chr(ord($column)-1);
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
     * @return int
     */
    public static function prevRow(int $row): int
    {
        return $row - 1;
    }

    /**
     * @param string $start
     * @param string $end
     * @return array
     */
    public static function generateColumnsRange(string $start, string $end): array
    {
        $end++;
        $letters = [];
        while ($start !== $end) {
            $letters[] = $start++;
        }
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