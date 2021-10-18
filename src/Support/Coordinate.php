<?php

namespace Src\Support;

use PhpOffice\PhpSpreadsheet\Exception;

class Coordinate extends \PhpOffice\PhpSpreadsheet\Cell\Coordinate
{
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
        try {
            [$col, $row] = parent::coordinateFromString($coordinate);
            $row = (int) $row;
            return [$col, $row];
        } catch (Exception $e) {
            return ['', 0];
        }
    }
}