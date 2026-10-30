<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat pengambilan kaos/kemeja di sekretariat (satu baris = satu kali serah).
     *
     * anggota.jumlah_kaos / jumlah_kemeja = jumlah TOTAL yang dimiliki
     * (jumlah awal dari Excel + pengambilan di tabel ini). Tabel ini adalah
     * catatan audit: siapa, kapan, ukuran apa. Baris yang dibatalkan tidak
     * dihapus, hanya ditandai (dibatalkan_at) supaya jejaknya tetap ada.
     */
    public function up(): void
    {
        Schema::create('pengambilan_seragam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('jenis', 10);                       // 'kaos' | 'kemeja'
            $table->string('ukuran', 10)->nullable();          // ukuran saat diserahkan
            $table->boolean('walau_sudah_punya')->default(false); // dikonfirmasi petugas padahal sudah punya
            $table->string('metode', 10)->default('scanner');  // web | scanner | nfc
            $table->string('dicatat_oleh')->nullable();        // petugas yang menyerahkan
            $table->timestamp('dibatalkan_at')->nullable();
            $table->string('dibatalkan_oleh')->nullable();
            $table->timestamps();

            $table->index(['anggota_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengambilan_seragam');
    }
};