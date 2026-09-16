<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_log', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('barang_id');

            $table->foreign('barang_id')
                ->references('id')
                ->on('inventaris_barang')
                ->onDelete('cascade');

            $table->unsignedBigInteger('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->string('aksi');

            // Contoh: tambah, edit, hapus, pinjam, kembali
            $table->json('detail')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_log');
    }
};