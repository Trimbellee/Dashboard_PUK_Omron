<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Exports\PengajuanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\BrevoMailService;
use App\Models\LaporanBendahara;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;

class PengajuanController extends Controller
{
    public function __construct()
    {
        if (!session()->has('user')) abort(401);
    }

    protected function requireRole(array $roles)
{
    $user = session('user');

    if (!$user || !in_array($user['role'] ?? '', $roles)) {
        abort(403,'Unauthorized');
    }
}
    /* ===================== */
    /* VIEW */
    /* ===================== */

    public function index()
    {
        $bidangList = \App\Models\Pengajuan::select('bidang')
            ->distinct()
            ->orderBy('bidang')
            ->pluck('bidang');

        return view('pengajuan', compact('bidangList'));
    }

    /* ===================== */
    /* DATA (SERVER SIDE) */
    /* ===================== */

    public function data(Request $request)
{
    $q = Pengajuan::query();

    // SEARCH
    if ($request->search) {
        $search = $request->search;
        $q->where(function ($query) use ($search) {
            $query->where('nama', 'like', "%$search%")
                  ->orWhere('tujuan', 'like', "%$search%")
                  ->orWhere('keperluan', 'like', "%$search%");
        });
    }

    // FILTER STATUS / BIDANG
    if ($request->status) $q->where('status', $request->status);
    if ($request->bidang) $q->where('bidang', $request->bidang);

    // FILTER BULAN & TAHUN
    if ($request->bulan && $request->tahun) {
        $start = Carbon::create($request->tahun, $request->bulan, 1)->startOfMonth();
        $end   = Carbon::create($request->tahun, $request->bulan, 1)->endOfMonth();
        $q->whereBetween('tanggal', [$start, $end]);
    }

    $limit = $request->limit ?? 10;
    $page  = $request->page ?? 1;

    // Hanya ambil kolom yang dibutuhkan
	$columns = [
  	  'id','nama','bidang','tujuan','keperluan',
  	  'biaya','status','tanggal','approver',
  	  'catatan','aktual_biaya','dikembalikan',
  	  'lampiran_proposal','lampiran_laporan',
  	  'status_biaya','tanggal_transfer','status_pengembalian','tanggal_pengembalian' 
	];
    // PAGINATE
    $paginator = $q->orderBy('tanggal','desc')
                   ->orderBy('id','desc')
                   ->paginate($limit, $columns, 'page', $page);

    // Cache laporan bendahara
    $laporanBulan = Cache::remember('laporan_bendahara_periode', 3600, function () {
            return LaporanBendahara::selectRaw("CONCAT(tahun,'-',LPAD(bulan,2,'0')) as periode")
                ->pluck('periode')
                ->flip();
        });
    // LazyCollection mapping → hemat memory & CPU
    $items = \Illuminate\Support\LazyCollection::make($paginator->items())
        ->map(function ($p) use ($laporanBulan) {
            $periode = substr($p->tanggal, 0, 7);

            return [
                'ID' => $p->id,
                'Nama' => $p->nama,
                'Bidang' => $p->bidang,
                'Tujuan' => $p->tujuan,
                'Keperluan' => $p->keperluan,
                'Biaya' => $p->biaya,
                'Status' => $p->status,
                'Tanggal' => $p->tanggal,
                'Approver' => $p->approver,
                'Catatan' => $p->catatan,
                'AktualBiaya' => $p->aktual_biaya,
                'Dikembalikan' => $p->dikembalikan,
                'LampiranProposal' => $p->lampiran_proposal,
                'LampiranLaporan' => $p->lampiran_laporan,
		'StatusBiaya' => $p->status_biaya,
		'TanggalTransfer' => $p->tanggal_transfer,
                'LaporanBendaharaAda' => isset($laporanBulan[$periode]),
		'StatusPengembalian' => $p->status_pengembalian,
                'TanggalPengembalian'=> $p->tanggal_pengembalian
            ];
        })
        ->all(); // ambil array untuk response JSON

    return response()->json([
        'success' => true,
        'data' => $items,
        'page' => $paginator->currentPage(),
        'totalPages' => $paginator->lastPage(),
        'total' => $paginator->total()
    ]);
}

    /* ===================== */
    /* STORE */
    /* ===================== */

    public function store(Request $request)
    {
        try {
            $request->validate([
                'Nama' => 'required|string|max:255',
                'Bidang' => 'required|string|max:255',
                'Tujuan' => 'required|string|max:255',
                'Keperluan' => 'required|string',
                'Biaya' => 'required|numeric',
                'LampiranProposal' => 'nullable|file|mimes:pdf|max:5120'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
        }

        $id = (string) Str::uuid();
        $path = null;

        if ($request->hasFile('LampiranProposal')) {
            $path = $request->file('LampiranProposal')
                ->storeAs('file/proposal', "{$id}.pdf");
        }

        $pengajuan = Pengajuan::create([
            'id' => $id,
            'nama' => $request->Nama,
            'bidang' => $request->Bidang,
            'tujuan' => $request->Tujuan,
            'keperluan' => $request->Keperluan,
            'biaya' => $request->Biaya,
            'lampiran_proposal' => $path,
            'status' => 'Menunggu',
            'tanggal' => now(),
            'created_by' => session('user.username')
        ]);

        $emails = $this->emailsByRole(['Ketua', 'Sekretaris']);

	$this->sendEmails(
    		$emails,
    		new \App\Mail\PengajuanBaruMail($pengajuan)
	);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim'
        ]);
    }

