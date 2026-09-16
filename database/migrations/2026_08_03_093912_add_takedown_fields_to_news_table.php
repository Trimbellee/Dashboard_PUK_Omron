<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->text('takedown_reason')->nullable()->after('published_at');
            $table->timestamp('takedown_at')->nullable()->after('takedown_reason');
            $table->foreignId('takedown_by')->nullable()->after('takedown_at')->constrained('anggota')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['takedown_by']);
            $table->dropColumn(['takedown_reason', 'takedown_at', 'takedown_by']);
        });
    }
};