<?php
// app/Models/KasMaster.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasMaster extends Model
{
    protected $table = 'kas_master';

    protected $fillable = [
        'kode', 'nama', 'urutan', 'aktif',
        'saldo_awal_mode', 'debit_mode', 'debit_rumus', 'kredit_mode', 'kredit_rumus',
        'menerima_sumbangan', 'tampil_rincian', 'nama_bidang',
        'punya_pajak', 'label_pajak', 'tujuan_pajak',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'menerima_sumbangan' => 'boolean',
        'tampil_rincian' => 'boolean',
        'punya_pajak' => 'boolean',
    ];
}