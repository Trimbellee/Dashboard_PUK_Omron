<?php

namespace App\Helpers;

class RfidHelper
{
    /**
     * Normalisasi RFID agar sama dengan format scanner.
     *
     * Contoh:
     * HP NFC:
     * 3682D44F
     *
     * Scanner:
     * 1339327030
     */
    public static function normalize(string $kode): string
    {
        $kode = strtoupper(trim($kode));

        if ($kode === '') {
            return '';
        }

        // Bersihkan karakter pemisah
        $clean = preg_replace('/[^A-F0-9]/', '', $kode);

        /**
         * Jika dari HP NFC:
         * 4 byte UID HEX
         * contoh:
         * 36 82 D4 4F
         *
         * Scanner membaca:
         * 4F D4 82 36
         */
        if (preg_match('/^[A-F0-9]{8}$/', $clean)) {

            $reverseHex = implode(
                '',
                array_reverse(str_split($clean, 2))
            );

            return (string) hexdec($reverseHex);
        }


        /**
         * Jika sudah dari scanner:
         * 1339327030
         */
        return preg_replace('/[^0-9]/', '', $kode);
    }
}