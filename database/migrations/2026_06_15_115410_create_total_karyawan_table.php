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
    Schema::create('total_karyawan', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('bulan');
            $table->smallInteger('tahun');
            $table->integer('total_karyawan');
            $table->timestamps();

            $table->unique(['bulan', 'tahun'], 'unique_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('total_karyawan');
    }
};
