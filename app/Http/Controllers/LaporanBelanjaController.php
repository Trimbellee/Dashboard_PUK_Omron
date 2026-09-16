<?php

namespace App\Http\Controllers;

use App\Models\BelanjaKoperasi;
use Illuminate\Http\Request;

class LaporanBelanjaController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);

        $laporanList = BelanjaKoperasi::selectRaw('bulan, tahun, SUM(biaya) as total')
                        ->groupBy('bulan','tahun')
                        ->orderBy('tahun','desc')
                        ->orderBy('bulan','desc')
                        ->get();

        $dataBelanja = BelanjaKoperasi::where('bulan',$bulan)
                        ->where('tahun',$tahun)
                        ->orderBy('bidang')
                        ->get();

        $isEdit = $dataBelanja->count() > 0;

        return view('laporan-belanja.index', compact('laporanList','dataBelanja','bulan','tahun','isEdit'));
    }
}
