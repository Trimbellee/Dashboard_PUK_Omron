<?php
// php artisan make:migration add_konfigurasi_kas_json

use App\Services\Kas\KasKonfigurasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tabel = ['laporan_bendahara', 'laporan_bendahara_arsip'];

    public function up(): void
    {
        foreach ($this->tabel as $t) {
            Schema::table($t, fn (Blueprint $b) => $b->json('konfigurasi_kas_json')->nullable());
        }

        // bekukan laporan yang sudah ada dengan konfigurasi master saat ini
        $konf = KasKonfigurasi::master();
        if (empty($konf['kas'])) return;

        $json = json_encode($konf, JSON_UNESCAPED_UNICODE);
        foreach ($this->tabel as $t) {
            DB::table($t)->whereNull('konfigurasi_kas_json')->update(['konfigurasi_kas_json' => $json]);
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $t) {
            Schema::table($t, fn (Blueprint $b) => $b->dropColumn('konfigurasi_kas_json'));
        }
    }
};