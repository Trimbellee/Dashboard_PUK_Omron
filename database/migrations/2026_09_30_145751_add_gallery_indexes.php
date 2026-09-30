<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Query publik: WHERE status = 'published' ORDER BY published_at
        Schema::table('gallery', function ($table) {
            $table->index(['status', 'published_at'], 'gallery_status_published_idx');
        });

        // Prefix index supaya aman untuk varchar(255) utf8mb4 di semua versi MySQL/MariaDB
        DB::statement('CREATE INDEX gallery_kategori_idx ON gallery (kategori(50))');
        DB::statement('CREATE INDEX gallery_kegiatan_idx ON gallery (kegiatan(100))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX gallery_kegiatan_idx ON gallery');
        DB::statement('DROP INDEX gallery_kategori_idx ON gallery');

        Schema::table('gallery', function ($table) {
            $table->dropIndex('gallery_status_published_idx');
        });
    }
};