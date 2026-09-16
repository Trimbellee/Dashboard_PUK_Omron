<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            $table->enum('kondisi_kembali', [
                'baik',
                'rusak',
                'perbaikan'
            ])
            ->nullable()
            ->after('keterangan');

            $table->text('catatan_kembali')
                ->nullable()
                ->after('kondisi_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            $table->dropColumn([
                'kondisi_kembali',
                'catatan_kembali'
            ]);
        });
    }
};