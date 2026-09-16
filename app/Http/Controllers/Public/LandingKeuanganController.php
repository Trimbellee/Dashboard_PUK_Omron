<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\LaporanKeuanganService;
use Illuminate\Http\Request;

/**
 * Endpoint AJAX untuk grafik Debit vs Kredit & Breakdown per Jenis Dana
 * di landing page. Dilindungi middleware 'anggota.auth' di routes/web.php —
 * controller ini tidak perlu cek session sendiri.
 */
class LandingKeuanganController extends Controller
{
    public function __construct(private LaporanKeuanganService $laporanKeuangan)
    {
    }

    public function debitKredit(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        abort_if($tahun < 2000 || $tahun > 2100, 422, 'Tahun tidak valid');

        return response()->json($this->laporanKeuangan->getDebitKreditBulanan($tahun));
    }

    public function detailJenis(Request $request)
    {
        $jenis = (string) $request->get('jenis', '');
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = $request->get('bulan', 'semua');

        abort_if(!$jenis || $tahun < 2000 || $tahun > 2100, 422, 'Parameter tidak valid');

        $rows = $this->laporanKeuangan->getDetailJenisDana($jenis, $tahun, $bulan);

        return response()->json(['data' => $rows, 'total' => count($rows)]);
    }

    /**
     * Khusus jenis "Dana Operasional" — breakdown per bidang (bukan per
     * transaksi), sama seperti modal "Dana Operasional — Per Bidang" di
     * dashboard.
     */
    public function realisasiPerBidang(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = $request->get('bulan', 'semua');

        abort_if($tahun < 2000 || $tahun > 2100, 422, 'Tahun tidak valid');

        $rows = $this->laporanKeuangan->getRealisasiPerBidang($tahun, $bulan);

        return response()->json(['data' => $rows]);
    }
}