<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_custom_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();

            $table->string('label');
            $table->enum('tipe', ['text', 'select', 'number', 'checkbox'])->default('text');

            // Dipakai kalau tipe = select, mis. ["S","M","L","XL"].
            $table->json('opsi')->nullable();

            $table->boolean('wajib')->default(false);
            $table->unsignedInteger('urutan')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_custom_field');
    }
};