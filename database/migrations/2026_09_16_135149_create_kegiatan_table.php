<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();

            $table->enum('jenis', ['pendidikan', 'seminar', 'aksi', 'lainnya']);

            // Hanya diisi kalau jenis = pendidikan.
            $table->enum('tingkat_kategori', ['dasar', 'lanjutan', 'spesialis', 'lainnya'])->nullable();

            // Diisi gantian: nama spesialisasi (kalau tingkat_kategori = spesialis)
            // atau nama tingkat manual (kalau tingkat_kategori = lainnya). NULL untuk dasar/lanjutan.
            $table->string('tingkat_keterangan')->nullable();

            $table->longText('deskripsi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('lokasi')->nullable();

            // NULL = kuota tidak dibatasi.
            $table->unsignedInteger('kuota')->nullable();

            $table->enum('status', ['draft', 'dibuka', 'ditutup', 'selesai'])->default('draft');
            $table->string('thumbnail')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['jenis', 'tingkat_kategori']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};