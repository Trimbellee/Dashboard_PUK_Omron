<?php

namespace App\Helpers;

/**
 * Sumber tunggal untuk data seragam anggota (kaos & kemeja).
 *
 * Dipakai bersama oleh form, validasi, import Excel, dan export,
 * supaya daftar ukuran & aturan jumlah (0, 1, 2, ... / Ya / Tidak) tidak tersebar di banyak tempat.
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

    /** Batas wajar jumlah kaos/kemeja per anggota (penangkal salah ketik). */
    public const JUMLAH_MAKS = 20;

    private const NILAI_YA = ['YA', 'Y', 'YES', 'TRUE', 'SUDAH', 'SUDAH PUNYA', 'PUNYA', 'ADA'];

    private const NILAI_TIDAK = ['TIDAK', 'N', 'NO', 'FALSE', 'BELUM', 'BELUM PUNYA', 'TIDAK PUNYA', 'TIDAK ADA'];

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
    | JUMLAH  (kaos / kemeja yang dimiliki)
    |--------------------------------------------------------------------------
    | 0 = belum punya (default kolom), 1, 2, 3, ... = jumlah yang dimiliki.
    | Kolom di database NOT NULL; null di sini hanya berarti "sel Excel kosong / tidak dikenali".
    */

    /**
     * Ubah isi sel Excel menjadi jumlah (int) atau null.
     *   angka   : 0, 1, 2, ...        → angka itu sendiri
     *   teks Ya : Ya, Y, Yes, Sudah   → 1
     *   teks Tdk: Tidak, N, Belum     → 0
     *   null    : kosong ATAU tidak dikenali
     *
     * Excel bisa mengirim boolean asli (TRUE/FALSE) atau angka (int/float),
     * jadi tipe-tipe itu ditangani lebih dulu sebelum dibersihkan sebagai teks.
     */
    public static function parseJumlah($value): ?int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value) || is_float($value)) {
            return self::angkaJumlah((float) $value);
        }

        $v = self::bersihkan($value);

        if ($v === null) {
            return null;
        }

        $v = strtoupper(preg_replace('/\s+/', ' ', $v));

        if (in_array($v, self::NILAI_YA, true))    return 1;
        if (in_array($v, self::NILAI_TIDAK, true)) return 0;

        if (preg_match('/^\d+(\.0+)?$/', $v)) {
            return self::angkaJumlah((float) $v);
        }

        return null;
    }

    /** true bila kosong (boleh) atau dikenali sebagai jumlah yang wajar. */
    public static function validJumlah($value): bool
    {
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return self::parseJumlah($value) !== null;
        }

        return self::bersihkan($value) === null
            || self::parseJumlah($value) !== null;
    }

    /** Teks tampilan: "Belum punya" (kosong atau 0) / "Sudah punya (n)". */
    public static function labelJumlah(?int $jumlah): string
    {
        if ($jumlah === null || $jumlah <= 0) return 'Belum punya';

        return "Sudah punya ({$jumlah})";
    }

    private static function angkaJumlah(float $n): ?int
    {
        if ($n < 0 || $n > self::JUMLAH_MAKS || floor($n) !== $n) {
            return null;
        }

        return (int) $n;
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