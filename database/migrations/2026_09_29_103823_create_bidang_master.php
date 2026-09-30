<?php
// php artisan make:migration create_bidang_master

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bidang_master', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 50)->unique();     // kunci permanen, mis. bidang_1
            $t->string('nama');                   // label tampilan, harus persis dipilih di Pengajuan/Koperasi
            $t->unsignedInteger('urutan')->default(0);
            $t->boolean('aktif')->default(true);
            $t->string('kas_tujuan', 50);         // kode di kas_master — realisasi bidang ini masuk kredit kas mana
            $t->boolean('punya_insentif')->default(false); // hanya boleh 1 baris true
            $t->timestamps();
        });

        // snapshot per laporan, sama pola dengan konfigurasi_kas_json
        foreach (['laporan_bendahara', 'laporan_bendahara_arsip'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $b) => $b->json('bidang_json')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['laporan_bendahara', 'laporan_bendahara_arsip'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $b) => $b->dropColumn('bidang_json'));
        }
        Schema::dropIfExists('bidang_master');
    }
};