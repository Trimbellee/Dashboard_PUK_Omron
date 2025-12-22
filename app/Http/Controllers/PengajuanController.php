<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AppsScriptService;

class PengajuanController extends Controller
{
    public function __construct(private AppsScriptService $api)
    {
        // 🔒 BLOK SEMUA REQUEST TANPA LOGIN (POSTMAN GUGUR)
        if (!session()->has('user')) {
            abort(401, 'Unauthorized');
        }
    }

    /* =====================
     *  HELPER ROLE CHECK
     * ===================== */
    private function requireRole(array $roles)
    {
        if (!in_array(session('user.role'), $roles)) {
            abort(403, 'Forbidden');
        }
    }

    /* =====================
     *  VIEW
     * ===================== */
    public function index()
    {
        return view('pengajuan');
    }

    /* =====================
     *  DATA TABLE
     * ===================== */
    public function data(Request $request)
    {
        $res = $this->api->request('get', [
            'action' => 'getPengajuan',
            'page'   => $request->page,
            'limit'  => $request->limit,
            'status' => $request->status,
            'bulan'  => $request->bulan,
            'tahun'  => $request->tahun,
            'bidang' => $request->bidang,
        ]);

        return response()->json(
            $res['success']
                ? $res['data']
                : ['success'=>false,'message'=>$res['message']]
        );
    }

    /* =====================
     *  ADD PENGAJUAN + PROPOSAL
     * ===================== */
    public function store(Request $request)
    {
        $this->requireRole(['Pengaju']);

        $request->validate([
            'Nama'              => 'required',
            'Bidang'            => 'required',
            'Tujuan'            => 'required',
            'Keperluan'         => 'required',
            'Biaya'             => 'required|numeric',
            'LampiranProposal'  => 'required|file|mimes:pdf|max:4096'
        ]);

        $file = $request->file('LampiranProposal');

        $res = $this->api->request('post', [
            'action'           => 'addPengajuan',
            'Nama'             => $request->Nama,
            'Bidang'           => $request->Bidang,
            'Tujuan'           => $request->Tujuan,
            'Keperluan'        => $request->Keperluan,
            'Biaya'            => $request->Biaya,
            'fileBase64'       => base64_encode(file_get_contents($file)),
            'fileName'         => $file->getClientOriginalName(),
            'mimeType'         => $file->getMimeType(),
        ], true);

        return back()->with(
            $res['success'] ? 'success' : 'error',
            $res['success'] ? 'Pengajuan berhasil dikirim.' : $res['message']
        );
    }

    /* =====================
     *  UPLOAD / OVERWRITE LAPORAN
     * ===================== */
    public function uploadLaporan(Request $request)
    {
        $this->requireRole(['Pengaju']);

        $request->validate([
            'ID'                => 'required',
            'LampiranLaporan'   => 'required|file|mimes:pdf|max:4096'
        ]);

        $file = $request->file('LampiranLaporan');

        $res = $this->api->request('post', [
            'action'     => 'uploadLaporan',
            'ID'         => $request->ID,
            'fileBase64' => base64_encode(file_get_contents($file)),
            'fileName'   => $file->getClientOriginalName(),
            'mimeType'   => $file->getMimeType()
        ], true);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['success']
                ? 'Laporan kegiatan berhasil diupload.'
                : $res['message']
        ]);
    }

    /* =====================
     *  UPDATE AKTUAL BIAYA
     * ===================== */
    public function updateAktualBiaya(Request $request)
    {
        $this->requireRole(['Bendahara']);

        $request->validate([
            'ID'           => 'required',
            'AktualBiaya'  => 'required|numeric'
        ]);

        $res = $this->api->request('post', [
            'action'      => 'updateAktualBiaya',
            'ID'          => $request->ID,
            'AktualBiaya' => $request->AktualBiaya
        ], true);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['success']
                ? 'Aktual biaya berhasil diperbarui.'
                : $res['message']
        ]);
    }

    /* =====================
     *  APPROVE
     * ===================== */
    public function approve(Request $request)
    {
        $this->requireRole(['Ketua','Sekretaris']);

        $res = $this->api->request('post', [
            'action'   => 'approvePengajuan',
            'ID'       => $request->id,
            'Approver' => session('user.username'),
            'Catatan'  => $request->catatan ?? ''
        ], true);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['success']
                ? 'Pengajuan disetujui.'
                : $res['message']
        ]);
    }

    /* =====================
     *  REJECT
     * ===================== */
    public function reject(Request $request)
    {
        $this->requireRole(['Ketua','Sekretaris']);

        $res = $this->api->request('post', [
            'action'   => 'rejectPengajuan',
            'ID'       => $request->id,
            'Approver' => session('user.username'),
            'Catatan'  => $request->catatan ?? ''
        ], true);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['success']
                ? 'Pengajuan ditolak.'
                : $res['message']
        ]);
    }

    /* =====================
     *  EDIT
     * ===================== */
   public function update(Request $request)
    {
        $this->requireRole(['Pengaju']);

        $request->validate([
            'ID'        => 'required',
            'Nama'      => 'required|string|max:255',
            'Bidang'    => 'required|string|max:255',
            'Tujuan'    => 'required|string|max:255',
            'Keperluan' => 'required|string|max:255',
            'Biaya'     => 'required|numeric',
            'LampiranProposal' => 'nullable|file|mimes:pdf|max:4096'
        ]);

        $payload = [
            'action'    => 'editPengajuan', // nama function Apps Script
            'ID'        => $request->ID,
            'Nama'      => $request->Nama,
            'Bidang'    => $request->Bidang,
            'Tujuan'    => $request->Tujuan,
            'Keperluan' => $request->Keperluan,
            'Biaya'     => $request->Biaya
        ];

        // Jika ada file proposal, kirim sebagai base64
        if ($request->hasFile('LampiranProposal')) {
            $file = $request->file('LampiranProposal');
            $payload['fileBase64'] = base64_encode(file_get_contents($file));
            $payload['fileName']   = $file->getClientOriginalName();
            $payload['mimeType']   = $file->getMimeType();
        }

        // Panggil Apps Script
        $res = $this->api->request('post', $payload, true);

        // Kembalikan response JSON ke frontend
        return response()->json([
            'success' => $res['success'] ?? false,
            'message' => $res['message'] ?? 'Gagal update pengajuan'
        ]);
    }



    /* =====================
     *  DELETE
     * ===================== */
    public function delete(Request $request)
    {
        $this->requireRole(['Pengaju']);

        $res = $this->api->request('post', [
            'action' => 'deletePengajuan',
            'ID'     => $request->id
        ], true);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['success']
                ? 'Pengajuan dihapus.'
                : $res['message']
        ]);
    }

    /* =====================
     *  EXPORT
     * ===================== */
    public function export(Request $request)
    {
        $this->requireRole(['Ketua','Sekretaris','Bendahara']);

        $res = $this->api->rawJson([
            'action' => 'exportPengajuan',
            'bulan'  => (int)($request->bulan ?? 0),
            'tahun'  => (int)($request->tahun ?? 0)
        ]);

        if (!($res['success'] ?? false)) {
            return back()->with('error', $res['message'] ?? 'Export gagal');
        }

        return response(base64_decode($res['file']))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$res['filename'].'"');
    }
}
