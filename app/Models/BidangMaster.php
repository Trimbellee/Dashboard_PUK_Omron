<?php
// app/Models/BidangMaster.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidangMaster extends Model
{
    protected $table = 'bidang_master';

    protected $fillable = ['kode', 'nama', 'urutan', 'aktif', 'kas_tujuan', 'punya_insentif'];

    protected $casts = [
        'aktif'          => 'boolean',
        'punya_insentif' => 'boolean',
    ];

    /** Nama bidang aktif, urut sesuai pengaturan. Dipakai dropdown Pengajuan & suggest Koperasi. */
    public static function namaAktif(): array
    {
        return static::where('aktif', true)->orderBy('urutan')->pluck('nama')->all();
    }
}