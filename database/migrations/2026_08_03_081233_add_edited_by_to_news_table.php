<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('edited_by')
                  ->nullable()
                  ->after('approved_by_sekretaris')
                  ->constrained('anggota')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropColumn('edited_by');
        });
    }
};