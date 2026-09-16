<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gallery', function (Blueprint $table) {
            // Hapus kolom gambar (local upload)
            $table->dropColumn('gambar');

            // Tambahkan kolom baru
            $table->enum('type', ['image', 'video'])->after('id')->default('image');
            $table->enum('source', ['youtube', 'googledrive'])->after('type')->default('youtube');
            $table->string('external_id')->after('source')->nullable();
            $table->string('thumbnail_url')->after('external_id')->nullable();
            $table->string('embed_url')->after('thumbnail_url');
            // Opsional: ubah 'kegiatan' menjadi 'description' atau biarkan
            // Saya biarkan 'kegiatan' sebagai deskripsi
        });
    }

    public function down()
    {
        Schema::table('gallery', function (Blueprint $table) {
            $table->string('gambar')->after('judul')->nullable();
            $table->dropColumn(['type', 'source', 'external_id', 'thumbnail_url', 'embed_url']);
        });
    }
};