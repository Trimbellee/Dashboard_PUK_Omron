<?php
namespace App\Services\Kas;

use App\Models\KasMaster;
use App\Models\BidangMaster;
use App\Models\LaporanParameter;

class KasKonfigurasi
{
    private const FIELD_KAS = [
        'kode', 'nama', 'saldo_awal_mode',
        'debit_mode', 'debit_rumus', 'kredit_mode', 'kredit_rumus',
        'menerima_sumbangan', 'tampil_rincian', 'nama_bidang',
        'punya_pajak', 'label_pajak', 'tujuan_pajak',
    ];
    private const BOOL_KAS = ['menerima_sumbangan', 'tampil_rincian', 'punya_pajak'];

    private const FIELD_BIDANG = ['kode', 'nama', 'kas_tujuan', 'punya_insentif'];
    private const BOOL_BIDANG  = ['punya_insentif'];

    /** Konfigurasi master saat ini (kas & bidang aktif saja, urut). */
    public static function master(): array
    {
        $kas = KasMaster::where('aktif', true)->orderBy('urutan')->get()
            ->map(function ($k) {
                $r = [];
                foreach (self::FIELD_KAS as $f) $r[$f] = $k->$f;
                $r['aktif'] = true;
                return $r;
            })->all();

        $bidang = BidangMaster::where('aktif', true)->orderBy('urutan')->get()
            ->map(function ($b) {
                $r = [];
                foreach (self::FIELD_BIDANG as $f) $r[$f] = $b->$f;
                $r['aktif'] = true;
                return $r;
            })->all();

        $parameter = LaporanParameter::orderBy('kode')->pluck('nilai', 'kode')
            ->map(fn ($v) => (float) $v)->all();

        return [
            'kas'         => $kas,
            'bidang'      => $bidang,
            'parameter'   => $parameter,
            'disimpan_at' => now()->toDateTimeString(),
        ];
    }

    public static function valid($konf): bool
    {
        return is_array($konf) && !empty($konf['kas']) && isset($konf['parameter']);
        // 'bidang' sengaja tidak diwajibkan di sini, supaya snapshot lama
        // (sebelum bidang_master ada) tetap dianggap valid dan tidak memicu
        // error — hitungLaporan() akan memperlakukan bidang kosong sebagai
        // "tidak ada agregasi KREDIT_BIDANG", bukan exception.
    }

    /** Sidik jari untuk membandingkan dua konfigurasi (abaikan waktu simpan). */
    public static function sidik(array $konf): string
    {
        $kas = array_map(function ($r) {
            $o = [];
            foreach (self::FIELD_KAS as $f) {
                $v = $r[$f] ?? null;
                $o[$f] = in_array($f, self::BOOL_KAS) ? (bool) $v : ($v === '' ? null : $v);
            }
            return $o;
        }, $konf['kas'] ?? []);

        $bidang = array_map(function ($r) {
            $o = [];
            foreach (self::FIELD_BIDANG as $f) {
                $v = $r[$f] ?? null;
                $o[$f] = in_array($f, self::BOOL_BIDANG) ? (bool) $v : ($v === '' ? null : $v);
            }
            return $o;
        }, $konf['bidang'] ?? []);

        $par = array_map(fn ($v) => round((float) $v, 6), $konf['parameter'] ?? []);
        ksort($par);

        return md5(json_encode([$kas, $bidang, $par]));
    }
}