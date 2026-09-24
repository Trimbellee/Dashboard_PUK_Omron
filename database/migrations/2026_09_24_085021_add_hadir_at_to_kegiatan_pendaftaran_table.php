<?php

use App\Models\KegiatanPendaftaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nama tabel diambil dari model supaya tidak perlu menebak (kegiatan_pendaftaran / kegiatan_pendaftarans / dst).
    private function tabel(): string
    {
        return (new KegiatanPendaftaran)->getTable();
    }

    public function up(): void
    {
        $tabel = $this->tabel();

        if (!Schema::hasColumn($tabel, 'hadir_at')) {
            Schema::table($tabel, function (Blueprint $t) {
                // Waktu check-in yang sebenarnya (updated_at ikut berubah tiap data diedit).
                $t->timestamp('hadir_at')->nullable()->after('status');
            });
        }

        // Isi awal dari updated_at untuk yang sudah hadir (tanpa menyentuh updated_at).
        DB::table($tabel)
            ->where('status', 'hadir')
            ->whereNull('hadir_at')
            ->update(['hadir_at' => DB::raw('updated_at')]);

        // Untuk query "daftar hadir terbaru per kegiatan" + hitung statistik.
        Schema::table($tabel, function (Blueprint $t) {
            $t->index(['kegiatan_id', 'status', 'hadir_at'], 'kp_kegiatan_status_hadir_idx');
        });
    }

    public function down(): void
    {
        $tabel = $this->tabel();

        Schema::table($tabel, function (Blueprint $t) {
            $t->dropIndex('kp_kegiatan_status_hadir_idx');
        });

        Schema::table($tabel, function (Blueprint $t) {
            $t->dropColumn('hadir_at');
        });
    }
};