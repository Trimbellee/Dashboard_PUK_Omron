<?php
// php artisan make:migration create_insentif_master_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insentif_master', function (Blueprint $table) {
            $table->id();
            $table->string('tujuan')->default('Sekretariat');
            $table->string('keperluan');
            $table->bigInteger('nominal')->default(0);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // isi awal dari daftar hardcode yang sekarang
        $data = [
            ['Insentif Ketua', 1150000],
            ['Insentif Sekretaris', 950000],
            ['Insentif Bendahara', 750000],
        ];
        foreach ([1,2,3,4,5,6] as $n) $data[] = ["Insentif Wakil Ketua Bidang $n", 650000];
        foreach ([1,2,3,4,5,6] as $n) $data[] = ["Insentif Wakil Sekretaris Bidang $n", 650000];
        $data[] = ['Insentif PANGKORMI', 400000];
        $data[] = ['Insentif PANGKORLAP', 400000];
        $data[] = ['Insentif Pengurus Struktural Organisasi di atas PUK', 500000];

        foreach ($data as $i => [$keperluan, $nominal]) {
            DB::table('insentif_master')->insert([
                'tujuan'     => 'Sekretariat',
                'keperluan'  => $keperluan,
                'nominal'    => $nominal,
                'urutan'     => $i + 1,
                'aktif'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insentif_master');
    }
};