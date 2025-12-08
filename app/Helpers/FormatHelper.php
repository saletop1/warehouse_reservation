<?php

namespace App\Helpers;

class FormatHelper
{
    public static function formatQuantity($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $floatValue = (float)$value;
        if (is_float($floatValue) && floor($floatValue) != $floatValue) {
            return number_format($floatValue, 2, '.', ',');
        }

        return number_format($floatValue, 0, '.', ',');
    }

    public static function formatUnit($unit)
    {
        $unit = strtoupper(trim($unit ?? ''));
        return $unit === 'ST' ? 'PC' : $unit;
    }
}
