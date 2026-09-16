<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // jabatan_puk (contoh: 'Wakil Ketua Bidang 5')
            $table->string('permission'); // create_news, create_gallery, review_news, review_gallery, approve_news, approve_gallery
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_permissions');
    }
};