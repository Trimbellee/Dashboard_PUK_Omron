<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_barang', function (Blueprint $table) {
            $table->id();

            $table->string('kode_barang')
                ->nullable()
                ->unique();

            $table->string('nama_barang');

            $table->string('type');

            $table->string('kategori');

            $table->integer('jumlah')
                ->default(1);

            $table->enum('kondisi', [
                'baik',
                'rusak',
                'perbaikan'
            ])->default('baik');

            $table->enum('status', [
                'tersedia',
                'dipinjam',
                'rusak'
            ])->default('tersedia');

            $table->date('tanggal_pembelian')
                ->nullable();

            $table->decimal('harga_perolehan', 15, 2)
                ->nullable();

            $table->text('keterangan')
                ->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_barang');
    }
};