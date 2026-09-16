<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            if (!Schema::hasColumn('laporan_bendahara', 'status')) {
                $table->string('status')->default('draft')->after('tahun');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'bendahara_nama')) {
                $table->string('bendahara_nama')->nullable()->after('status');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'bendahara_ttd_at')) {
                $table->timestamp('bendahara_ttd_at')->nullable()->after('bendahara_nama');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'sekretaris_nama')) {
                $table->string('sekretaris_nama')->nullable()->after('bendahara_ttd_at');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'sekretaris_ttd_at')) {
                $table->timestamp('sekretaris_ttd_at')->nullable()->after('sekretaris_nama');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'ketua_nama')) {
                $table->string('ketua_nama')->nullable()->after('sekretaris_ttd_at');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'ketua_ttd_at')) {
                $table->timestamp('ketua_ttd_at')->nullable()->after('ketua_nama');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'catatan_tolak')) {
                $table->text('catatan_tolak')->nullable()->after('ketua_ttd_at');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'ditolak_oleh')) {
                $table->string('ditolak_oleh')->nullable()->after('catatan_tolak');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'ditolak_at')) {
                $table->timestamp('ditolak_at')->nullable()->after('ditolak_oleh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            $drop = [];

            foreach ([
                'status',
                'bendahara_nama',
                'bendahara_ttd_at',
                'sekretaris_nama',
                'sekretaris_ttd_at',
                'ketua_nama',
                'ketua_ttd_at',
                'catatan_tolak',
                'ditolak_oleh',
                'ditolak_at',
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