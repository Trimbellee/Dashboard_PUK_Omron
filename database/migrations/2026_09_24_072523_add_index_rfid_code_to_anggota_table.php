<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            // Index komposit untuk query live list + delta
            $table->index(['rfid_code', 'updated_at'], 'anggota_rfid_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropIndex('anggota_rfid_updated_idx');
        });
    }
};