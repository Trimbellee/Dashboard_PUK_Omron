<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->string('jabatan_puk', 100)->nullable()->after('dibuat_oleh');
        });

        // Isi untuk sesi lama: cocokkan nama pembuat (dibuat_oleh) ke jabatan di tabel anggota.
        $jabatanPerNama = DB::table('anggota')->pluck('jabatan_puk', 'nama_lengkap');

        DB::table('sesi_absensi')->orderBy('id')->each(function ($sesi) use ($jabatanPerNama) {
            $jabatan = $jabatanPerNama[$sesi->dibuat_oleh] ?? null;

            if ($jabatan) {
                DB::table('sesi_absensi')->where('id', $sesi->id)->update(['jabatan_puk' => $jabatan]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->dropColumn('jabatan_puk');
        });
    }
};