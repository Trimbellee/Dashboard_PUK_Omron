<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // =========================
        // DEFAULT FILTER (IDENTIK VERSI LAMA)
        // =========================
        $currentMonth = (int) date('m');
        $currentYear  = (int) date('Y');

        $bulan  = $request->input('bulan');
        $tahun  = $request->input('tahun');
        $bidang = $request->input('bidang');

        if ($bulan === null) {
            $bulan = $currentMonth;
        } elseif ($bulan === 'all') {
            $bulan = '';
        }

        if ($tahun === null) {
            $tahun = $currentYear;
        } elseif ($tahun === 'all') {
            $tahun = '';
        }

        if (!$bidang) {
            $bidang = '';
        }

        $apiUrl = env('API_URL');

        // =========================
        // DEFAULT DATA (ANTI NULL)
        // =========================
        $stat = $this->emptyStat();
        $chartData = [];

        // =========================
        // STATISTIK (ROOT RESPONSE)
        // =========================
        try {
            $response = Http::timeout(6)
                ->retry(1, 300)
                ->get($apiUrl, [
                    'action' => 'getStatistik',
                    'bulan'  => $bulan,
                    'tahun'  => $tahun,
                    'bidang' => $bidang,
                ]);

            if ($response->ok() && is_array($response->json())) {
                $data = $response->json();

                $stat = [
                    'total'           => (int) ($data['total'] ?? 0),
                    'totalAmount'     => (int) ($data['totalAmount'] ?? 0),
                    'menunggu'        => (int) ($data['menunggu'] ?? 0),
                    'menungguAmount'  => (int) ($data['menungguAmount'] ?? 0),
                    'disetujui'       => (int) ($data['disetujui'] ?? 0),
                    'disetujuiAmount' => (int) ($data['disetujuiAmount'] ?? 0),
                    'ditolak'         => (int) ($data['ditolak'] ?? 0),
                    'ditolakAmount'   => (int) ($data['ditolakAmount'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard getStatistik error', [
                'error' => $e->getMessage(),
            ]);
        }

        // =========================
        // CHART PER BIDANG
        // =========================
        try {
            $response = Http::timeout(6)
                ->retry(1, 300)
                ->get($apiUrl, [
                    'action' => 'getApprovedByBidang',
                    'bulan'  => $bulan,
                    'tahun'  => $tahun,
                    'bidang' => $bidang,
                ]);

            if ($response->ok() && is_array($response->json())) {
                $data = $response->json();
                $chartData = array_map(
                    fn ($v) => (int) $v,
                    $data['approvedByBidang'] ?? []
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard chart error', [
                'error' => $e->getMessage(),
            ]);
        }

        // =========================
        // VIEW
        // =========================
        return view('dashboard', [
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'bidang'     => $bidang,
            'stat'       => $stat,
            'chartData'  => $chartData,
            'bidangList' => $this->getBidangList(),
        ]);
    }

    // =========================
    // DEFAULT STAT (ANTI ERROR)
    // =========================
    private function emptyStat(): array
    {
        return [
            'total'           => 0,
            'totalAmount'     => 0,
            'menunggu'        => 0,
            'menungguAmount'  => 0,
            'disetujui'       => 0,
            'disetujuiAmount' => 0,
            'ditolak'         => 0,
            'ditolakAmount'   => 0,
        ];
    }

    // =========================
    // BIDANG LIST
    // =========================
    private function getBidangList(): array
    {
        return [
            'Ketua', 'Sekretaris', 'Bendahara',
            'Bidang 1', 'Bidang 2', 'Bidang 3',
            'Bidang 4', 'Bidang 5', 'Bidang 6',
            'GM', 'KorLap'
        ];
    }
}
