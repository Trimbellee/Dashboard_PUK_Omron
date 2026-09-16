<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('struktur_organisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('struktur_organisasi')->onDelete('cascade');
            $table->string('jabatan', 255);
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('struktur_organisasi');
    }
};