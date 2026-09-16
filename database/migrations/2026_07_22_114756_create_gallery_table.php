<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gallery', function (Blueprint $table) {
            $table->id();

            $table->string('judul')->nullable();
            $table->string('gambar');
            $table->string('kegiatan')->nullable();

            // Workflow approval
            $table->enum('status', [
                'draft',
                'submitted_bidang5',
                'submitted_sekretaris',
                'published',
                'revisi'
            ])->default('draft');

            $table->text('catatan_revisi')->nullable();

            // Relasi user
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('anggota')
                ->nullOnDelete();

            $table->foreignId('reviewed_by_bidang5')
                ->nullable()
                ->constrained('anggota')
                ->nullOnDelete();

            $table->foreignId('approved_by_sekretaris')
                ->nullable()
                ->constrained('anggota')
                ->nullOnDelete();

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery');
    }
};