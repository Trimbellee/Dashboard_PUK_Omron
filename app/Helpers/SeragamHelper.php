<?php

namespace App\Helpers;

/**
 * Sumber tunggal untuk data seragam anggota (kaos & kemeja).
 *
 * Dipakai bersama oleh form, validasi, import Excel, dan export,
 * supaya daftar ukuran & aturan "Ya/Tidak" tidak tersebar di banyak tempat.
 */
class SeragamHelper
{
    /** Daftar ukuran baku (urutan = urutan tampil di dropdown). */
    public const UKURAN = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];

    /** Penulisan lain yang dianggap sama dengan ukuran baku. */
    private const ALIAS_UKURAN = [
        '2XL'    => 'XXL',
        'XXXL'   => '3XL',
        'XXXXL'  => '4XL',
        'XXXXXL' => '5XL',
    ];

    private const NILAI_YA = [
        'YA', 'Y', 'YES', 'TRUE', '1',
        'SUDAH', 'SUDAH PUNYA', 'PUNYA', 'ADA',
    ];

    private const NILAI_TIDAK = [
        'TIDAK', 'N', 'NO', 'FALSE', '0',
        'BELUM', 'BELUM PUNYA', 'TIDAK PUNYA', 'TIDAK ADA',
    ];

    /*
    |--------------------------------------------------------------------------
    | UKURAN
    |--------------------------------------------------------------------------
    */

    /**
     * Rapikan ukuran dari Excel / input lain.
     * Mengembalikan null bila kosong ATAU tidak dikenali.
     */
    public static function normalizeUkuran($value): ?string
    {
        $v = self::bersihkan($value);

        if ($v === null) {
            return null;
        }

        $v = strtoupper(str_replace([' ', '-'], '', $v));
        $v = self::ALIAS_UKURAN[$v] ?? $v;

        return in_array($v, self::UKURAN, true) ? $v : null;
    }

    /** true bila kosong (boleh) atau dikenali sebagai ukuran baku. */
    public static function validUkuran($value): bool
    {
        return self::bersihkan($value) === null
            || self::normalizeUkuran($value) !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | YA / TIDAK  (sudah punya kaos / kemeja)
    |--------------------------------------------------------------------------
    */

    /**
     * Ubah isi sel Excel menjadi true / false / null.
     *   true  : Ya, Y, Yes, TRUE, 1, Sudah, Punya, Ada
     *   false : Tidak, N, No, FALSE, 0, Belum, Belum Punya
     *   null  : kosong ATAU tidak dikenali
     *
     * Catatan: Excel bisa mengirim boolean asli (TRUE/FALSE) atau angka 1/0,
     * jadi tipe bool/angka ditangani lebih dulu sebelum dibersihkan sebagai teks.
     */
    public static function parseYaTidak($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            if ((float) $value === 1.0) return true;
            if ((float) $value === 0.0) return false;
            return null;
        }

        $v = self::bersihkan($value);

        if ($v === null) {
            return null;
        }

        $v = strtoupper(preg_replace('/\s+/', ' ', $v));

        if (in_array($v, self::NILAI_YA, true))    return true;
        if (in_array($v, self::NILAI_TIDAK, true)) return false;

        return null;
    }

    /** true bila kosong (boleh) atau dikenali sebagai Ya/Tidak. */
    public static function validYaTidak($value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value) || is_float($value)) {
            return self::parseYaTidak($value) !== null;
        }

        return self::bersihkan($value) === null
            || self::parseYaTidak($value) !== null;
    }

    /** Teks untuk export Excel: "Ya" / "Tidak" / "" (belum didata). */
    public static function labelYaTidak(?bool $value): string
    {
        if ($value === null) return '';

        return $value ? 'Ya' : 'Tidak';
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL
    |--------------------------------------------------------------------------
    */

    private static function bersihkan($value): ?string
    {
        $v = trim((string) $value);

        if ($v === '' || in_array(strtoupper($v), ['NULL', 'N/A', '-'], true)) {
            return null;
        }

        return $v;
    }
}