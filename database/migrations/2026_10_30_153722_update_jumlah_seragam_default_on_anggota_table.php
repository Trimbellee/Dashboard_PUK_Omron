<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jumlah_kaos / jumlah_kemeja: default 0 dan WAJIB terisi (tidak lagi NULL).
     *
     *   0 = belum punya (default), 1, 2, 3, ... = jumlah dimiliki.
     *
     * Baris yang masih NULL diisi 0 lebih dulu (DB::table melewati soft delete,
     * jadi anggota yang sudah dihapus-lunak ikut terisi).
     *
     * Setelah ini kolomnya NOT NULL, jadi semua jalur tulis harus mengirim angka
     * (kosong → 0): form admin dan import anggota baru sudah disesuaikan di
     * AnggotaController::normalisasiJumlahSeragam().
     */
    public function up(): void
    {
        DB::table('anggota')->whereNull('jumlah_kaos')->update(['jumlah_kaos' => 0]);
        DB::table('anggota')->whereNull('jumlah_kemeja')->update(['jumlah_kemeja' => 0]);

        Schema::table('anggota', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_kaos')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('jumlah_kemeja')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Mengembalikan kolom ke boleh-NULL tanpa default. Baris yang tadinya NULL
     * dan sudah menjadi 0 TIDAK bisa dibedakan lagi dari yang memang 0.
     */
    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_kaos')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('jumlah_kemeja')->nullable()->default(null)->change();
        });
    }
};