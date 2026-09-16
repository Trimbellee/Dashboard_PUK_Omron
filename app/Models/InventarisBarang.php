<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarisBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventaris_barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'type',
        'kategori',
        'jumlah',
        'kondisi',
        'status',
        'tanggal_pembelian',
        'harga_perolehan',
        'keterangan',
        'created_by'
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'harga_perolehan' => 'decimal:2'
    ];  

    protected function setNamaBarangAttribute($value)
    {
        $this->attributes['nama_barang'] = strtoupper($value);
    }

    protected function setTypeAttribute($value)
    {
        $this->attributes['type'] = strtoupper($value);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function peminjaman()
    {
        return $this->hasMany(InventarisPeminjaman::class, 'barang_id');
    }

    public function peminjamanAktif()
    {
        return $this->hasMany(InventarisPeminjaman::class, 'barang_id')
                    ->where('status', 'aktif');
    }

    public function logs()
    {
        return $this->hasMany(InventarisLog::class, 'barang_id');
    }

    /**
     * Setiap baris merepresentasikan SATU unit fisik (kode_barang berbeda-beda
     * meski nama/type sama). Jadi "tersedia" hanya soal status, bukan stok.
     */
    public function isTersedia()
    {
        return $this->status === 'tersedia' && !$this->peminjamanAktif()->exists();
    }

    public function isSedangDipinjam()
    {
        return $this->peminjamanAktif()->exists();
    }
}
