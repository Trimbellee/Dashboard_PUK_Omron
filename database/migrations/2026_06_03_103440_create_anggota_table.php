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
       Schema::create('anggota', function (Blueprint $table) {
    $table->id();
    $table->char('ulid', 26)->unique()->nullable();

    $table->string('nama_lengkap')->index();
    $table->string('nik_karyawan', 8)->unique();

    $table->string('departemen')->index();
    $table->string('jabatan')->index();

    $table->enum('jenis_kelamin', ['Pria', 'Wanita']);

    $table->enum('jenis_identitas', ['KTP', 'SIM', 'PASPOR'])->nullable();
    $table->string('nomor_identitas', 32)->nullable();

    $table->string('nomor_whatsapp', 30)->nullable()->unique();
    $table->string('email')->nullable()->unique();

    $table->string('tempat_lahir')->nullable();
    $table->date('tanggal_lahir')->nullable();

    $table->string('provinsi_domisili')->nullable();
    $table->string('kota_domisili')->nullable();
    $table->string('kecamatan_domisili')->nullable();
    $table->string('kelurahan_domisili')->nullable();
    $table->text('alamat_domisili')->nullable();

    $table->string('provinsi_ktp')->nullable();
    $table->string('kota_ktp')->nullable();
    $table->string('kecamatan_ktp')->nullable();
    $table->string('kelurahan_ktp')->nullable();
    $table->text('alamat_ktp')->nullable();

    $table->string('pendidikan_terakhir')->nullable();

    $table->date('tanggal_mulai_bekerja')->nullable();
    $table->date('tanggal_masuk_anggota')->nullable();
    $table->date('tanggal_keluar_anggota')->nullable();

    $table->string('status_karyawan')->index();

    $table->boolean('is_active')->default(true);

    $table->timestamp('last_imported_at')->nullable();

    $table->string('foto')->nullable();

    $table->timestamps();
    $table->softDeletes();
});    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
