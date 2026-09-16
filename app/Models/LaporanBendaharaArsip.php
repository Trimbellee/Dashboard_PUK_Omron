<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanBendaharaArsip extends Model
{
    protected $table = 'laporan_bendahara_arsip';

    protected $fillable = [
        'laporan_bendahara_id', 'bulan', 'tahun', 'versi_ke',
        'cos', 'pajak_bank',
        'saldo_awal_json', 'debit_manual_json', 'kredit_manual_json',
        'dana_lain_json', 'dana_sumbangan_json', 'insentif_json',
        'debit_json', 'kredit_json', 'total_json', 'riwayat_json',
        'pdf_url',
        'bendahara_nama', 'bendahara_ttd_at',
        'sekretaris_nama', 'sekretaris_ttd_at',
        'ketua_nama', 'ketua_ttd_at',
        'dibuka_oleh', 'alasan_buka_kembali', 'dibuka_at',
    ];

    protected $casts = [
        'saldo_awal_json'     => 'array',
        'debit_manual_json'   => 'array',
        'kredit_manual_json'  => 'array',
        'dana_lain_json'      => 'array',
        'dana_sumbangan_json' => 'array',
        'insentif_json'       => 'array',
        'debit_json'          => 'array',
        'kredit_json'         => 'array',
        'total_json'          => 'array',
        'riwayat_json'        => 'array',
        'bendahara_ttd_at'    => 'datetime',
        'sekretaris_ttd_at'   => 'datetime',
        'ketua_ttd_at'        => 'datetime',
        'dibuka_at'           => 'datetime',
    ];
}