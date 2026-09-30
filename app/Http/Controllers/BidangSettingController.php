<?php

namespace App\Http\Controllers;

use App\Models\BidangMaster;
use App\Models\KasMaster;
use App\Models\Pengajuan;
use App\Models\BelanjaKoperasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BidangSettingController extends Controller
{
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

        return view('bidang.index', [
            'bidang'    => BidangMaster::orderBy('urutan')->get(),
            'kasAktif'  => KasMaster::where('aktif', true)->orderBy('urutan')->get(['kode', 'nama']),
            'bolehUbah' => $this->bolehUbah(),
        ]);
    }

    /* ---------- pembersihan & pemeriksaan ---------- */

    private function bersihkan(array $rows): array
    {
        return array_map(fn ($r) => [
            'kode'           => strtolower(trim($r['kode'] ?? '')),
            'nama'           => trim($r['nama'] ?? ''),
            'aktif'          => !empty($r['aktif']),
            'kas_tujuan'     => strtolower(trim($r['kas_tujuan'] ?? '')),
            'punya_insentif' => !empty($r['punya_insentif']),
        ], array_values($rows));
    }

    private function periksa(array $rows): array
    {
        $err = [];
        $e = function (?int $i, string $field, string $msg) use (&$err) {
            $err[] = ['index' => $i, 'field' => $field, 'message' => $msg];
        };

        $kodeKasAktif = KasMaster::where('aktif', true)->pluck('kode')->map('strtolower')->all();

        $kodeSemua   = [];
        $namaSemua   = [];
        $jumlahInsentif = 0;

        foreach ($rows as $i => $r) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $r['kode'])) {
                $e($i, 'kode', 'Kode harus huruf kecil/angka/underscore, diawali huruf');
            } elseif (in_array($r['kode'], $kodeSemua)) {
                $e($i, 'kode', 'Kode sudah dipakai baris lain');
            }
            $kodeSemua[] = $r['kode'];

            if ($r['nama'] === '') {
                $e($i, 'nama', 'Nama wajib diisi');
            } elseif (in_array(strtoupper($r['nama']), $namaSemua)) {
                $e($i, 'nama', 'Nama sudah dipakai baris lain (realisasi akan tercampur)');
            }
            $namaSemua[] = strtoupper($r['nama']);

            if (!in_array($r['kas_tujuan'], $kodeKasAktif)) {
                $e($i, 'kas_tujuan', 'Kas tujuan harus salah satu kas yang aktif');
            }

            if ($r['punya_insentif'] && $r['aktif']) {
                $jumlahInsentif++;
            }
        }

        if ($jumlahInsentif > 1) {
            $e(null, '', 'Hanya boleh satu bidang aktif yang ditandai "Punya insentif"');
        }
        if (empty(array_filter($rows, fn ($r) => $r['aktif']))) {
            $e(null, '', 'Minimal satu bidang harus aktif');
        }

        return $err;
    }

    /** Peringatan (bukan blokir): bidang dinonaktifkan padahal masih ada realisasi bulan berjalan. */
    private function peringatan(array $rows): array
    {
        $nonaktif = array_column(array_filter($rows, fn ($r) => !$r['aktif']), 'nama');
        if (!$nonaktif) return [];

        $bulan = now()->month;
        $tahun = now()->year;
        $out   = [];

        foreach ($nonaktif as $nama) {
            $namaUpper = strtoupper(trim($nama));

            $adaPengajuan = Pengajuan::where('status', 'Disetujui')
                ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                ->whereRaw('UPPER(TRIM(bidang)) = ?', [$namaUpper])
                ->exists();

            $adaKoperasi = BelanjaKoperasi::where('bulan', $bulan)->where('tahun', $tahun)
                ->whereRaw('UPPER(TRIM(bidang)) = ?', [$namaUpper])
                ->exists();

            if ($adaPengajuan || $adaKoperasi) {
                $out[] = "Bidang \"{$nama}\" dinonaktifkan, padahal masih ada realisasi bulan ini. "
                       . "Realisasi itu tidak akan lagi masuk ke ringkasan kas setelah disimpan.";
            }
        }

        return $out;
    }

    /* ---------- endpoint ---------- */

    public function validasi(Request $request)
    {
        if (!$this->bolehLihat()) return response()->json(['errors' => []], 403);

        $errors = $this->periksa($this->bersihkan($request->input('bidang', [])));

        return response()->json(['errors' => $errors]);
    }

    public function sync(Request $request)
    {
        if (!$this->bolehUbah()) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $rows = $this->bersihkan($request->input('bidang', []));

        if ($errors = $this->periksa($rows)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        $warn = $this->peringatan($rows);
        if ($warn && !$request->boolean('konfirmasi')) {
            return response()->json(['success' => false, 'needs_confirm' => true, 'warnings' => $warn], 409);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $i => $r) {
                // bidang tidak pernah dihapus, hanya dinonaktifkan
                BidangMaster::updateOrCreate(['kode' => $r['kode']], $r + ['urutan' => $i + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Pengaturan bidang berhasil disimpan']);
    }
}