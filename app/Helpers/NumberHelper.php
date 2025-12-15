<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format quantity with proper decimal handling - Indonesian format
     * Format Indonesia: titik untuk ribuan, koma untuk desimal
     * Contoh: 3.000 (tiga ribu), 3,467 (tiga koma empat ratus enam puluh tujuh), 3 (tiga)
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
            // Angka bulat: gunakan titik sebagai pemisah ribuan
            return number_format($number, 0, ',', '.');
        }

        // Jika desimal, format dengan 3 digit, lalu hapus trailing zeros
        $formatted = number_format($number, $decimals, ',', '.');

        // Hapus nol trailing setelah koma
        $formatted = preg_replace('/(,\d*?)0+$/', '$1', $formatted);

        // Hapus koma jika tidak ada angka desimal
        $formatted = rtrim($formatted, ',');

        return $formatted;
    }

    /**
     * Format stock number specifically - Indonesian format
     * Contoh: 3.000, 3,467, 3
     */
    public static function formatStockNumber($number)
    {
        return self::formatQuantity($number, 3);
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

    /**
     * Format number for display with Indonesian thousands separator
     * Example: 1000 -> 1.000, 1234.567 -> 1.234,567
     */
    public static function formatNumberIndonesian($number, $decimalPlaces = 3)
    {
        return self::formatQuantity($number, $decimalPlaces);
    }
}
