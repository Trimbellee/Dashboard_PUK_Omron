<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            if (!Schema::hasColumn('laporan_bendahara', 'catatan_revisi')) {
                $table->string('catatan_revisi', 500)->nullable()->after('status');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'ditolak_role')) {
                $table->string('ditolak_role')->nullable()->after('ditolak_oleh');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'riwayat_json')) {
                $table->json('riwayat_json')->nullable()->after('total_json');
            }

        });
    }

    public function down(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            $drop = [];

            foreach ([
                'catatan_revisi',
                'ditolak_role',
                'riwayat_json',
            ] as $column) {
                if (Schema::hasColumn('laporan_bendahara', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};