<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laporan_bendahara_arsip')) {

            Schema::create('laporan_bendahara_arsip', function (Blueprint $table) {
                $table->id();

                $table->foreignId('laporan_bendahara_id')
                    ->constrained('laporan_bendahara')
                    ->cascadeOnDelete();

                $table->unsignedTinyInteger('bulan');
                $table->unsignedSmallInteger('tahun');
                $table->unsignedInteger('versi_ke');

                $table->integer('cos')->default(0);
                $table->integer('pajak_bank')->default(0);

                $table->json('saldo_awal_json')->nullable();
                $table->json('debit_manual_json')->nullable();
                $table->json('kredit_manual_json')->nullable();
                $table->json('dana_lain_json')->nullable();
                $table->json('dana_sumbangan_json')->nullable();
                $table->json('insentif_json')->nullable();
                $table->json('debit_json')->nullable();
                $table->json('kredit_json')->nullable();
                $table->json('total_json')->nullable();
                $table->json('riwayat_json')->nullable();

                $table->string('pdf_url')->nullable();

                $table->string('bendahara_nama')->nullable();
                $table->timestamp('bendahara_ttd_at')->nullable();

                $table->string('sekretaris_nama')->nullable();
                $table->timestamp('sekretaris_ttd_at')->nullable();

                $table->string('ketua_nama')->nullable();
                $table->timestamp('ketua_ttd_at')->nullable();

                $table->string('dibuka_oleh')->nullable();
                $table->text('alasan_buka_kembali')->nullable();
                $table->timestamp('dibuka_at')->nullable();

                $table->timestamps();

                // Mencegah arsip versi yang sama tersimpan dua kali
                $table->unique(
                    ['laporan_bendahara_id', 'versi_ke'],
                    'laporan_bendahara_arsip_unique_version'
                );

                // Mempercepat pencarian arsip berdasarkan bulan & tahun
                $table->index(['bulan', 'tahun']);
            });

        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laporan_bendahara_arsip')) {
            Schema::dropIfExists('laporan_bendahara_arsip');
        }
    }
};