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
        Schema::create('organization_profile', function (Blueprint $table) {
            $table->id();

            $table->text('sejarah_singkat')->nullable();
            $table->text('visi')->nullable();
            $table->json('misi')->nullable(); // Array poin misi

            $table->string('periode_kepengurusan')->nullable();
            $table->string('alamat_sekretariat')->nullable();
            $table->string('email_sekretariat')->nullable();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('anggota')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_profile');
    }
};