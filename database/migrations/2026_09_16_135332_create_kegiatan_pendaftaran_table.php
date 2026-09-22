<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();

            // Sesuaikan nama tabel 'anggota' & kolomnya kalau berbeda di project asli.
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();

            $table->enum('status', ['diterima', 'ditolak_kuota', 'hadir'])->default('diterima');

            // Jawaban field custom kegiatan, keyed by kegiatan_custom_field.id,
            // mis. {"3": "L", "4": "Tidak ada"}.
            $table->json('custom_values')->nullable();

            $table->timestamps();

            // 1 anggota hanya bisa daftar sekali per kegiatan.
            $table->unique(['kegiatan_id', 'anggota_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_pendaftaran');
    }
};