<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\BelanjaKoperasi;
use App\Models\LaporanBendahara;
use App\Models\LaporanBendaharaArsip;
use App\Models\User;
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
    private const STATUS_MENUNGGU_SEKRETARIS  = 'menunggu_sekretaris';
    private const STATUS_MENUNGGU_KETUA       = 'menunggu_ketua';
    private const STATUS_SELESAI              = 'selesai';

    public function index(Request $request)
    {   
        $role = session('user')['role'] ?? null;

        if (!in_array($role, ['Bendahara', 'Sekretaris', 'Ketua'])) {
            abort(403, 'Tidak diizinkan mengakses halaman ini');
        }
        $bulan = (int)($request->bulan ?? now()->month);
        $tahun = (int)($request->tahun ?? now()->year);

        $laporan = $this->getLaporanData($bulan, $tahun);

        return view('laporan-bendahara.index', compact('laporan'));
    }

    /* ============================================================
            STORE
            Menyimpan draft laporan & generate PDF pratinjau
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
            $bulan = (int)$request->bulan;
            $tahun = (int)$request->tahun;

            if (!$bulan || !$tahun) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulan & tahun wajib'
                ], 422);
            }

            $existing = LaporanBendahara::where('bulan', $bulan)->where('tahun', $tahun)->first();

            if ($existing && !in_array($existing->status, [self::STATUS_DRAFT])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan sudah diajukan, tidak bisa diubah lagi.'
                ], 422);
            }

            DB::transaction(function () use ($request, $bulan, $tahun) {
                $laporan = LaporanBendahara::updateOrCreate(
                    ['bulan' => $bulan, 'tahun' => $tahun],
                    [
                        'cos'                 => (int)$request->cos,
                        'pajak_bank'          => (int)$request->pajak_bank,
                        'saldo_awal_json'     => $request->saldo_awal_json ?? [],
                        'debit_manual_json'   => $request->debit_manual_json ?? [],
                        'kredit_manual_json'  => $request->kredit_manual_json ?? [],
                        'dana_lain_json'      => $request->dana_lain_json ?? [],
                        'dana_sumbangan_json' => $request->dana_sumbangan_json ?? [],
                        'insentif_json'       => $request->insentif ?? $this->defaultInsentif(),
                        'debit_json'          => $request->debit_json ?? [],
                        'kredit_json'         => $request->kredit_json ?? [],
                        'total_json'          => $request->total_json ?? [],
                        'status'              => self::STATUS_DRAFT,

                        // laporan sudah diperbaiki -> bersihkan flag "perlu revisi"
                        'catatan_revisi'      => null,
                        'ditolak_oleh'        => null,
                        'ditolak_role'        => null,
                        'ditolak_at'          => null,
                        
                    ]
                    
                );

                $laporan->bendahara_nama    = null;
                $laporan->bendahara_ttd_at  = null;
                $laporan->sekretaris_nama   = null;
                $laporan->sekretaris_ttd_at = null;
                $laporan->ketua_nama        = null;
                $laporan->ketua_ttd_at      = null;
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

        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

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

        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

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

        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

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
        TOLAK — SEKRETARIS
        menunggu_sekretaris -> draft (dengan catatan revisi)
    ============================================================ */
    public function rejectSekretaris(Request $request)
    {
        return $this->reject($request, 'Sekretaris', self::STATUS_MENUNGGU_SEKRETARIS);
    }

    /* ============================================================
        TOLAK — KETUA
        menunggu_ketua -> draft (dengan catatan revisi)
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

        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

        // simpan email bendahara SEBELUM field bendahara_nama di-null-kan
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

        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

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

                    'cos'                 => $laporan->cos,
                    'pajak_bank'          => $laporan->pajak_bank,
                    'saldo_awal_json'     => $laporan->saldo_awal_json,
                    'debit_manual_json'   => $laporan->debit_manual_json,
                    'kredit_manual_json'  => $laporan->kredit_manual_json,
                    'dana_lain_json'      => $laporan->dana_lain_json,
                    'dana_sumbangan_json' => $laporan->dana_sumbangan_json,
                    'insentif_json'       => $laporan->insentif_json,
                    'debit_json'          => $laporan->debit_json,
                    'kredit_json'         => $laporan->kredit_json,
                    'total_json'          => $laporan->total_json,
                    'riwayat_json'        => $laporan->riwayat_json,

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
        HELPER — catat satu entri riwayat audit
        Dipanggil SEBELUM save(), jadi controller lain yang
        memanggil harus tetap memanggil $laporan->save() sendiri.
    ============================================================ */
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

    /* ============================================================
        HELPER — respon error konsisten untuk semua endpoint status
    ============================================================ */
    private function respondError(\Throwable $e)
    {
        $status = $e instanceof \Illuminate\Validation\ValidationException
            ? 422
            : ($e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage() ?: 'Terjadi kesalahan',
        ], $status ?: 500);
    }

    /* ============================================================
        HELPER — email berdasarkan role (sama seperti PengajuanController)
    ============================================================ */
    private function emailsByRole(array $roles): array
    {
        return User::whereIn('role', $roles)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->toArray();
    }

    /* ============================================================
        HELPER — cari email berdasarkan username (untuk notif ke Bendahara)
    ============================================================ */
    private function emailByUsername(?string $username): ?string
    {
        if (!$username) return null;

        return User::where('username', $username)->value('email');
    }

    /* ============================================================
        HELPER — render mailable sekali, kirim ke banyak penerima
    ============================================================ */
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

        // ambil mapping email -> username sekali query, bukan per-email
        $namaByEmail = User::whereIn('email', $emails)
            ->pluck('username', 'email');

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

    /* ============================================================
        HELPER — ambil TTD user sebagai base64 data URI untuk PDF
        (DomPDF sering gagal load gambar dari URL asset(), jadi
        dikonversi ke base64 langsung dari file lokal)
    ============================================================ */
    private function getTtdBase64(?string $username): ?string
    {
        if (!$username) {
            return null;
        }

        $user = User::where('username', $username)->first();

        if (!$user || !$user->ttd) {
            return null;
        }

        $path = storage_path('app/public/' . $user->ttd);

        if (!file_exists($path)) {
            return null;
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /* ============================================================
        GENERATE PDF (reusable — dipanggil store() & approval)
    ============================================================ */
    private function generatePdfLaporan(int $bulan, int $tahun): string
    {
        $laporan = $this->getLaporanData($bulan, $tahun);

        $pdf = PDF::loadView('laporan-bendahara.pdf', [
            'laporan' => $laporan
        ])->setPaper('A4', 'portrait');

        $filename = "Laporan Bendahara {$bulan}-{$tahun}.pdf";
        $path = "laporan-bendahara/{$tahun}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        // tambahkan versi (timestamp) supaya browser tidak pakai cache lama
        $pdfUrl = asset('storage/' . $path) . '?v=' . now()->timestamp;

        LaporanBendahara::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->update(['pdf_url' => $pdfUrl]);

        return $pdfUrl;
    }

    /* ============================================================
        DEFAULT INSENTIF
        PADANAN: getDefaultInsentifSekretariat
    ============================================================ */
    private function defaultInsentif()
    {
        return [
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Ketua','nominal'=>1150000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Sekretaris','nominal'=>950000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Bendahara','nominal'=>750000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 1','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 2','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 3','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 4','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 5','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 6','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 1','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 2','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 3','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 4','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 5','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 6','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif PANGKORMI','nominal'=>400000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif PANGKORLAP','nominal'=>400000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Pengurus Struktural Organisasi di atas PUK','nominal'=>500000],
        ];
    }

    /* ============================================================
            GET LAPORAN DATA
            Menyusun semua data untuk halaman & PDF
    ============================================================ */
    private function getLaporanData(int $bulan, int $tahun): array
    {
        /* =========================
        DATA PENGAJUAN
        ========================= */
        $pengajuan = Pengajuan::query()
            ->where('status', 'Disetujui')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->map(function ($r) {
                return [
                    'Bidang'     => strtoupper(trim($r->bidang)),
                    'Tujuan'     => $r->tujuan,
                    'Keperluan'  => $r->keperluan,
                    'Aktual'     => (int)($r->aktual_biaya ?? 0),
                    'Tanggal'    => optional($r->tanggal)->format('d M Y'),
                ];
            });

        /* =========================
        BELANJA KOPERASI
        ========================= */
        $koperasi = BelanjaKoperasi::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->map(fn($r) => [
                'Bidang'    => strtoupper(trim($r->bidang)),
                'Tujuan'    => $r->tujuan,
                'Keperluan' => $r->keperluan,
                'Aktual'    => (int)$r->biaya,
                'Tanggal'   => $r->tanggal
                                ? Carbon::parse($r->tanggal)->format('d M Y')
                                : '-',
            ]);

        /* =========================
        GROUP PER BIDANG
        ========================= */
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

        /* =========================
        DATA LAPORAN TERSIMPAN & RINGKASAN KAS
        ========================= */
        $saved = LaporanBendahara::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        /* =========================
        SALDO AWAL PRIORITY
            1. total_json bulan sebelumnya
            2. saldo_awal bulan ini
            3. default 0
        ========================= */
        $prevDate = Carbon::create($tahun, $bulan, 1)->subMonth();
        $prevLaporan = LaporanBendahara::where('bulan', $prevDate->month)
            ->where('tahun', $prevDate->year)
            ->first();

        $saldoAwalFinal = [];

        $jenisKas = [
            'Dana Operasional','Dana Perjuangan','Dana Sosial','Dana Permusyawaratan',
            'Dana Sosial Politik','Bunga Giro','Bunga Deposito','COS DPP',
            'Biaya Transfer',
        ];

        foreach ($jenisKas as $j) {
            if ($prevLaporan && !empty($prevLaporan->total_json[$j])) {
                $saldoAwalFinal[$j] = $prevLaporan->total_json[$j];
            } elseif ($saved && !empty($saved->saldo_awal_json[$j])) {
                $saldoAwalFinal[$j] = $saved->saldo_awal_json[$j];
            } else {
                $saldoAwalFinal[$j] = 0;
            }
        }

        /* =========================
        RINGKASAN KAS (DARI DATA TERSIMPAN)
        ========================= */
        $ringkasanKasPdf = [];
        $no = 1;

        foreach ($jenisKas as $j) {
            $ringkasanKasPdf[] = [
                'no'         => $no++,
                'jenis'      => $j,
                'saldo_awal' => $saved?->saldo_awal_json[$j] ?? 0,
                'debit'      => $saved?->debit_json[$j] ?? 0,
                'kredit'     => $saved?->kredit_json[$j] ?? 0,
                'total'      => $saved?->total_json[$j] ?? 0,
            ];
        }
        
        $arsipVersi = $saved
        ? LaporanBendaharaArsip::where('laporan_bendahara_id', $saved->id)
            ->orderByDesc('versi_ke')
            ->get(['versi_ke', 'pdf_url', 'dibuka_oleh', 'alasan_buka_kembali', 'dibuka_at'])
        : collect();

        // TTD user (base64) untuk ditampilkan di PDF
        $bendaharaTtd  = $this->getTtdBase64($saved?->bendahara_nama);
        $sekretarisTtd = $this->getTtdBase64($saved?->sekretaris_nama);
        $ketuaTtd      = $this->getTtdBase64($saved?->ketua_nama);

        return [
            'meta' => [
                'bulan'   => $bulan,
                'tahun'   => $tahun,
                'is_edit' => (bool)$saved,

                'status'             => $saved?->status ?? 'draft',

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

                // info penolakan / revisi terakhir (hanya terisi selama status draft hasil tolak)
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
            'pajak_bank' => $saved?->pajak_bank ?? 0,
            'ringkasan_kas'       => $ringkasanKasPdf,
            'per_bidang'          => $perBidang,
            'dana_lain'           => $saved?->dana_lain_json ?? [],
            'dana_sumbangan'      => $saved?->dana_sumbangan_json ?? [],
            'insentif_sekretaris' => $saved?->insentif_json ?? $this->defaultInsentif(),
            'saved_raw' => [
                'saldo_awal'    => $saldoAwalFinal,
                'debit_manual'  => $saved?->debit_manual_json ?? [],
                'kredit_manual' => $saved?->kredit_manual_json ?? [],
                'cos'           => $saved?->cos ?? 0,
                'pajak_bank'    => $saved?->pajak_bank ?? 0,
            ],
        ];
    }
}