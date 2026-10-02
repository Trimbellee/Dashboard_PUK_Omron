<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Exports\PengajuanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LaporanBendahara;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\BidangMaster;
use App\Helpers\PdfCompressor;

class PengajuanController extends Controller
{
    public function __construct()
    {
        if (!session()->has('user')) abort(401);
    }

    protected function requireRole(array $roles): void
    {
        $user = session('user');
        if (!$user || !in_array($user['role'] ?? '', $roles)) {
            abort(403, 'Unauthorized');
        }
    }

    /* ─────────────────────────────────────────
     | HELPER — kirim mail, gagal tidak meledak
     ─────────────────────────────────────────*/
    protected function sendMailSafe($mailable, $to): void
    {
        try {
            if (is_array($to)) {
                $to = array_filter($to); // buang null / kosong
            }

            if (empty($to)) return;

            Mail::to($to)->send($mailable);

        } catch (\Throwable $e) {
            // Catat di log tapi JANGAN lempar exception ke client
            Log::warning('[PengajuanController] Gagal kirim email: ' . $e->getMessage(), [
                'mailable' => get_class($mailable),
                'to'       => $to,
            ]);
        }
    }

    /* ─────────────────────────────────────────
     | HELPER — email berdasarkan role
     ─────────────────────────────────────────*/
    protected function emailsByRole(array $roles): array
    {
        return User::whereIn('role', $roles)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->toArray();
    }

    /* ─────────────────────────────────────────
     | HELPER — cek bulan terkunci
     ─────────────────────────────────────────*/
    private function isBulanLocked($tanggal): bool
    {
        $t = Carbon::parse($tanggal);
        return LaporanBendahara::where([
            'bulan' => $t->month,
            'tahun' => $t->year,
        ])->exists();
    }

    /* ─────────────────────────────────────────
     | HELPER — notif selisih dana
     ─────────────────────────────────────────*/
    protected function notifySelisihDana($p): void
    {
        if ($p->dikembalikan == 0) return;

        $pembuat = User::where('username', $p->created_by)->first();
        if (!$pembuat?->email) return;

        $this->sendMailSafe(new \App\Mail\SelisihDanaMail($p), $pembuat->email);
    }

    /* =====================
     | VIEW
    ===================== */
    public function index()
    {
        $bidangList = BidangMaster::namaAktif();

        return view('pengajuan', compact('bidangList'));
    }

    /* =====================
     | DATA — server-side pagination
    ===================== */
    public function data(Request $request)
    {
        $q = Pengajuan::query();

        // Search
        if ($s = $request->search) {
            $q->where(function ($query) use ($s) {
                $query->where('nama',      'like', "%{$s}%")
                      ->orWhere('tujuan',    'like', "%{$s}%")
                      ->orWhere('keperluan', 'like', "%{$s}%");
            });
        }

        // Filter
        if ($request->status) $q->where('status', $request->status);
        if ($request->bidang)  $q->where('bidang',  $request->bidang);

        if ($request->bulan && $request->tahun) {
            $start = Carbon::create($request->tahun, $request->bulan, 1)->startOfMonth();
            $end   = Carbon::create($request->tahun, $request->bulan, 1)->endOfMonth();
            $q->whereBetween('tanggal', [$start, $end]);
        }

        $limit = max(1, (int) ($request->limit ?? 10));
        $page  = max(1, (int) ($request->page  ?? 1));

        $columns = [
            'id', 'nama', 'bidang', 'tujuan', 'keperluan',
            'biaya', 'status', 'tanggal', 'approver', 'catatan',
            'aktual_biaya', 'dikembalikan', 'lampiran_proposal',
            'lampiran_laporan', 'status_biaya', 'tanggal_transfer',
            'status_pengembalian', 'tanggal_pengembalian',
        ];

        $paginator = $q->orderBy('tanggal', 'desc')
                       ->orderBy('id', 'desc')
                       ->paginate($limit, $columns, 'page', $page);

        // Cache periode laporan bendahara
        $laporanBulan = Cache::remember('laporan_bendahara_periode', 3600, function () {
            return LaporanBendahara::selectRaw("CONCAT(tahun,'-',LPAD(bulan,2,'0')) as periode")
                ->pluck('periode')
                ->flip();
        });

        $items = \Illuminate\Support\LazyCollection::make($paginator->items())
            ->map(fn($p) => [
                'ID'                  => $p->id,
                'Nama'                => $p->nama,
                'Bidang'              => $p->bidang,
                'Tujuan'              => $p->tujuan,
                'Keperluan'           => $p->keperluan,
                'Biaya'               => $p->biaya,
                'Status'              => $p->status,
                'Tanggal'             => $p->tanggal,
                'Approver'            => $p->approver,
                'Catatan'             => $p->catatan,
                'AktualBiaya'         => $p->aktual_biaya,
                'Dikembalikan'        => $p->dikembalikan,
                'LampiranProposal'    => $p->lampiran_proposal,
                'LampiranLaporan'     => $p->lampiran_laporan,
                'StatusBiaya'         => $p->status_biaya,
                'TanggalTransfer'     => $p->tanggal_transfer,
                'LaporanBendaharaAda' => isset($laporanBulan[substr($p->tanggal, 0, 7)]),
                'StatusPengembalian'  => $p->status_pengembalian,
                'TanggalPengembalian' => $p->tanggal_pengembalian,
            ])
            ->all();

        return response()->json([
            'success'    => true,
            'data'       => $items,
            'page'       => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'total'      => $paginator->total(),
        ]);
    }

