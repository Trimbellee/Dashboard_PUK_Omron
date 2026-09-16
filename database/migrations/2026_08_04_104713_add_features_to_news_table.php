<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // Featured / Headline
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('is_breaking')->default(false)->after('is_featured');
            $table->boolean('is_headline')->default(false)->after('is_breaking');
            
            // Scheduled Publishing
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            
            // SEO Metadata
            $table->string('meta_title')->nullable()->after('scheduled_at');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Activity Log
            $table->timestamp('last_edited_at')->nullable()->after('meta_description');
            $table->foreignId('last_edited_by')->nullable()->after('last_edited_at')->constrained('anggota')->nullOnDelete();
        });

        // Tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // News Tags pivot
        Schema::create('news_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Activity Logs table
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('loggable');
            $table->foreignId('user_id')->constrained('anggota')->onDelete('cascade');
            $table->string('action');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        // Update status enum
        DB::statement("ALTER TABLE news MODIFY COLUMN status ENUM('draft', 'revisi', 'submitted_bidang5', 'submitted_sekretaris', 'published', 'takedown', 'scheduled') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('news_tag');
        Schema::dropIfExists('tags');
        
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn([
                'is_featured', 'is_breaking', 'is_headline',
                'scheduled_at', 'meta_title', 'meta_description',
                'last_edited_at', 'last_edited_by'
            ]);
        });
    }
};