<?php

use App\Helpers\FormatHelper;

if (!function_exists('format_quantity')) {
    function format_quantity($value)
    {
        return FormatHelper::formatQuantity($value);
    }
}

if (!function_exists('format_unit')) {
    function format_unit($unit)
    {
        return FormatHelper::formatUnit($unit);
    }
}

if (!function_exists('format_material_number')) {
    function format_material_number($materialNumber)
    {
        if (preg_match('/^[0-9]+$/', $materialNumber)) {
            return ltrim($materialNumber, '0');
        }

        return $materialNumber;
    }
}
