<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->string('lokasi')
                ->nullable()
                ->after('judul');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->dropColumn('lokasi');
        });
    }
};