    /* ===================== */
    /* UPDATE */
    /* ===================== */

    public function update(Request $request)
    {
        $request->validate(['ID' => 'required']);

        $p = Pengajuan::findOrFail($request->ID);

        if ($p->created_by !== session('user.username')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengedit pengajuan ini'
            ], 403);
        }

        if ($p->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan sudah diproses'
            ]);
        }

        $p->update([
            'nama' => $request->Nama,
            'bidang' => $request->Bidang,
            'tujuan' => $request->Tujuan,
            'keperluan' => $request->Keperluan,
            'biaya' => $request->Biaya,
        ]);

        if ($request->hasFile('LampiranProposal')) {

            if ($p->lampiran_proposal && Storage::exists($p->lampiran_proposal)) {
                Storage::delete($p->lampiran_proposal);
            }

            $p->lampiran_proposal = $request->file('LampiranProposal')
                ->storeAs('file/proposal', "{$p->id}.pdf");

            $p->save();
        }

        return response()->json(['success' => true]);
    }

	public function uploadLaporan(Request $request)
{
    $request->validate([
        'ID' => 'required',
        'LampiranLaporan' => 'required|file|mimes:pdf|max:5120',
        'AktualBiaya' => 'required|numeric|min:0'
    ]);

    $p = Pengajuan::findOrFail($request->ID);

    if ($this->isBulanLocked($p->tanggal)) {
        return response()->json([
            'success' => false,
            'message' => 'Bulan ini sudah ada laporan bendahara. Data tidak bisa diubah.'
        ]);
    }

    if ($p->created_by !== session('user.username')) {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak berhak upload laporan'
        ], 403);
    }

    $file = $request->file('LampiranLaporan');

    $aktual = (float) $request->AktualBiaya;
    $biayaPengajuan = (float) $p->biaya;

    /*
    =====================
    HAPUS FILE LAMA
    =====================
    */

    if ($p->lampiran_laporan && Storage::exists($p->lampiran_laporan)) {
        Storage::delete($p->lampiran_laporan);
    }

    /*
    =====================
    SIMPAN FILE BARU
    =====================
    */

    $path = $file->storeAs('file/laporan', "{$p->id}.pdf");

    /*
    =====================
    HITUNG BIAYA
    =====================
    */

    $p->lampiran_laporan = $path;
    $p->aktual_biaya = $aktual;
    $p->dikembalikan = $biayaPengajuan - $aktual;

    $p->save();

    /*
    =====================
    ATTACHMENT PDF
    =====================
    */

    $attachments = [];

    try {

        $content = Storage::get($path);

        $attachments[] = BrevoMailService::makeAttachmentFromContent(
            $content,
            'Laporan-'.$p->id.'.pdf'
        );

    } catch (\Throwable $e) {

        \Log::warning('Attachment gagal dibuat', [
            'pengajuan_id' => $p->id,
            'error' => $e->getMessage()
        ]);
    }

    /*
    =====================
    EMAIL NOTIFIKASI
    =====================
    */

    $emails = $this->emailsByRole(['Bendahara','Ketua','Sekretaris']);

    $this->sendEmails(
        $emails,
        new \App\Mail\LaporanUploadedMail($p),
        $attachments
    );

    $this->notifySelisihDana($p);

    return response()->json([
        'success' => true,
        'message' => 'Laporan berhasil diupload'
    ]);
}
 

    public function approve(Request $request)
{
    $this->requireRole(['Ketua', 'Sekretaris']);

    $p = Pengajuan::findOrFail($request->id);

	$p->update([
    		'status' => 'Disetujui',
    		'approver' => session('user.username'),
    		'catatan' => $request->catatan
	]);
    $emails = [];

	$pembuat = User::where('username', $p->created_by)->first();
		if ($pembuat?->email) {
    		$emails[] = $pembuat->email;
	}

	$emails = array_merge(
    		$emails,
    		$this->emailsByRole(['Bendahara','Ketua','Sekretaris'])
	);

	$this->sendEmails(
   		$emails,
   		new \App\Mail\StatusPengajuanMail($p,'Disetujui')
	);

    return response()->json(['success' => true]);
}

    public function reject(Request $request)
{
    $this->requireRole(['Ketua', 'Sekretaris']);

	$p = Pengajuan::findOrFail($request->id);

	$p->update([
   	  'status' => 'Ditolak',
  	  'approver' => session('user.username'),
  	  'catatan' => $request->catatan
	]);

	$emails = [];

	$pembuat = User::where('username', $p->created_by)->first();
		if ($pembuat?->email) {
    		$emails[] = $pembuat->email;
	}

	$emails = array_merge(
    		$emails,
    		$this->emailsByRole(['Ketua','Sekretaris'])
	);

	$this->sendEmails(
    		$emails,
    		new \App\Mail\StatusPengajuanMail($p,'Ditolak')
	);
    

    return response()->json(['success' => true]);
}

    public function delete(Request $request)
    {
        $p = Pengajuan::findOrFail($request->id);

        if ($p->created_by !== session('user.username')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak menghapus pengajuan ini'
            ], 403);
        }

        if ($p->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan sudah diproses'
            ]);
        }

        $files = array_filter([
            $p->lampiran_proposal,
            $p->lampiran_laporan
        ]);

        if ($files) {
            Storage::delete($files);
        }

        $p->delete();

        return response()->json(['success' => true]);
    }

    public function previewFile($type, $id)
    {	
	if(!session()->has('user')){
	    abort(403);
	}

        if (!in_array($type, ['proposal', 'laporan'])) {
            abort(404);
        }

        $p = Pengajuan::findOrFail($id);

        $path = "file/{$type}/{$id}.pdf";

        if (!Storage::exists($path)) {
            abort(404);
        }

        return response()->file(
            Storage::path($path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportExcel(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $status = $request->status;

        $namaFile = 'Pengajuan_Dana_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new PengajuanExport($bulan, $tahun, $status),
            $namaFile
        );
    }

    protected function emailsByRole(array $roles)
{
    return User::whereIn('role', $roles)
        ->whereNotNull('email')
        ->pluck('email')
        ->unique()
        ->values()
        ->toArray();
}

/**
 * Render mailable SEKALI lalu kirim ke banyak penerima.
 * Mencegah: (1) double-build, (2) timeout per-penerima menumpuk.
 */
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

    /* =====================
     * STATUS TRANSFER DANA
     * ===================== */
    public function updateStatusBiaya(Request $request)
    { 
        $this->requireRole(['Bendahara']);

        $p = Pengajuan::findOrFail($request->id);

        if ($p->status_biaya === 'Transfer') {
            return response()->json([
                'success' => false,
                'message' => 'Dana sudah pernah ditransfer'
            ]);
        }

        $p->status_biaya = 'Transfer';
        $p->tanggal_transfer = now();
        $p->save();

        // ambil email pengaju
        $pembuat = User::where('username',$p->created_by)->first();

    if ($pembuat?->email) {

        $this->sendEmails(
            [$pembuat->email],
            new \App\Mail\DanaDitransferMail($p)
        );

    }

        return response()->json([
            'success' => true
        ]);
    }


     /* =====================
     * kONFIRMASI PENGEMBALIAN
     * DANA
     * ===================== */
     public function konfirmasiPengembalian(Request $request)
{
    $this->requireRole(['Bendahara']);

    $p = Pengajuan::findOrFail($request->id);

    $p->status_pengembalian = 'Sudah';
    $p->tanggal_pengembalian = now();
    $p->save();

    // ambil email pembuat
    $pembuat = User::where('username', $p->created_by)->first();

    if ($pembuat?->email) {

        $this->sendEmails(
            [$pembuat->email],
            new \App\Mail\SisaDanaDiterimaMail($p)
        );

    }

    return response()->json([
        'success' => true
    ]);
}

    /* =====================
     * kONFIRMASI TRANSFER
     * KEKURANGAN DANA
     * ===================== */

     public function konfirmasiTransferKurang(Request $request)
{
    $this->requireRole(['Bendahara']);

    $p = Pengajuan::findOrFail($request->id);

    $p->status_pengembalian = 'Sudah';
    $p->tanggal_pengembalian = now();
    $p->save();

    $pembuat = User::where('username', $p->created_by)->first();

    if ($pembuat?->email) {

        $this->sendEmails(
            [$pembuat->email],
            new \App\Mail\KekuranganDanaDitransferMail($p)
        );

    }

    return response()->json([
        'success' => true
    ]);
}

    private function isBulanLocked($tanggal)
    {
        $tanggal = Carbon::parse($tanggal);

        return LaporanBendahara::where([
            'bulan' => $tanggal->month,
            'tahun' => $tanggal->year
        ])->exists();
    }

	protected function notifySelisihDana($p)
    {
	    $pembuat = User::where('username',$p->created_by)->first();

	    if (!$pembuat?->email) {
        return;
	    }

	    if ($p->dikembalikan != 0) {

	        $this->sendEmails(
	            [$pembuat->email],
	            new \App\Mail\SelisihDanaMail($p)
	        );

	    }
     }

}