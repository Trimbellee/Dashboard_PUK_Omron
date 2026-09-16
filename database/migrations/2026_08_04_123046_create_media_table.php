<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('thumbnail')->nullable();
            $table->integer('size')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('anggota')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('media');
    }
};