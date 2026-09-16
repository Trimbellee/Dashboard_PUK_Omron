<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePengajuanTable extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nama');
            $table->string('bidang');
            $table->string('tujuan');
            $table->longText('keperluan');

            $table->bigInteger('biaya');

            $table->string('lampiran_proposal')->nullable();
            $table->bigInteger('aktual_biaya')->nullable();
            $table->bigInteger('dikembalikan')->nullable();
            $table->string('lampiran_laporan')->nullable();

            $table->enum('status', ['Menunggu','Disetujui','Ditolak'])->default('Menunggu');
            $table->string('approver')->nullable();

            $table->text('catatan')->nullable();
            $table->timestamp('tanggal')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
}
