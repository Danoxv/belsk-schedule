<?php

namespace Src\Support;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * @param array $arr
     * @param mixed $value
     * @param bool $strict
     */
    public static function unsetByValue(array &$arr, $value, bool $strict = true): void
    {
        foreach (array_keys($arr, $value, $strict) as $key) {
            unset($arr[$key]);
        }
    }
}