    /* =====================
     | STORE
    ===================== */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'Nama'              => 'required|string|max:255',
                'Bidang'            => 'required|string|max:255',
                'Tujuan'            => 'required|string|max:255',
                'Keperluan'         => 'required|string',
                'Biaya'             => 'required|numeric',
                'LampiranProposal'  => 'nullable|file|mimes:pdf|max:5120',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        $id   = (string) Str::uuid();
        $path = null;

        if ($request->hasFile('LampiranProposal')) {
            $path = "file/proposal/{$id}.pdf";
            Storage::put($path, PdfCompressor::compress(
                $request->file('LampiranProposal')->getRealPath()
            ));
        }

        $pengajuan = Pengajuan::create([
            'id'               => $id,
            'nama'             => $request->Nama,
            'bidang'           => $request->Bidang,
            'tujuan'           => $request->Tujuan,
            'keperluan'        => $request->Keperluan,
            'biaya'            => $request->Biaya,
            'lampiran_proposal' => $path,
            'status'           => 'Menunggu',
            'tanggal'          => now(),
            'created_by'       => session('user.username'),
        ]);

        // ✅ Email aman — gagal kirim tidak membuat response 500
        $this->sendMailSafe(
            new \App\Mail\PengajuanBaruMail($pengajuan),
            $this->emailsByRole(['Ketua', 'Sekretaris'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim',
        ]);
    }

