<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data seragam anggota (kaos & kemeja).
     *
     * Kolom boolean sengaja NULLABLE tanpa default:
     *   NULL  = belum didata
     *   true  = sudah punya
     *   false = belum punya
     *
     * Dengan begitu "belum didata" tidak tercampur dengan "belum punya",
     * dan baris Excel yang kosong tidak menimpa data yang sudah terisi.
     */
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->string('ukuran_kaos', 10)->nullable()->after('pendidikan_terakhir');
            $table->string('ukuran_kemeja', 10)->nullable()->after('ukuran_kaos');
            $table->boolean('sudah_punya_kaos')->nullable()->after('ukuran_kemeja');
            $table->boolean('sudah_punya_kemeja')->nullable()->after('sudah_punya_kaos');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn([
                'ukuran_kaos',
                'ukuran_kemeja',
                'sudah_punya_kaos',
                'sudah_punya_kemeja',
            ]);
        });
    }
};