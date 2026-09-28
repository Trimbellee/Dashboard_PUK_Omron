<?php
namespace App\Services\Kas;

use App\Models\KasMaster;
use App\Models\LaporanParameter;

class KasKonfigurasi
{
    private const FIELD = [
        'kode', 'nama', 'saldo_awal_mode',
        'debit_mode', 'debit_rumus', 'kredit_mode', 'kredit_rumus',
        'menerima_sumbangan', 'tampil_rincian', 'nama_bidang',
        'punya_pajak', 'label_pajak', 'tujuan_pajak',
    ];
    private const BOOL = ['menerima_sumbangan', 'tampil_rincian', 'punya_pajak'];

    /** Konfigurasi master saat ini (item aktif saja, urut). */
    public static function master(): array
    {
        $kas = KasMaster::where('aktif', true)->orderBy('urutan')->get()
            ->map(function ($k) {
                $r = [];
                foreach (self::FIELD as $f) $r[$f] = $k->$f;
                $r['aktif'] = true;
                return $r;
            })->all();

        $parameter = LaporanParameter::orderBy('kode')->pluck('nilai', 'kode')
            ->map(fn ($v) => (float) $v)->all();

        return [
            'kas'         => $kas,
            'parameter'   => $parameter,
            'disimpan_at' => now()->toDateTimeString(),
        ];
    }

    public static function valid($konf): bool
    {
        return is_array($konf) && !empty($konf['kas']) && isset($konf['parameter']);
    }

    /** Sidik jari untuk membandingkan dua konfigurasi (abaikan waktu simpan). */
    public static function sidik(array $konf): string
    {
        $kas = array_map(function ($r) {
            $o = [];
            foreach (self::FIELD as $f) {
                $v = $r[$f] ?? null;
                $o[$f] = in_array($f, self::BOOL) ? (bool) $v : ($v === '' ? null : $v);
            }
            return $o;
        }, $konf['kas'] ?? []);

        $par = array_map(fn ($v) => round((float) $v, 6), $konf['parameter'] ?? []);
        ksort($par);

        return md5(json_encode([$kas, $par]));
    }
}