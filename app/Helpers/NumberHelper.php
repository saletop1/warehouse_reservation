<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format quantity with proper decimal handling
     */
    public static function formatQuantity($number, $decimals = 3)
    {
        // Jika null atau empty
        if (is_null($number) || $number === '') {
            return '0';
        }

        $number = floatval($number);

        // Cek apakah angka bulat (tanpa desimal)
        if (intval($number) == $number) {
            return number_format($number, 0);
        }

        // Jika desimal, format dengan 3 digit, lalu hapus trailing zeros
        $formatted = number_format($number, $decimals, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted;
    }

    /**
     * Remove leading zeros from PRO numbers
     * - Jika hanya angka: hapus leading zeros (001234 → 1234)
     * - Jika kombinasi huruf+angka: hapus leading zeros dari bagian angka (PRO001234 → PRO1234)
     * - Jika mengandung simbol atau karakter lain: kembalikan asli
     */
    public static function removeLeadingZeros($string)
    {
        // Jika null atau empty
        if (is_null($string) || $string === '') {
            return $string;
        }

        $string = (string) $string;
        $originalString = $string;

        // Trim spasi
        $string = trim($string);

        // Cek jika string mengandung simbol selain huruf dan angka
        if (preg_match('/[^a-zA-Z0-9]/', $string)) {
            // Jika ada simbol, kembalikan asli
            return $originalString;
        }

        // Cek pattern: huruf diikuti angka (contoh: "PRO123", "R001", "A100")
        if (preg_match('/^([a-zA-Z]*)(\d+)$/i', $string, $matches)) {
            $letters = $matches[1]; // bagian huruf (bisa kosong)
            $numbers = $matches[2]; // bagian angka

            // Hapus leading zeros dari bagian angka saja
            $cleanedNumbers = ltrim($numbers, '0');
            if ($cleanedNumbers === '') {
                $cleanedNumbers = '0';
            }

            return $letters . $cleanedNumbers;
        }

        // Jika hanya huruf (tanpa angka), kembalikan asli
        if (preg_match('/^[a-zA-Z]+$/i', $string)) {
            return $originalString;
        }

        // Fallback: hapus leading zeros jika hanya angka
        $result = ltrim($string, '0');
        return $result === '' ? '0' : $result;
    }

    /**
     * Clean PRO number - versi lebih sederhana untuk sources
     */
    public static function cleanProNumber($string)
    {
        return self::removeLeadingZeros($string);
    }
}
