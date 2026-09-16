<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('laporan', function (Blueprint $table) {

            $table->id();

            // harus sama dengan pengajuan.id
            $table->char('pengajuan_id', 36);

            $table->text('nama_kegiatan')->nullable();
            $table->text('tujuan')->nullable();
            $table->text('tanggal')->nullable();
            $table->string('jam')->nullable();
            $table->string('tempat')->nullable();

            $table->longText('anggaran_aktual_json')->nullable();

            $table->timestamps();

            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuan')
                ->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};