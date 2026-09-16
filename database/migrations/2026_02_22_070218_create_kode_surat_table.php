<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('kode_surat', function (Blueprint $table) {
        $table->id();
        $table->integer('nomor_urut');
        $table->string('no_surat')->unique();
        $table->string('jenis', 10);
        $table->string('bidang', 50);
        $table->integer('bulan');
        $table->string('bulan_romawi', 5);
        $table->integer('tahun');
        $table->text('keterangan')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('kode_surat');
}

};
