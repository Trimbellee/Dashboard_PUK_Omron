<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('anggota_id')->nullable()->after('id')->constrained('anggota')->nullOnDelete();
            $table->string('nik_karyawan')->nullable()->after('nama');
            $table->string('departemen')->nullable()->after('nik_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['anggota_id']);
            $table->dropColumn(['anggota_id', 'nik_karyawan', 'departemen']);
        });
    }
};