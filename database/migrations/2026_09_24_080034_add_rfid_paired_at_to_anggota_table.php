<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $t) {
            $t->timestamp('rfid_paired_at')->nullable()->after('rfid_code');
        });

        // Kode kosong ('') harus jadi NULL, kalau tidak unique index akan bentrok
        DB::table('anggota')->where('rfid_code', '')->update(['rfid_code' => null]);

        // Isi awal waktu pairing dari updated_at (tanpa menyentuh updated_at)
        DB::table('anggota')
            ->whereNotNull('rfid_code')
            ->update(['rfid_paired_at' => DB::raw('updated_at')]);

        Schema::table('anggota', function (Blueprint $t) {
            // rfid_code sudah UNIQUE di tabel — cukup index untuk urutan "terbaru"
            $t->index('rfid_paired_at', 'anggota_rfid_paired_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $t) {
            $t->dropIndex('anggota_rfid_paired_at_index');
            $t->dropColumn('rfid_paired_at');
        });
    }
};