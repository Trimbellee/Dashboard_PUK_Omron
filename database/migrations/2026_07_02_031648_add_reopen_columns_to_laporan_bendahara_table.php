<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            if (!Schema::hasColumn('laporan_bendahara', 'dibuka_kembali_oleh')) {
                $table->string('dibuka_kembali_oleh')->nullable()->after('riwayat_json');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'dibuka_kembali_alasan')) {
                $table->text('dibuka_kembali_alasan')->nullable()->after('dibuka_kembali_oleh');
            }

            if (!Schema::hasColumn('laporan_bendahara', 'dibuka_kembali_at')) {
                $table->timestamp('dibuka_kembali_at')->nullable()->after('dibuka_kembali_alasan');
            }

        });
    }

    public function down(): void
    {
        Schema::table('laporan_bendahara', function (Blueprint $table) {

            $drop = [];

            foreach ([
                'dibuka_kembali_oleh',
                'dibuka_kembali_alasan',
                'dibuka_kembali_at',
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