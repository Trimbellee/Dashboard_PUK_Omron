<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sudah_punya_kaos / sudah_punya_kemeja (boolean)
     *   → jumlah_kaos / jumlah_kemeja (angka)
     *
     * Arti nilai: NULL = belum didata, 0 = belum punya, 1, 2, 3, ... = jumlah dimiliki.
     *
     * Data yang sudah masuk ikut dikonversi:
     *   true → 1,  false → 0,  NULL → NULL
     */
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_kaos')->nullable()->after('ukuran_kemeja');
            $table->unsignedSmallInteger('jumlah_kemeja')->nullable()->after('jumlah_kaos');
        });

        // Boolean (tinyint 0/1/NULL) langsung menjadi angka 0/1/NULL.
        DB::table('anggota')->update([
            'jumlah_kaos'   => DB::raw('sudah_punya_kaos'),
            'jumlah_kemeja' => DB::raw('sudah_punya_kemeja'),
        ]);

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['sudah_punya_kaos', 'sudah_punya_kemeja']);
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->boolean('sudah_punya_kaos')->nullable()->after('ukuran_kemeja');
            $table->boolean('sudah_punya_kemeja')->nullable()->after('sudah_punya_kaos');
        });

        // Jumlah > 0 → true, 0 → false, NULL → NULL (jumlah ≥ 2 kembali jadi "punya").
        DB::table('anggota')->update([
            'sudah_punya_kaos'   => DB::raw('CASE WHEN jumlah_kaos IS NULL THEN NULL WHEN jumlah_kaos > 0 THEN 1 ELSE 0 END'),
            'sudah_punya_kemeja' => DB::raw('CASE WHEN jumlah_kemeja IS NULL THEN NULL WHEN jumlah_kemeja > 0 THEN 1 ELSE 0 END'),
        ]);

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['jumlah_kaos', 'jumlah_kemeja']);
        });
    }
};