<?php

namespace App\Support;

class Format
{
    /** Contoh: 12000 -> "Rp12.000" (format SKPL-NF-11). */
    public static function rupiah(int|float $nilai): string
    {
        return 'Rp'.number_format($nilai, 0, ',', '.');
    }

    /**
     * Samakan format nomor kontak agar bisa dicocokkan.
     * "+62 812-9876-5432", "62812...", dan "0812 9876 5432" menjadi "081298765432".
     */
    public static function nomor(string $nomor): string
    {
        $angka = preg_replace('/\D+/', '', $nomor) ?? '';

        if (str_starts_with($angka, '62')) {
            $angka = '0'.substr($angka, 2);
        }

        return $angka;
    }
}
