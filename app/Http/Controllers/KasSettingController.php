<?php

namespace App\Http\Controllers;

use App\Models\KasMaster;
use App\Models\LaporanBendahara;
use App\Models\LaporanParameter;
use App\Services\Kas\Formula;
use App\Services\Kas\FormulaException;
use App\Services\Kas\KasCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasSettingController extends Controller
{
    private const BIDANG_TETAP = [
        'SEKRETARIS', 'BIDANG_1', 'BIDANG_2', 'BIDANG_3', 'BIDANG_4',
        'BIDANG_5', 'BIDANG_6', 'GM', 'KORLAP',
    ];
    private const VAR_UMUM = ['COS', 'INSENTIF', 'DANA_LAIN'];

    private function role(): ?string
    {
        return session('user')['role'] ?? null;
    }

    private function bolehLihat(): bool
    {
        return in_array($this->role(), ['Bendahara', 'Sekretaris', 'Ketua']);
    }

    private function bolehUbah(): bool
    {
        return in_array($this->role(), ['Bendahara', 'Sekretaris']);
    }

    public function index()
    {
        if (!$this->bolehLihat()) abort(403, 'Tidak diizinkan mengakses halaman ini');

        return view('kas.index', [
            'kas'         => KasMaster::orderBy('urutan')->get(),
            'params'      => LaporanParameter::orderBy('id')->get(['kode', 'nama', 'nilai']),
            'bidangTetap' => self::BIDANG_TETAP,
            'bolehUbah'   => $this->bolehUbah(),
        ]);
    }

    /* ---------- pembersihan input ---------- */

    private function bersihkanKas(array $rows): array
    {
        $mode = fn ($v, array $ok, string $def) => in_array($v, $ok, true) ? $v : $def;

        return array_map(function ($r) use ($mode) {
            $debit  = $mode($r['debit_mode'] ?? null, ['manual', 'rumus'], 'manual');
            $kredit = $mode($r['kredit_mode'] ?? null, ['manual', 'rumus'], 'manual');
            $pajak  = !empty($r['punya_pajak']);

            return [
                'kode'               => strtolower(trim($r['kode'] ?? '')),
                'nama'               => trim($r['nama'] ?? ''),
                'aktif'              => !empty($r['aktif']),
                'saldo_awal_mode'    => $mode($r['saldo_awal_mode'] ?? null, ['carry_over', 'nol'], 'carry_over'),
                'debit_mode'         => $debit,
                'debit_rumus'        => $debit === 'rumus' ? trim($r['debit_rumus'] ?? '') : null,
                'kredit_mode'        => $kredit,
                'kredit_rumus'       => $kredit === 'rumus' ? trim($r['kredit_rumus'] ?? '') : null,
                'menerima_sumbangan' => !empty($r['menerima_sumbangan']),
                'tampil_rincian'     => $pajak || !empty($r['tampil_rincian']),
                'nama_bidang'        => trim($r['nama_bidang'] ?? '') ?: null,
                'punya_pajak'        => $pajak,
                'label_pajak'        => $pajak ? (trim($r['label_pajak'] ?? '') ?: 'Pajak') : null,
                'tujuan_pajak'       => $pajak ? (trim($r['tujuan_pajak'] ?? '') ?: 'Bank') : null,
            ];
        }, array_values($rows));
    }

    private function bersihkanParam(array $rows): array
    {
        return array_map(fn ($r) => [
            'kode'  => strtoupper(trim($r['kode'] ?? '')),
            'nama'  => trim($r['nama'] ?? ''),
            'nilai' => is_numeric($r['nilai'] ?? null) ? (float) $r['nilai'] : null,
        ], array_values($rows));
    }

    /* ---------- pemeriksaan ---------- */

    private function periksa(array $kas, array $params): array
    {
        $err = [];
        $e = function (string $scope, ?int $i, string $field, string $msg) use (&$err) {
            $err[] = ['scope' => $scope, 'index' => $i, 'field' => $field, 'message' => $msg];
        };

        // parameter
        $kodeParam = [];
        foreach ($params as $i => $p) {
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $p['kode'])) {
                $e('param', $i, 'kode', 'Kode harus huruf besar/angka/underscore, diawali huruf');
            } elseif (in_array($p['kode'], self::VAR_UMUM) || in_array($p['kode'], $kodeParam)) {
                $e('param', $i, 'kode', 'Kode sudah dipakai');
            }
            if ($p['nama'] === '')    $e('param', $i, 'nama', 'Nama wajib diisi');
            if ($p['nilai'] === null) $e('param', $i, 'nilai', 'Nilai harus berupa angka');
            $kodeParam[] = $p['kode'];
        }

        // kas
        $kodeSemua = [];
        $kodeAktif = [];
        $bidang    = self::BIDANG_TETAP;

        foreach ($kas as $i => $k) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $k['kode'])) {
                $e('kas', $i, 'kode', 'Kode harus huruf kecil/angka/underscore, diawali huruf');
            } elseif (in_array($k['kode'], array_map('strtolower', $kodeSemua))) {
                $e('kas', $i, 'kode', 'Kode sudah dipakai item lain');
            }
            if ($k['nama'] === '') $e('kas', $i, 'nama', 'Nama wajib diisi');

            $n = KasCalculator::norm($k['kode']);
            $kodeSemua[] = $n;
            if ($k['aktif']) $kodeAktif[] = $n;
            if ($k['nama_bidang']) $bidang[] = KasCalculator::norm($k['nama_bidang']);
        }

        if ($kodeAktif === []) $e('global', null, '', 'Minimal satu item kas harus aktif');

        // rumus
        $umum = array_merge(self::VAR_UMUM, array_map('strtoupper', array_column($params, 'kode')));

        foreach ($kas as $i => $k) {
            if (!$k['aktif']) continue;

            foreach (['debit', 'kredit'] as $jenis) {
                if ($k["{$jenis}_mode"] !== 'rumus') continue;
                $field = "{$jenis}_rumus";

                try {
                    $f = Formula::parse($k[$field] ?? '');
                } catch (FormulaException $ex) {
                    $e('kas', $i, $field, $ex->getMessage());
                    continue;
                }

                foreach ($f->variables() as $v) {
                    if (!str_contains($v, '.')) {
                        if (!in_array($v, $umum)) $e('kas', $i, $field, "Variabel '{$v}' tidak dikenal");
                        continue;
                    }
                    [$ns, $kd] = explode('.', $v, 2);
                    $ok = match ($ns) {
                        'SALDO', 'DEBIT', 'KREDIT' => in_array($kd, $kodeAktif),
                        'PAJAK', 'SUMBANGAN'       => in_array($kd, $kodeSemua),
                        'BIDANG'                   => in_array($kd, $bidang),
                        default                    => false,
                    };
                    if (!$ok) {
                        $e('kas', $i, $field, "'{$v}' tidak dikenal, atau merujuk kas yang tidak aktif");
                    }
                }
            }
        }

        // siklus, pembagian nol (hanya jika isian dasar sudah beres)
        if (!$err) {
            try {
                $this->kalkulator($kas, $params)->validasi();
            } catch (FormulaException $ex) {
                $e('global', null, '', $ex->getMessage());
            }
        }

        return $err;
    }

    private function kalkulator(array $kas, array $params): KasCalculator
    {
        $aktif = array_values(array_filter($kas, fn ($k) => $k['aktif']));
        $map   = [];
        foreach ($params as $p) $map[$p['kode']] = $p['nilai'];

        return new KasCalculator($aktif, $map);
    }

    /** Peringatan (bukan blokir) saat item aktif dinonaktifkan tapi saldonya masih ada. */
    private function peringatan(array $kas): array
    {
        $nonaktif = array_column(array_filter($kas, fn ($k) => !$k['aktif']), 'kode');
        if (!$nonaktif) return [];

        $baru   = KasMaster::where('aktif', true)->whereIn('kode', $nonaktif)->get();
        $latest = LaporanBendahara::orderByDesc('tahun')->orderByDesc('bulan')->first();
        $out    = [];

        foreach ($baru as $m) {
            $tj    = $latest?->total_json ?? [];
            $saldo = (int) ($tj[$m->kode] ?? $tj[$m->nama] ?? 0);
            if ($saldo !== 0) {
                $out[] = "{$m->nama} dinonaktifkan, padahal saldo akhir laporan terakhir "
                       . "({$latest->bulan}/{$latest->tahun}) masih Rp " . number_format($saldo, 0, ',', '.')
                       . ". Saldo itu tidak akan terbawa ke bulan berikutnya.";
            }
        }
        return $out;
    }

    /* ---------- endpoint ---------- */

    public function validasi(Request $request)
    {
        if (!$this->bolehLihat()) return response()->json(['errors' => []], 403);

        $errors = $this->periksa(
            $this->bersihkanKas($request->input('kas', [])),
            $this->bersihkanParam($request->input('params', []))
        );

        return response()->json(['errors' => $errors]);
    }

    public function simulasi(Request $request)
    {
        if (!$this->bolehLihat()) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $kas    = $this->bersihkanKas($request->input('kas', []));
        $params = $this->bersihkanParam($request->input('params', []));

        if ($errors = $this->periksa($kas, $params)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        try {
            $input = (array) $request->input('input', []);
            $hasil = $this->kalkulator($kas, $params)->hitung($input);
        } catch (FormulaException $ex) {
            return response()->json([
                'success' => false,
                'errors'  => [['scope' => 'global', 'index' => null, 'field' => '', 'message' => $ex->getMessage()]],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'hasil'   => $hasil,
            'cos'     => (int) ($input['cos'] ?? 0),
        ]);
    }

    public function sync(Request $request)
    {
        if (!$this->bolehUbah()) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $kas    = $this->bersihkanKas($request->input('kas', []));
        $params = $this->bersihkanParam($request->input('params', []));

        if ($errors = $this->periksa($kas, $params)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        $warn = $this->peringatan($kas);
        if ($warn && !$request->boolean('konfirmasi')) {
            return response()->json(['success' => false, 'needs_confirm' => true, 'warnings' => $warn], 409);
        }

        DB::transaction(function () use ($kas, $params) {
            foreach ($params as $p) {
                LaporanParameter::updateOrCreate(['kode' => $p['kode']], ['nama' => $p['nama'], 'nilai' => $p['nilai']]);
            }
            // parameter yang dihapus di layar ikut dihapus (rumus yang masih merujuknya sudah ditolak di atas)
            LaporanParameter::whereNotIn('kode', array_column($params, 'kode'))->delete();

            foreach ($kas as $i => $k) {
                // kas tidak pernah dihapus, hanya dinonaktifkan
                KasMaster::updateOrCreate(['kode' => $k['kode']], $k + ['urutan' => $i + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Pengaturan kas berhasil disimpan']);
    }
}