<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_absensi', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->json('kolom_ditampilkan');
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->string('dibuat_oleh')->nullable();
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sesi_absensi_entri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_absensi_id')->constrained('sesi_absensi')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->json('data');
            $table->enum('metode', ['web', 'scanner', 'nfc'])->default('web');
            $table->timestamps();

            $table->unique(['sesi_absensi_id', 'anggota_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_absensi_entri');
        Schema::dropIfExists('sesi_absensi');
    }
};