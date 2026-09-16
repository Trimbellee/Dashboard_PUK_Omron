<?php

namespace App\Http\Controllers;

use App\Models\LaporanBendahara;
use Illuminate\Http\Request;

class LaporanBulananController extends Controller
{
    public function index()
    {
        $laporanList = LaporanBendahara::query()
            ->select(
                'id', 'bulan', 'tahun', 'pdf_url', 'status',
                'catatan_revisi', 'dibuka_kembali_alasan',
                'dibuka_kembali_oleh', 'dibuka_kembali_at'
            )
            ->withCount('arsip')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->map(function ($row) {
                return [
                    'bulan'                  => (int) $row->bulan,
                    'tahun'                  => (int) $row->tahun,
                    'pdf_url'                => $row->pdf_url ?? '',
                    'status'                 => $row->status ?? 'draft',
                    'perlu_revisi'           => $row->status === 'draft' && (!empty($row->catatan_revisi) || !empty($row->dibuka_kembali_alasan)),
                    'pernah_dibuka_kembali'  => $row->arsip_count > 0,
                    'dibuka_kembali_oleh'    => $row->dibuka_kembali_oleh,
                    'dibuka_kembali_at'      => $row->dibuka_kembali_at
                                                ? \Carbon\Carbon::parse($row->dibuka_kembali_at)->translatedFormat('d M Y, H:i')
                                                : null,
                ];
            })
            ->toArray();

        return view('laporan-bulanan.index', compact('laporanList'));
    }

    public function buat(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
        ]);

        return redirect()->route('laporan-bendahara.index', [
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
        ]);
    }
}