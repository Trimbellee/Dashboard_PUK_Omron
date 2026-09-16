<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarisPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'inventaris_peminjaman';

    protected $fillable = [
        'barang_id',
        'jumlah_pinjam',
        'peminjam_nama',
        'peminjam_kontak',
        'petugas_id',
        'penerima_id',
        'tanggal_pinjam',
        'tanggal_kembali',
        'status',
        'keterangan',
        'kondisi_kembali',
        'catatan_kembali',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_kembali' => 'date',
        'jumlah_pinjam' => 'integer'
    ];

    public function barang()
    {
        return $this->belongsTo(InventarisBarang::class, 'barang_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopeTerlambat($query)
    {
        return $query->where('status', 'terlambat');
    }
    public function scopeHilang($query)
    {
        return $query->where('status', 'hilang');
    }
}
