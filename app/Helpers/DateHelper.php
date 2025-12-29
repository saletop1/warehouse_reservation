<?php
// app/Helpers/DateHelper.php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Format tanggal ke format Indonesia
     */
    public static function toIndoDateTime($date, $includeTime = true)
    {
        if (!$date) {
            return '';
        }

        $carbonDate = Carbon::parse($date)->setTimezone('Asia/Jakarta');

        if ($includeTime) {
            return $carbonDate->format('d/m/Y H:i:s');
        }

        return $carbonDate->format('d/m/Y');
    }

    /**
     * Format untuk modal transfer
     */
    public static function toModalFormat($date)
    {
        if (!$date) {
            return '';
        }

        return Carbon::parse($date)
            ->setTimezone('Asia/Jakarta')
            ->format('d/m/Y, H.i.s');
    }

    /**
     * Format untuk database (Y-m-d H:i:s)
     */
    public static function toDatabaseFormat($date)
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s');
    }

    /**
     * Format untuk tampilan dengan jam dan menit
     */
    public static function toTimeFormat($date)
    {
        if (!$date) {
            return '';
        }

        return Carbon::parse($date)
            ->setTimezone('Asia/Jakarta')
            ->format('H:i');
    }
}