    /* =====================
     | UPDATE
    ===================== */
    public function update(Request $request)
    {
        $request->validate(['ID' => 'required']);

        $p = Pengajuan::findOrFail($request->ID);

        if ($p->created_by !== session('user.username')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengedit pengajuan ini',
            ], 403);
        }

        if ($p->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan sudah diproses',
            ]);
        }

        $p->update([
            'nama'      => $request->Nama,
            'bidang'    => $request->Bidang,
            'tujuan'    => $request->Tujuan,
            'keperluan' => $request->Keperluan,
            'biaya'     => $request->Biaya,
        ]);

        if ($request->hasFile('LampiranProposal')) {
            if ($p->lampiran_proposal && Storage::exists($p->lampiran_proposal)) {
                Storage::delete($p->lampiran_proposal);
            }
            $p->lampiran_proposal = "file/proposal/{$p->id}.pdf";
            Storage::put($p->lampiran_proposal, PdfCompressor::compress(
                $request->file('LampiranProposal')->getRealPath()
            ));
            $p->save();
        }

        return response()->json(['success' => true]);
    }

    /* =====================
     | UPLOAD LAPORAN
    ===================== */
    public function uploadLaporan(Request $request)
    {
        try {
            $request->validate([
                'ID'              => 'required',
                'LampiranLaporan' => 'required|file|mimes:pdf|max:5120',
                'AktualBiaya'     => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        $p = Pengajuan::findOrFail($request->ID);

        if ($this->isBulanLocked($p->tanggal)) {
            return response()->json([
                'success' => false,
                'message' => 'Bulan ini sudah ada laporan bendahara. Data tidak bisa diubah.',
            ]);
        }

        if ($p->created_by !== session('user.username')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak upload laporan',
            ], 403);
        }

        if ($p->lampiran_laporan && Storage::exists($p->lampiran_laporan)) {
            Storage::delete($p->lampiran_laporan);
        }

        $p->lampiran_laporan = "file/laporan/{$p->id}.pdf";
        Storage::put($p->lampiran_laporan, PdfCompressor::compress(
            $request->file('LampiranLaporan')->getRealPath()
        ));

        $p->aktual_biaya  = (float) $request->AktualBiaya;
        $p->dikembalikan  = (float) $p->biaya - $p->aktual_biaya;
        $p->save();

        // ✅ Email aman
        $this->sendMailSafe(
            new \App\Mail\LaporanUploadedMail($p),
            $this->emailsByRole(['Bendahara', 'Ketua', 'Sekretaris'])
        );

        return response()->json(['success' => true]);
    }

    /* =====================
     | APPROVE
    ===================== */
    public function approve(Request $request)
    {
        $this->requireRole(['Ketua', 'Sekretaris']);

        $p = Pengajuan::findOrFail($request->id);

        $p->update([
            'status'   => 'Disetujui',
            'approver' => session('user.username'),
            'catatan'  => $request->catatan,
        ]);

        // Email ke pembuat
        $user = User::where('username', $p->created_by)->first();
        if ($user?->email) {
            $this->sendMailSafe(
                new \App\Mail\StatusPengajuanMail($p, 'Disetujui'),
                $user->email
            );
        }

        // Email ke semua Bendahara
        $this->sendMailSafe(
            new \App\Mail\StatusPengajuanMail($p, 'Disetujui'),
            $this->emailsByRole(['Bendahara'])
        );

        return response()->json(['success' => true]);
    }

    /* =====================
     | REJECT
    ===================== */
    public function reject(Request $request)
    {
        $this->requireRole(['Ketua', 'Sekretaris']);

        $p = Pengajuan::findOrFail($request->id);

        $p->update([
            'status'   => 'Ditolak',
            'approver' => session('user.username'),
            'catatan'  => $request->catatan,
        ]);

        // ✅ Email aman
        $user = User::where('username', $p->created_by)->first();
        if ($user?->email) {
            $this->sendMailSafe(
                new \App\Mail\StatusPengajuanMail($p, 'Ditolak'),
                $user->email
            );
        }

        return response()->json(['success' => true]);
    }

    /* =====================
     | DELETE
    ===================== */
    public function delete(Request $request)
    {
        $p = Pengajuan::findOrFail($request->id);

        if ($p->created_by !== session('user.username')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak menghapus pengajuan ini',
            ], 403);
        }

        if ($p->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan sudah diproses',
            ]);
        }

        $files = array_filter([
            $p->lampiran_proposal,
            $p->lampiran_laporan,
        ]);

        if ($files) Storage::delete($files);

        $p->delete();

        return response()->json(['success' => true]);
    }

    /* =====================
     | PREVIEW PDF
    ===================== */
    public function previewFile($type, $id)
    {
        if (!session()->has('user')) abort(403);
        if (!in_array($type, ['proposal', 'laporan'])) abort(404);

        $p    = Pengajuan::findOrFail($id);
        $path = "file/{$type}/{$id}.pdf";

        if (!Storage::exists($path)) abort(404);

        return response()->file(
            Storage::path($path),
            ['Content-Type' => 'application/pdf']
        );
    }

    /* =====================
     | EXPORT EXCEL
    ===================== */
    public function exportExcel(Request $request)
    {
        $namaFile = 'Pengajuan_Dana_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new PengajuanExport($request->bulan, $request->tahun, $request->status),
            $namaFile
        );
    }

    /* =====================
     | STATUS TRANSFER DANA
    ===================== */
    public function updateStatusBiaya(Request $request)
    {
        $this->requireRole(['Bendahara']);

        $p = Pengajuan::findOrFail($request->id);

        if ($p->status_biaya === 'Transfer') {
            return response()->json([
                'success' => false,
                'message' => 'Dana sudah pernah ditransfer',
            ]);
        }

        $p->status_biaya     = 'Transfer';
        $p->tanggal_transfer = now();
        $p->save();

        // ✅ Email aman
        $user = User::where('username', $p->created_by)->first();
        if ($user?->email) {
            $this->sendMailSafe(new \App\Mail\DanaDitransferMail($p), $user->email);
        }

        return response()->json(['success' => true]);
    }

    /* =====================
     | KONFIRMASI PENGEMBALIAN SISA
    ===================== */
    public function konfirmasiPengembalian(Request $request)
    {
        $this->requireRole(['Bendahara']);

        $p = Pengajuan::findOrFail($request->id);
        $p->status_pengembalian  = 'Sudah';
        $p->tanggal_pengembalian = now();
        $p->save();

        // ✅ Email aman
        $pembuat = User::where('username', $p->created_by)->first();
        if ($pembuat?->email) {
            $this->sendMailSafe(new \App\Mail\SisaDanaDiterimaMail($p), $pembuat->email);
        }

        return response()->json(['success' => true]);
    }

    /* =====================
     | KONFIRMASI TRANSFER KEKURANGAN
    ===================== */
    public function konfirmasiTransferKurang(Request $request)
    {
        $this->requireRole(['Bendahara']);

        $p = Pengajuan::findOrFail($request->id);
        $p->status_pengembalian  = 'Sudah';
        $p->tanggal_pengembalian = now();
        $p->save();

        // ✅ Email aman
        $pembuat = User::where('username', $p->created_by)->first();
        if ($pembuat?->email) {
            $this->sendMailSafe(new \App\Mail\KekuranganDanaDitransferMail($p), $pembuat->email);
        }

        return response()->json(['success' => true]);
    }
}