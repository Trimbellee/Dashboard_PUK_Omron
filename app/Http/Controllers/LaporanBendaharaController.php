<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\BelanjaKoperasi;
use App\Models\LaporanBendahara;
use App\Models\LaporanBendaharaArsip;
use App\Models\InsentifMaster;
use App\Models\User;
use App\Services\Kas\KasKonfigurasi;
use App\Services\Kas\FormulaException;
use App\Services\Kas\KasCalculator;
use App\Services\BrevoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class LaporanBendaharaController extends Controller
{
    /* ============================================================
        Urutan status resmi dalam alur persetujuan.
        draft -> menunggu_sekretaris -> menunggu_ketua -> selesai
        Sekretaris & Ketua bisa menolak -> kembali ke draft
        (dengan catatan revisi) untuk diperbaiki Bendahara.
    ============================================================ */
    private const STATUS_DRAFT               = 'draft';
    private const STATUS_MENUNGGU_SEKRETARIS = 'menunggu_sekretaris';
    private const STATUS_MENUNGGU_KETUA      = 'menunggu_ketua';
    private const STATUS_SELESAI             = 'selesai';

    /** Bagian rincian yang selalu ada (belum dikonfigurasi lewat master). */
    private const BIDANG_TETAP = [
        ['SEKRETARIS', 'Sekretaris'], ['BIDANG 1', 'Bidang 1'], ['BIDANG 2', 'Bidang 2'],
        ['BIDANG 3', 'Bidang 3'], ['BIDANG 4', 'Bidang 4'], ['BIDANG 5', 'Bidang 5'],
        ['BIDANG 6', 'Bidang 6'], ['GM', 'GM'], ['KORLAP', 'KorLap'],
    ];

    /* ============================================================
        INDEX
    ============================================================ */
    public function index(Request $request)
    {
        $role = session('user')['role'] ?? null;

        if (!in_array($role, ['Bendahara', 'Sekretaris', 'Ketua'])) {
            abort(403, 'Tidak diizinkan mengakses halaman ini');
        }

        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);

        $laporan = $this->getLaporanData($bulan, $tahun);

        return view('laporan-bendahara.index', compact('laporan'));
    }

    /* ============================================================
        HITUNG (pratinjau) — tidak menyimpan apa pun
    ============================================================ */
    public function hitung(Request $request)
    {
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris', 'Ketua'])) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            $saved = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();
            $hasil = $this->hitungLaporan($bulan, $tahun, $request->all(), $this->konfigurasiUntuk($saved));

            return response()->json(['success' => true, 'hasil' => $hasil]);
        } catch (FormulaException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ============================================================
        MUAT KONFIGURASI TERBARU (khusus draft)
        Mengganti snapshot konfigurasi dengan master saat ini.
        Isian tersimpan tidak disentuh.
    ============================================================ */
    public function muatKonfigurasi(Request $request)
    {
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris'])) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            DB::transaction(function () use ($bulan, $tahun) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan belum pernah disimpan');
                }
                if ($laporan->status !== self::STATUS_DRAFT) {
                    abort(422, 'Konfigurasi hanya bisa dimuat ulang saat laporan berstatus draft');
                }

                $laporan->konfigurasi_kas_json = KasKonfigurasi::master();
                $this->catatRiwayat($laporan, 'muat_konfigurasi');
                $laporan->save();
            });

            return response()->json(['success' => true, 'message' => 'Konfigurasi kas diperbarui']);
        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        STORE
        Menyimpan draft laporan & generate PDF pratinjau.
        Angka debit/kredit/total dihitung ulang di server.
    ============================================================ */
    public function store(Request $request)
    {
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan. Hanya Bendahara atau Sekretaris yang dapat menyimpan laporan.'
            ], 403);
        }

        try {
            $bulan = (int) $request->bulan;
            $tahun = (int) $request->tahun;

            if (!$bulan || !$tahun) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulan & tahun wajib'
                ], 422);
            }

            $existing = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

            if ($existing && $existing->status !== self::STATUS_DRAFT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan sudah diajukan, tidak bisa diubah lagi.'
                ], 422);
            }

            // snapshot milik laporan; laporan baru mengambil dari master
            $konf  = $this->konfigurasiUntuk($existing);
            $hasil = $this->hitungLaporan($bulan, $tahun, $request->all(), $konf);

            $debit = $kredit = $total = [];
            foreach ($hasil['rows'] as $r) {
                $debit[$r['kode']]  = $r['debit'];
                $kredit[$r['kode']] = $r['kredit'];
                $total[$r['kode']]  = $r['total'];
            }

            $pajak = collect($request->pajak_json ?? [])->map(fn ($v) => (int) $v)->all();

            DB::transaction(function () use ($request, $bulan, $tahun, $debit, $kredit, $total, $pajak, $konf) {
                $laporan = LaporanBendahara::updateOrCreate(
                    ['bulan' => $bulan, 'tahun' => $tahun],
                    [
                        'cos'                  => (int) $request->cos,
                        'pajak_bank'           => (int) ($pajak['bunga_giro'] ?? 0), // kolom lama NOT NULL
                        'pajak_json'           => $pajak,
                        'saldo_awal_json'      => $request->saldo_awal_json ?? [],
                        'debit_manual_json'    => $request->debit_manual_json ?? [],
                        'kredit_manual_json'   => $request->kredit_manual_json ?? [],
                        'dana_lain_json'       => $request->dana_lain_json ?? [],
                        'dana_sumbangan_json'  => $request->dana_sumbangan_json ?? [],
                        'insentif'             => $request->insentif ?? $this->defaultInsentif(),
                        'debit_json'           => $debit,
                        'kredit_json'          => $kredit,
                        'total_json'           => $total,
                        'konfigurasi_kas_json' => $konf,
                        'status'               => self::STATUS_DRAFT,

                        // laporan sudah diperbaiki -> bersihkan flag "perlu revisi"
                        'catatan_revisi'       => null,
                        'ditolak_oleh'         => null,
                        'ditolak_role'         => null,
                        'ditolak_at'           => null,
                    ]
                );

                $laporan->bendahara_nama        = null;
                $laporan->bendahara_ttd_at      = null;
                $laporan->sekretaris_nama       = null;
                $laporan->sekretaris_ttd_at     = null;
                $laporan->ketua_nama            = null;
                $laporan->ketua_ttd_at          = null;
                $laporan->dibuka_kembali_oleh   = null;
                $laporan->dibuka_kembali_alasan = null;
                $laporan->dibuka_kembali_at     = null;

                $this->catatRiwayat($laporan, 'simpan_draft');
                $laporan->save();
            });

            $this->generatePdfLaporan($bulan, $tahun);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil disimpan'
            ]);

        } catch (FormulaException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Perhitungan gagal: ' . $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => basename($e->getFile())
            ], 500);
        }
    }

    /* ============================================================
        SUBMIT — BENDAHARA (draft -> menunggu_sekretaris)
    ============================================================ */
    public function submitBendahara(Request $request)
    {
        if ((session('user')['role'] ?? null) !== 'Bendahara') {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            DB::transaction(function () use ($bulan, $tahun) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan tidak ditemukan');
                }

                if ($laporan->status !== self::STATUS_DRAFT) {
                    abort(422, 'Laporan sudah diajukan sebelumnya');
                }

                $laporan->status           = self::STATUS_MENUNGGU_SEKRETARIS;
                $laporan->bendahara_nama   = session('user')['username'] ?? 'Bendahara';
                $laporan->bendahara_ttd_at = now();

                $laporan->catatan_revisi = null;
                $laporan->ditolak_oleh   = null;
                $laporan->ditolak_role   = null;
                $laporan->ditolak_at     = null;

                $this->catatRiwayat($laporan, 'ajukan_ke_sekretaris');
                $laporan->save();
            });

            $this->generatePdfLaporan($bulan, $tahun);

            $laporanTerbaru = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

            $this->sendEmails(
                $this->emailsByRole(['Sekretaris']),
                new \App\Mail\LaporanSubmittedMail($laporanTerbaru)
            );

            return response()->json(['success' => true, 'message' => 'Laporan berhasil diajukan ke Sekretaris']);

        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        APPROVAL — SEKRETARIS
    ============================================================ */
    public function approveSekretaris(Request $request)
    {
        if ((session('user')['role'] ?? null) !== 'Sekretaris') {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            DB::transaction(function () use ($bulan, $tahun) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan tidak ditemukan');
                }

                if ($laporan->status !== self::STATUS_MENUNGGU_SEKRETARIS) {
                    abort(422, 'Laporan tidak dalam status menunggu Sekretaris');
                }

                $laporan->status            = self::STATUS_MENUNGGU_KETUA;
                $laporan->sekretaris_nama   = session('user')['username'] ?? 'Sekretaris';
                $laporan->sekretaris_ttd_at = now();

                $this->catatRiwayat($laporan, 'setujui_sekretaris');
                $laporan->save();
            });

            $this->generatePdfLaporan($bulan, $tahun);

            $laporanTerbaru = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

            $this->sendEmails(
                $this->emailsByRole(['Ketua']),
                new \App\Mail\LaporanDisetujuiSekretarisMail($laporanTerbaru)
            );

            return response()->json(['success' => true, 'message' => 'Laporan disetujui Sekretaris']);

        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        APPROVAL — KETUA
    ============================================================ */
    public function approveKetua(Request $request)
    {
        if ((session('user')['role'] ?? null) !== 'Ketua') {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            DB::transaction(function () use ($bulan, $tahun) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan tidak ditemukan');
                }

                if ($laporan->status !== self::STATUS_MENUNGGU_KETUA) {
                    abort(422, 'Laporan tidak dalam status menunggu Ketua');
                }

                $laporan->status       = self::STATUS_SELESAI;
                $laporan->ketua_nama   = session('user')['username'] ?? 'Ketua';
                $laporan->ketua_ttd_at = now();

                $this->catatRiwayat($laporan, 'setujui_ketua');
                $laporan->save();
            });

            $this->generatePdfLaporan($bulan, $tahun);

            $laporanTerbaru = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

            $emailBendahara = $this->emailByUsername($laporanTerbaru->bendahara_nama);

            if ($emailBendahara) {
                $this->sendEmails(
                    [$emailBendahara],
                    new \App\Mail\LaporanSelesaiMail($laporanTerbaru)
                );
            }

            return response()->json(['success' => true, 'message' => 'Laporan final disetujui Ketua']);

        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        TOLAK — SEKRETARIS (menunggu_sekretaris -> draft)
    ============================================================ */
    public function rejectSekretaris(Request $request)
    {
        return $this->reject($request, 'Sekretaris', self::STATUS_MENUNGGU_SEKRETARIS);
    }

    /* ============================================================
        TOLAK — KETUA (menunggu_ketua -> draft)
    ============================================================ */
    public function rejectKetua(Request $request)
    {
        return $this->reject($request, 'Ketua', self::STATUS_MENUNGGU_KETUA);
    }

    /* ============================================================
        REJECT (reusable) — dipanggil rejectSekretaris & rejectKetua
    ============================================================ */
    private function reject(Request $request, string $role, string $statusYangDiharapkan)
    {
        if ((session('user')['role'] ?? null) !== $role) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $request->validate([
            'catatan' => 'required|string|max:500',
        ], [
            'catatan.required' => 'Catatan alasan penolakan wajib diisi.',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // simpan username bendahara SEBELUM field bendahara_nama di-null-kan
        $usernameBendaharaLama = null;

        try {
            DB::transaction(function () use ($bulan, $tahun, $role, $statusYangDiharapkan, $request, &$usernameBendaharaLama) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan tidak ditemukan');
                }

                if ($laporan->status !== $statusYangDiharapkan) {
                    abort(422, 'Laporan tidak dalam status yang bisa ditolak');
                }

                $usernameBendaharaLama = $laporan->bendahara_nama;

                $laporan->status         = self::STATUS_DRAFT;
                $laporan->catatan_revisi = $request->catatan;
                $laporan->ditolak_oleh   = session('user')['username'] ?? $role;
                $laporan->ditolak_role   = $role;
                $laporan->ditolak_at     = now();

                $laporan->bendahara_nama    = null;
                $laporan->bendahara_ttd_at  = null;
                $laporan->sekretaris_nama   = null;
                $laporan->sekretaris_ttd_at = null;
                $laporan->ketua_nama        = null;
                $laporan->ketua_ttd_at      = null;

                $this->catatRiwayat($laporan, 'tolak', $request->catatan);
                $laporan->save();
            });

            $laporanTerbaru = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();
            $emailBendahara = $this->emailByUsername($usernameBendaharaLama);

            if ($emailBendahara) {
                $this->sendEmails(
                    [$emailBendahara],
                    new \App\Mail\LaporanDitolakMail($laporanTerbaru, $role, $request->catatan)
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Laporan dikembalikan ke Bendahara untuk direvisi'
            ]);

        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        BUKA KEMBALI — KETUA (selesai -> draft, approval ulang penuh)
        Data lama diarsipkan dulu ke laporan_bendahara_arsip
    ============================================================ */
    public function reopen(Request $request)
    {
        if ((session('user')['role'] ?? null) !== 'Ketua') {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $request->validate([
            'alasan' => 'required|string|max:500',
        ], [
            'alasan.required' => 'Alasan membuka kembali laporan wajib diisi.',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        try {
            DB::transaction(function () use ($bulan, $tahun, $request) {
                $laporan = LaporanBendahara::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();

                if (!$laporan) {
                    abort(404, 'Laporan tidak ditemukan');
                }

                if ($laporan->status !== self::STATUS_SELESAI) {
                    abort(422, 'Hanya laporan berstatus Selesai yang dapat dibuka kembali');
                }

                $versiKe = LaporanBendaharaArsip::where('laporan_bendahara_id', $laporan->id)->count() + 1;

                // arsipkan snapshot laporan yang sudah final
                LaporanBendaharaArsip::create([
                    'laporan_bendahara_id' => $laporan->id,
                    'bulan'    => $laporan->bulan,
                    'tahun'    => $laporan->tahun,
                    'versi_ke' => $versiKe,

                    'cos'                  => $laporan->cos,
                    'pajak_bank'           => $laporan->pajak_bank,
                    'pajak_json'           => $laporan->pajak_json,
                    'saldo_awal_json'      => $laporan->saldo_awal_json,
                    'debit_manual_json'    => $laporan->debit_manual_json,
                    'kredit_manual_json'   => $laporan->kredit_manual_json,
                    'dana_lain_json'       => $laporan->dana_lain_json,
                    'dana_sumbangan_json'  => $laporan->dana_sumbangan_json,
                    'insentif_json'        => $laporan->insentif,
                    'debit_json'           => $laporan->debit_json,
                    'kredit_json'          => $laporan->kredit_json,
                    'total_json'           => $laporan->total_json,
                    'riwayat_json'         => $laporan->riwayat_json,
                    'konfigurasi_kas_json' => $laporan->konfigurasi_kas_json,

                    'pdf_url' => $laporan->pdf_url,

                    'bendahara_nama'    => $laporan->bendahara_nama,
                    'bendahara_ttd_at'  => $laporan->bendahara_ttd_at,
                    'sekretaris_nama'   => $laporan->sekretaris_nama,
                    'sekretaris_ttd_at' => $laporan->sekretaris_ttd_at,
                    'ketua_nama'        => $laporan->ketua_nama,
                    'ketua_ttd_at'      => $laporan->ketua_ttd_at,

                    'dibuka_oleh'         => session('user')['username'] ?? 'Ketua',
                    'alasan_buka_kembali' => $request->alasan,
                    'dibuka_at'           => now(),
                ]);

                // reset laporan utama, minta approval ulang penuh dari awal
                $laporan->status = self::STATUS_DRAFT;

                $laporan->bendahara_nama    = null;
                $laporan->bendahara_ttd_at  = null;
                $laporan->sekretaris_nama   = null;
                $laporan->sekretaris_ttd_at = null;
                $laporan->ketua_nama        = null;
                $laporan->ketua_ttd_at      = null;

                $laporan->catatan_revisi = null;
                $laporan->ditolak_oleh   = null;
                $laporan->ditolak_role   = null;
                $laporan->ditolak_at     = null;

                $laporan->dibuka_kembali_oleh   = session('user')['username'] ?? 'Ketua';
                $laporan->dibuka_kembali_alasan = $request->alasan;
                $laporan->dibuka_kembali_at     = now();

                $this->catatRiwayat($laporan, 'buka_kembali', $request->alasan);
                $laporan->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Laporan dibuka kembali & dikembalikan ke Bendahara untuk direvisi'
            ]);

        } catch (\Throwable $e) {
            return $this->respondError($e);
        }
    }

    /* ============================================================
        INSENTIF MASTER — dipakai tombol "Muat ulang dari master"
        Hanya mengembalikan data, tidak menyimpan apa pun.
    ============================================================ */
    public function insentifMaster()
    {
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris'])) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        return response()->json([
            'success' => true,
            'items'   => $this->defaultInsentif(),
        ]);
    }

    /* ============================================================
        PERHITUNGAN
    ============================================================ */

    /** Snapshot milik laporan; laporan yang belum punya memakai master. */
    private function konfigurasiUntuk(?LaporanBendahara $laporan): array
    {
        $konf = $laporan?->konfigurasi_kas_json;

        return KasKonfigurasi::valid($konf) ? $konf : KasKonfigurasi::master();
    }

    /** Satu-satunya jalur hitung: dipakai pratinjau dan store(). */
    private function hitungLaporan(int $bulan, int $tahun, array $p, array $konf): array
    {
        $kas = collect($konf['kas'])->map(fn ($r) => (object) $r);

        $kodeByNama = [];
        $terima     = [];
        foreach ($kas as $k) {
            $kodeByNama[KasCalculator::norm($k->nama)] = $k->kode;
            $terima[$k->kode] = (bool) $k->menerima_sumbangan;
        }

        // sumbangan dipetakan ke kas lewat nama yang dipilih di dropdown
        $sumbangan = [];
        foreach ($p['dana_sumbangan_json'] ?? [] as $r) {
            $kode = $kodeByNama[KasCalculator::norm($r['bidang'] ?? '')] ?? null;
            if ($kode && $terima[$kode]) {
                $sumbangan[$kode] = ($sumbangan[$kode] ?? 0) + (int) ($r['nominal'] ?? 0);
            }
        }

        // total realisasi per bidang (nama) dihitung server dari database
        $realisasiPerNama = [];
        foreach ($this->ambilPerBidang($bulan, $tahun) as $nama => $rows) {
            $realisasiPerNama[strtoupper(trim($nama))] = array_sum(array_column($rows, 'aktual'));
        }

        // petakan nama bidang -> KODE bidang di bidang_master, dinormalisasi
        // (KREDIT_BIDANG.* di rumus butuh kunci berupa kode bidang, bukan nama)
        $bidang = [];
        foreach ($konf['bidang'] ?? [] as $b) {
            $namaUpper = strtoupper(trim($b['nama']));
            $kodeNorm  = KasCalculator::norm($b['kode']);
            $bidang[$kodeNorm] = $realisasiPerNama[$namaUpper] ?? 0;
        }

        return KasCalculator::dariKonfigurasi($konf)->hitung([
            'cos'           => (int) ($p['cos'] ?? 0),
            'saldo_awal'    => $p['saldo_awal_json'] ?? [],
            'debit_manual'  => $p['debit_manual_json'] ?? [],
            'kredit_manual' => $p['kredit_manual_json'] ?? [],
            'pajak'         => $p['pajak_json'] ?? [],
            'sumbangan'     => $sumbangan,
            'bidang'        => $bidang,
            'insentif'      => $this->jumlahNominal($p['insentif'] ?? $this->defaultInsentif()),
            'dana_lain'     => $this->jumlahNominal($p['dana_lain_json'] ?? []),
        ]);
    }

    /** Rincian per bidang dari Pengajuan + Belanja Koperasi. */
    private function ambilPerBidang(int $bulan, int $tahun): array
    {
        $pengajuan = Pengajuan::query()
            ->where('status', 'Disetujui')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->map(fn ($r) => [
                'Bidang'    => strtoupper(trim($r->bidang)),
                'Tujuan'    => $r->tujuan,
                'Keperluan' => $r->keperluan,
                'Aktual'    => (int) ($r->aktual_biaya ?? 0),
                'Tanggal'   => optional($r->tanggal)->format('d M Y'),
            ]);

        $koperasi = BelanjaKoperasi::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->map(fn ($r) => [
                'Bidang'    => strtoupper(trim($r->bidang)),
                'Tujuan'    => $r->tujuan,
                'Keperluan' => $r->keperluan,
                'Aktual'    => (int) $r->biaya,
                'Tanggal'   => $r->tanggal ? Carbon::parse($r->tanggal)->format('d M Y') : '-',
            ]);

        $perBidang = [];
        foreach ($pengajuan->merge($koperasi) as $row) {
            $bidang = $row['Bidang'] ?: 'LAINNYA';
            $perBidang[$bidang][] = [
                'tanggal'   => $row['Tanggal'],
                'tujuan'    => $row['Tujuan'],
                'keperluan' => $row['Keperluan'],
                'aktual'    => $row['Aktual'],
            ];
        }

        return $perBidang;
    }

    /** Ambil nilai per kas: kunci kode dulu, lalu nama (data lama). */
    private function nilaiKas($json, string $kode, string $nama)
    {
        return is_array($json) ? ($json[$kode] ?? $json[$nama] ?? 0) : 0;
    }

    private function jumlahNominal($rows): int
    {
        return (int) array_sum(array_map(fn ($r) => (int) ($r['nominal'] ?? 0), (array) $rows));
    }

    /* ============================================================
        DEFAULT INSENTIF (dari master)
    ============================================================ */
    private function defaultInsentif(): array
    {
        return InsentifMaster::where('aktif', true)
            ->orderBy('urutan')
            ->get(['tujuan', 'keperluan', 'nominal'])
            ->map(fn ($r) => [
                'tujuan'    => $r->tujuan,
                'keperluan' => $r->keperluan,
                'nominal'   => (int) $r->nominal,
            ])
            ->all();
    }

    /* ============================================================
        HELPER — riwayat, error, email, TTD, PDF
    ============================================================ */

    /** Catat satu entri riwayat. Dipanggil SEBELUM save(). */
    private function catatRiwayat(LaporanBendahara $laporan, string $aksi, ?string $catatan = null): void
    {
        $riwayat = $laporan->riwayat_json ?? [];

        $riwayat[] = [
            'aksi'    => $aksi,
            'role'    => session('user')['role'] ?? null,
            'nama'    => session('user')['username'] ?? null,
            'catatan' => $catatan,
            'at'      => now()->toDateTimeString(),
        ];

        $laporan->riwayat_json = $riwayat;
    }

    private function respondError(\Throwable $e)
    {
        $status = $e instanceof \Illuminate\Validation\ValidationException
            ? 422
            : ($e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500);

        // abort() melempar HttpException; ambil status dari sana
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $status = $e->getStatusCode();
        }

        return response()->json([
            'success' => false,
            'message' => $e->getMessage() ?: 'Terjadi kesalahan',
        ], $status ?: 500);
    }

    private function emailsByRole(array $roles): array
    {
        return User::whereIn('role', $roles)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->toArray();
    }

    private function emailByUsername(?string $username): ?string
    {
        if (!$username) return null;

        return User::where('username', $username)->value('email');
    }

    /** Render mailable sekali, kirim ke banyak penerima. */
    protected function sendEmails(array $emails, $mailable, array $attachments = [])
    {
        $emails = array_values(array_unique(array_filter($emails)));

        if (empty($emails)) return;

        try {
            $html    = $mailable->render();
            $subject = $mailable->subject ?? '(No Subject)';
        } catch (\Throwable $e) {
            \Log::error('Gagal render mailable', ['error' => $e->getMessage()]);
            return;
        }

        $namaByEmail = User::whereIn('email', $emails)->pluck('username', 'email');

        foreach ($emails as $email) {
            $toName = $namaByEmail[$email] ?? $email;

            try {
                BrevoMailService::send($email, $toName, $subject, $html, $attachments);
            } catch (\Throwable $e) {
                \Log::error('Email gagal dikirim', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** TTD user sebagai base64 data URI (DomPDF sering gagal load dari URL). */
    private function getTtdBase64(?string $username): ?string
    {
        if (!$username) {
            \Log::debug('TTD: username kosong');
            return null;
        }

        $user = User::where('username', $username)->first();

        if (!$user) {
            \Log::warning("TTD: user '{$username}' tidak ditemukan");
            return null;
        }

        if (!$user->ttd) {
            \Log::warning("TTD: user '{$username}' belum upload TTD");
            return null;
        }

        $path = storage_path('app/public/' . $user->ttd);

        if (!file_exists($path)) {
            \Log::warning("TTD: file tidak ada di {$path}");
            return null;
        }

        $data = file_get_contents($path);
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** Generate PDF (dipakai store() & approval). */
    private function generatePdfLaporan(int $bulan, int $tahun): string
    {
        $laporan = $this->getLaporanData($bulan, $tahun);

        $pdf = PDF::loadView('laporan-bendahara.pdf', [
            'laporan' => $laporan
        ])->setPaper('A4', 'portrait');

        $filename = "Laporan Bendahara {$bulan}-{$tahun}.pdf";
        $path = "laporan-bendahara/{$tahun}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        // versi (timestamp) supaya browser tidak memakai cache lama
        $pdfUrl = asset('storage/' . $path) . '?v=' . now()->timestamp;

        LaporanBendahara::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->update(['pdf_url' => $pdfUrl]);

        return $pdfUrl;
    }

    /* ============================================================
        GET LAPORAN DATA
        Menyusun semua data untuk halaman & PDF
    ============================================================ */
    private function getLaporanData(int $bulan, int $tahun): array
    {
        $perBidang = $this->ambilPerBidang($bulan, $tahun);

        $saved = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

        $prevDate    = Carbon::create($tahun, $bulan, 1)->subMonth();
        $prevLaporan = LaporanBendahara::where('bulan', $prevDate->month)
            ->where('tahun', $prevDate->year)
            ->first();

        // konfigurasi: snapshot laporan, atau master untuk laporan baru
        $konf    = $this->konfigurasiUntuk($saved);
        $kasList = collect($konf['kas'])->map(fn ($r) => (object) $r);

        $konfigurasiBerbeda = $saved
            && KasKonfigurasi::valid($saved->konfigurasi_kas_json)
            && KasKonfigurasi::sidik($saved->konfigurasi_kas_json) !== KasKonfigurasi::sidik(KasKonfigurasi::master());

        // pajak: pajak_json, atau kolom lama pajak_bank untuk laporan yang belum termigrasi
        $pajakSaved = $saved?->pajak_json ?? [];
        if ($saved && $saved->pajak_json === null) {
            $pajakSaved['bunga_giro'] = (int) $saved->pajak_bank;
        }

        /* =========================
           RINGKASAN KAS
           Saldo awal: total bulan lalu -> saldo tersimpan -> 0
        ========================= */
        $saldoAwalFinal = $debitManual = $kreditManual = [];
        $ringkasanKas = [];
        $no = 1;

        foreach ($kasList as $k) {
            $kode = $k->kode;
            $nama = $k->nama;

            if ($k->saldo_awal_mode === 'carry_over') {
                $v = $prevLaporan ? $this->nilaiKas($prevLaporan->total_json, $kode, $nama) : 0;
                if (empty($v)) {
                    $v = $this->nilaiKas($saved?->saldo_awal_json, $kode, $nama);
                }
                $saldoAwalFinal[$kode] = $v;
            } else {
                $saldoAwalFinal[$kode] = 0;
            }

            $debitManual[$kode]  = $this->nilaiKas($saved?->debit_manual_json, $kode, $nama);
            $kreditManual[$kode] = $this->nilaiKas($saved?->kredit_manual_json, $kode, $nama);

            $ringkasanKas[] = [
                'no'              => $no++,
                'kode'            => $kode,
                'jenis'           => $nama,
                'saldo_awal'      => $this->nilaiKas($saved?->saldo_awal_json, $kode, $nama),
                'debit'           => $this->nilaiKas($saved?->debit_json, $kode, $nama),
                'kredit'          => $this->nilaiKas($saved?->kredit_json, $kode, $nama),
                'total'           => $this->nilaiKas($saved?->total_json, $kode, $nama),
                'saldo_awal_mode' => $k->saldo_awal_mode,
                'debit_mode'      => $k->debit_mode,
                'kredit_mode'     => $k->kredit_mode,
            ];
        }

        /* =========================
           BAGIAN RINCIAN: bidang tetap + kas yang punya rincian/pajak
        ========================= */
        $rincian = [];
        foreach (self::BIDANG_TETAP as [$key, $label]) {
            $rincian[] = [
                'key' => $key, 'label' => $label, 'kode' => null,
                'insentif'     => $key === 'SEKRETARIS',
                'punya_pajak'  => false, 'label_pajak' => null, 'tujuan_pajak' => null, 'pajak' => 0,
                'items'        => $perBidang[$key] ?? [],
            ];
        }
        foreach ($kasList as $k) {
            if (!$k->tampil_rincian && !$k->punya_pajak) continue;

            $label = $k->nama_bidang ?: $k->nama;
            $key   = strtoupper(trim($label));

            $rincian[] = [
                'key' => $key, 'label' => $label, 'kode' => $k->kode, 'insentif' => false,
                'punya_pajak'  => (bool) $k->punya_pajak,
                'label_pajak'  => $k->label_pajak,
                'tujuan_pajak' => $k->tujuan_pajak,
                'pajak'        => (int) ($pajakSaved[$k->kode] ?? 0),
                'items'        => $perBidang[$key] ?? [],
            ];
        }

        $arsipVersi = $saved
            ? LaporanBendaharaArsip::where('laporan_bendahara_id', $saved->id)
                ->orderByDesc('versi_ke')
                ->get(['versi_ke', 'pdf_url', 'dibuka_oleh', 'alasan_buka_kembali', 'dibuka_at'])
            : collect();

        // TTD user (base64) untuk PDF
        $bendaharaTtd  = $this->getTtdBase64($saved?->bendahara_nama);
        $sekretarisTtd = $this->getTtdBase64($saved?->sekretaris_nama);
        $ketuaTtd      = $this->getTtdBase64($saved?->ketua_nama);

        return [
            'meta' => [
                'bulan'   => $bulan,
                'tahun'   => $tahun,
                'is_edit' => (bool) $saved,
                'status'  => $saved?->status ?? 'draft',

                'konfigurasi_berbeda'     => (bool) $konfigurasiBerbeda,
                'konfigurasi_disimpan_at' => $konf['disimpan_at'] ?? null,

                'bendahara_nama'     => $saved?->bendahara_nama,
                'bendahara_ttd_at'   => $saved?->bendahara_ttd_at,
                'sekretaris_nama'    => $saved?->sekretaris_nama,
                'sekretaris_ttd_at'  => $saved?->sekretaris_ttd_at,
                'ketua_nama'         => $saved?->ketua_nama,
                'ketua_ttd_at'       => $saved?->ketua_ttd_at,

                // gambar TTD (base64 data URI), null jika user belum upload TTD
                'bendahara_ttd_img'  => $bendaharaTtd,
                'sekretaris_ttd_img' => $sekretarisTtd,
                'ketua_ttd_img'      => $ketuaTtd,

                // info penolakan / revisi terakhir
                'catatan_revisi'     => $saved?->catatan_revisi,
                'ditolak_oleh'       => $saved?->ditolak_oleh,
                'ditolak_role'       => $saved?->ditolak_role,
                'ditolak_at'         => $saved?->ditolak_at,

                // jejak audit lengkap, terbaru di akhir array
                'riwayat'            => $saved?->riwayat_json ?? [],

                'dibuka_kembali_oleh'   => $saved?->dibuka_kembali_oleh,
                'dibuka_kembali_alasan' => $saved?->dibuka_kembali_alasan,
                'dibuka_kembali_at'     => $saved?->dibuka_kembali_at,
                'arsip_versi'           => $arsipVersi,
            ],
            'ringkasan_kas'       => $ringkasanKas,
            'per_bidang'          => $perBidang,
            'rincian'             => $rincian,
            'opsi_sumbangan'      => $kasList->where('menerima_sumbangan', true)->pluck('nama')->values()->all(),
            'dana_lain'           => $saved?->dana_lain_json ?? [],
            'dana_sumbangan'      => $saved?->dana_sumbangan_json ?? [],
            'insentif_sekretaris' => !empty($saved?->insentif) ? $saved->insentif : $this->defaultInsentif(),
            'saved_raw' => [
                'saldo_awal'    => $saldoAwalFinal,
                'debit_manual'  => $debitManual,
                'kredit_manual' => $kreditManual,
                'cos'           => $saved?->cos ?? 0,
            ],
        ];
    }
}