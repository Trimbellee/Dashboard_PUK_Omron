<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            // Jumlah unit yang dipinjam dalam satu transaksi.
            // Default 1 agar data peminjaman lama tetap valid.
            $table->unsignedInteger('jumlah_pinjam')
                ->default(1)
                ->after('barang_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            $table->dropColumn('jumlah_pinjam');
        });
    }
};