<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_peminjaman', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('barang_id');

            $table->foreign('barang_id')
                ->references('id')
                ->on('inventaris_barang')
                ->onDelete('cascade');

            $table->string('peminjam_nama');

            $table->string('peminjam_kontak')
                ->nullable();

            $table->foreignId('petugas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('tanggal_pinjam');

            $table->date('tanggal_kembali')
                ->nullable();

            $table->enum('status', [
                'aktif',
                'selesai',
                'terlambat'
            ])->default('aktif');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_peminjaman');
    }
};