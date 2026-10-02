<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ganti 'files' kalau tabel model App\Models\File Anda bernama lain.
    private string $table = 'files';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $t) {
            $t->unsignedBigInteger('size')->nullable()->after('path');
            $t->string('thumbnail')->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $t) {
            $t->dropColumn(['size', 'thumbnail']);
        });
    }
};