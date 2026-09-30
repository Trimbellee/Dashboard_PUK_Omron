<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE gallery MODIFY source ENUM('youtube','googledrive','tiktok') NOT NULL DEFAULT 'youtube'");
        DB::statement("ALTER TABLE gallery MODIFY thumbnail_url TEXT NULL");
        DB::statement("ALTER TABLE gallery MODIFY embed_url TEXT NOT NULL");
    }

    public function down(): void
    {
        // Pastikan tidak ada baris source = 'tiktok' sebelum rollback.
        DB::statement("ALTER TABLE gallery MODIFY source ENUM('youtube','googledrive') NOT NULL DEFAULT 'youtube'");
        DB::statement("ALTER TABLE gallery MODIFY thumbnail_url VARCHAR(255) NULL");
        DB::statement("ALTER TABLE gallery MODIFY embed_url VARCHAR(255) NOT NULL");
    }
};