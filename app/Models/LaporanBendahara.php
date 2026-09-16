<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanBendahara extends Model
{
    protected $table = 'laporan_bendahara';

    protected $fillable = [
        'bulan',
        'tahun',
        'cos',
        'pajak_bank',
        'saldo_awal_json',
        'debit_manual_json',
        'kredit_manual_json',
        'dana_lain_json',
        'dana_sumbangan_json',
        'insentif',
        'debit_json',
        'kredit_json',
        'total_json',
        'pdf_path',
        'status',
        'bendahara_nama',
        'bendahara_ttd_at',
        'sekretaris_nama',
        'sekretaris_ttd_at',
        'ketua_nama',
        'ketua_ttd_at',
        'catatan_revisi', 
        'ditolak_oleh', 
        'ditolak_role', 
        'ditolak_at', 
        'riwayat_json',
        'dibuka_kembali_oleh',
        'dibuka_kembali_alasan',
        'dibuka_kembali_at',
    ];

    protected $casts = [
        'saldo_awal_json' => 'array',
        'debit_manual_json' => 'array',
        'kredit_manual_json' => 'array',
        'dana_lain_json' => 'array',
        'dana_sumbangan_json' => 'array',
        'insentif' => 'array',
        'debit_json' => 'array',
        'kredit_json' => 'array',
        'total_json' => 'array',
        'bendahara_ttd_at'    => 'datetime',
        'sekretaris_ttd_at'   => 'datetime',
        'ketua_ttd_at'        => 'datetime',
        'ditolak_at'   => 'datetime',
        'riwayat_json' => 'array',
        'dibuka_kembali_at' => 'datetime',
    ];

    public function arsip()
    {
        return $this->hasMany(LaporanBendaharaArsip::class);
    }
}