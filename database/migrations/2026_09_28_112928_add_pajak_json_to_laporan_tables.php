<?php
// php artisan make:migration add_pajak_json_to_laporan_tables

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['laporan_bendahara', 'laporan_bendahara_arsip'] as $tabel) {
            Schema::table($tabel, function (Blueprint $t) {
                $t->json('pajak_json')->nullable();
            });

            DB::statement("UPDATE {$tabel} SET pajak_json = JSON_OBJECT('bunga_giro', pajak_bank)");
        }
    }

    public function down(): void
    {
        foreach (['laporan_bendahara', 'laporan_bendahara_arsip'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->dropColumn('pajak_json'));
        }
    }
};