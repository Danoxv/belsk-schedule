<?php

namespace Src\Support;

use PhpOffice\PhpSpreadsheet\Exception;

class Coordinate extends \PhpOffice\PhpSpreadsheet\Cell\Coordinate
{
    public const FIRST_COL = 'A';
    public const FIRST_ROW = 1;

    /**
     * @param string $column
     * @return string
     * @throws Exception
     */
    public static function nextColumn(string $column): string
    {
        return self::stringFromColumnIndex(self::columnIndexFromString($column) + 1);
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
     */
    public static function prevRow(int $row): ?int
    {
        if ($row === self::FIRST_ROW) {
            return null;
        }

        return $row - 1;
    }

    /**
     * @param string $start
     * @param string $end
     * @return string[]
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