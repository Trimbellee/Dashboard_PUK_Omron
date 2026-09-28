<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan duplikat lama dulu (kalau ada) sebelum constraint dipasang,
        // supaya migration tidak gagal karena data yang sudah ada.
        // Simpan entri PALING AWAL per (sesi_absensi_id, anggota_id), hapus sisanya.
        DB::statement('
            DELETE t1 FROM sesi_absensi_entri t1
            INNER JOIN sesi_absensi_entri t2
            WHERE t1.id > t2.id
              AND t1.sesi_absensi_id = t2.sesi_absensi_id
              AND t1.anggota_id = t2.anggota_id
        ');

        Schema::table('sesi_absensi_entri', function (Blueprint $table) {
            $table->unique(['sesi_absensi_id', 'anggota_id'], 'sesi_absensi_entri_unik');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_absensi_entri', function (Blueprint $table) {
            $table->dropUnique('sesi_absensi_entri_unik');
        });
    }
};