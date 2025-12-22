<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AppsScriptService;

class KodeSuratController extends Controller
{
    protected $api;

    public function __construct(AppsScriptService $api)
    {
        $this->api = $api;
    }

    public function index()
    {
        return view('kodesurat');
    }

    public function generate(Request $request)
    {
        $res = $this->api->request('post', [
            'action'     => 'generateKodeSurat',
            'jenis'      => $request->jenis,
            'bidang'     => $request->bidang,
            'bulan'      => $request->bulan,
            'tahun'      => $request->tahun,
            'keterangan' => $request->keterangan,
        ], true);

        if (!$res['success']) {
            return response()->json(['success' => false], 500);
        }

        return response()->json($res['data']);
    }

    public function data()
    {
        $res = $this->api->request('get', [
            'action' => 'listKodeSurat'
        ]);

        if (!$res['success']) {
            return response()->json([]);
        }

        return response()->json($res['data']);
    }

}
