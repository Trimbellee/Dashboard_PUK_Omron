<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventaris_peminjaman', 'penerima_id')) {
            return;
        }

        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            $table->foreignId('penerima_id')
                ->nullable()
                ->after('petugas_id')
                ->constrained('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('inventaris_peminjaman', 'penerima_id')) {
            return;
        }

        Schema::table('inventaris_peminjaman', function (Blueprint $table) {
            $table->dropForeign(['penerima_id']);
            $table->dropColumn('penerima_id');
        });
    }
};