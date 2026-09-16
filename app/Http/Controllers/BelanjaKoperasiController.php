<?php

namespace App\Http\Controllers;

use App\Models\BelanjaKoperasi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BelanjaKoperasiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);

        $dataBelanja = BelanjaKoperasi::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('bidang')
            ->get();

        $isEdit = $dataBelanja->count() > 0;

        return view('belanja-koperasi.index', compact(
            'bulan',
            'tahun',
            'dataBelanja',
            'isEdit'
        ));
    }

    public function simpan(Request $request)
    {
        if (!in_array(session('user')['role'] ?? '', ['Sekretaris', 'Bendahara'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $rows = $request->rows ?? [];
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $mode = $request->mode;

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'Data kosong']);
        }

        if ($mode === 'update') {
            BelanjaKoperasi::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->delete();
        }

        foreach ($rows as $r) {

            if (empty($r['tanggal'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal wajib diisi'
                ], 422);
            }

            if (empty($r['keperluan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keperluan wajib diisi'
                ], 422);
            }

            if (!isset($r['biaya']) || (int)$r['biaya'] <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biaya harus di isi'
                ], 422);
            }

            try {

                $tgl = Carbon::parse($r['tanggal']);

                if (
                    $tgl->month != $bulan ||
                    $tgl->year != $tahun
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal tidak sesuai periode laporan'
                    ], 422);
                }

            } catch (\Exception $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Format tanggal tidak valid'
                ], 422);
            }
        }

        foreach ($rows as $r) {
            BelanjaKoperasi::create([
                'bulan' => $r['bulan'],
                'tahun' => $r['tahun'],
                'tanggal' => $r['tanggal'],
                'bidang' => $r['bidang'],
                'tujuan' => $r['tujuan'] ?? 'Koperasi',
                'keperluan' => $r['keperluan'],
                'biaya' => $r['biaya'],
                'timestamp' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $mode === 'update'
                ? 'Belanja koperasi berhasil diupdate'
                : 'Belanja koperasi berhasil disimpan'
        ]);
    }
}
