<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: ubah ENUM dengan menambahkan 'takedown'
        DB::statement("ALTER TABLE news MODIFY COLUMN status ENUM('draft', 'revisi', 'submitted_bidang5', 'submitted_sekretaris', 'published', 'takedown') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Rollback ke ENUM sebelumnya tanpa 'takedown'
        DB::statement("ALTER TABLE news MODIFY COLUMN status ENUM('draft', 'revisi', 'submitted_bidang5', 'submitted_sekretaris', 'published') NOT NULL DEFAULT 'draft'");
    }
};