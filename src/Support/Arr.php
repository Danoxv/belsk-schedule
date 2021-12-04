<?php

namespace Src\Support;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * @param array $arr
     * @param $value
     * @param bool $strict
     */
    public static function unsetByValue(array &$arr, $value, bool $strict = true)
    {
        foreach (array_keys($arr, $value, $strict) as $key) {
            unset($arr[$key]);
        }
    }
}