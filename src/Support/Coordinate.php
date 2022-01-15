<?php
declare(strict_types=1);

namespace Src\Support;

use PhpOffice\PhpSpreadsheet\Exception;

class Coordinate extends \PhpOffice\PhpSpreadsheet\Cell\Coordinate
{
    public const FIRST_COL = 'A';
    private const LAST_COL = 'ZZZ';

    public const FIRST_ROW = 1;

    /**
     * @param string $column
     * @return string|null
     * @throws Exception
     */
    public static function nextColumn(string $column): ?string
    {
        $column = strtoupper($column); // multibyte support is not necessary here

        if ($column === self::LAST_COL) {
            return null;
        }

        return self::stringFromColumnIndex(self::columnIndexFromString($column) + 1);
    }

    /**
     * @param string $column
     * @return string|null
     * @throws Exception
     */
    public static function prevColumn(string $column): ?string
    {
        $column = strtoupper($column); // multibyte support is not necessary here

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

        if ($prevRow < self::FIRST_ROW) {
            throw new Exception('Row can not be less than ' . self::FIRST_ROW);
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
        $columns = [];

        $startIndex = self::columnIndexFromString($start);
        $endIndex = self::columnIndexFromString($end);

        do {
            $columns[] = self::stringFromColumnIndex($startIndex);
            $startIndex++;
        } while ($startIndex <= $endIndex);

        return $columns;
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
        $coordinate = strtoupper($coordinate); // multibyte support is not necessary here

        $col = Str::EMPTY;
        $row = 0;

        sscanf($coordinate, '%[A-Z]%d', $col, $row);

        return [$col, $row];
    }
}