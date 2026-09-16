<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KodeSurat;

class KodeSuratController extends Controller
{
  
    public function index()
    {
        return view('kodesurat');
    }

    public function generate(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $bulanRomawi = $this->toRoman((int)$request->bulan);

            $isSK = $request->bidang === 'SK';

            // Lock per tahun
            $max = KodeSurat::where('tahun', $request->tahun)
                ->lockForUpdate()
                ->max('nomor_urut');

            $next = $max ? $max + 1 : 1;
            $nomor = str_pad($next, 3, '0', STR_PAD_LEFT);

            // Format berbeda untuk SK
            if ($isSK) {
                $noSurat = "{$nomor}/PUK-OMI/SKep/{$bulanRomawi}/{$request->tahun}";
            } else {
                $noSurat = "{$nomor}/PUK-OMI/{$request->jenis}/{$request->bidang}/{$bulanRomawi}/{$request->tahun}";
            }

            $row = KodeSurat::create([
                'nomor_urut'   => $next,
                'no_surat'     => $noSurat,
                'jenis'        => $isSK ? 'SKep' : $request->jenis,
                'bidang'       => $request->bidang,
                'bulan'        => $request->bulan,
                'bulan_romawi' => $bulanRomawi,
                'tahun'        => $request->tahun,
                'keterangan'   => $request->keterangan,
            ]);

            return response()->json([
                'no_surat' => $row->no_surat
            ]);
        });
    }

    private function toRoman(int $num): string
    {
        $map = [
            'XII'=>12,'XI'=>11,'X'=>10,'IX'=>9,
            'VIII'=>8,'VII'=>7,'VI'=>6,
            'V'=>5,'IV'=>4,'III'=>3,'II'=>2,'I'=>1
        ];

        $res = '';
        foreach ($map as $r => $v) {
            while ($num >= $v) {
                $res .= $r;
                $num -= $v;
            }
        }
        return $res;
    }

    public function data()
    {
        return KodeSurat::orderBy('created_at')
            ->get()
            ->map(fn ($r) => [
                'no_surat'   => $r->no_surat,
                'keterangan' => $r->keterangan,
                'tanggal'    => $r->created_at->format('d/m/Y')
            ]);
    }


}
