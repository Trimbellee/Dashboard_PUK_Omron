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
        Schema::create('anggota_password_resets', function (Blueprint $table) {
            $table->foreignId('anggota_id')
                ->primary()
                ->constrained('anggota')
                ->cascadeOnDelete();
            $table->string('token');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // diisi manual di kode, nullable krn alasan MySQL strict mode
            $table->timestamp('created_at')->nullable();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_password_resets');
    }
};
