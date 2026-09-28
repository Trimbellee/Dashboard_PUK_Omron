<?php
// php artisan make:migration create_kas_master_and_parameter

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas_master', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 50)->unique();          // kunci permanen, mis. dana_sosial
            $t->string('nama');
            $t->unsignedInteger('urutan')->default(0);
            $t->boolean('aktif')->default(true);

            $t->string('saldo_awal_mode', 20)->default('carry_over'); // carry_over | nol
            $t->string('debit_mode', 20)->default('manual');          // manual | rumus
            $t->text('debit_rumus')->nullable();
            $t->string('kredit_mode', 20)->default('manual');         // manual | rumus
            $t->text('kredit_rumus')->nullable();

            $t->boolean('menerima_sumbangan')->default(false);
            $t->boolean('tampil_rincian')->default(false);
            $t->string('nama_bidang')->nullable();     // nama bidang di Pengajuan (dipakai tahap 3)

            $t->boolean('punya_pajak')->default(false);
            $t->string('label_pajak')->nullable();
            $t->string('tujuan_pajak')->nullable();

            $t->timestamps();
        });

        Schema::create('laporan_parameter', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 50)->unique();          // mis. PEMBAGI_COS
            $t->string('nama');
            $t->decimal('nilai', 14, 6)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_parameter');
        Schema::dropIfExists('kas_master');
    }
};