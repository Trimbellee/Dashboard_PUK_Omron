<?php

namespace App\Http\Controllers;

use App\Models\InsentifMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsentifController extends Controller
{
    private function authorizeRole(): void
    {
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris', 'Ketua'])) {
            abort(403, 'Tidak diizinkan mengakses halaman ini');
        }
    }

    public function index()
    {
        $this->authorizeRole();

        $items = InsentifMaster::orderBy('urutan')->get();

        return view('insentif.index', compact('items'));
    }

    public function sync(Request $request)
    {
        // hanya Bendahara & Sekretaris yang boleh mengubah
        if (!in_array(session('user')['role'] ?? null, ['Bendahara', 'Sekretaris'])) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403);
        }

        $data = $request->validate([
            'items'             => 'present|array',
            'items.*.id'        => 'nullable|integer',
            'items.*.tujuan'    => 'required|string|max:255',
            'items.*.keperluan' => 'required|string|max:255',
            'items.*.nominal'   => 'required|integer|min:0',
            'items.*.aktif'     => 'required|boolean',
        ]);

        DB::transaction(function () use ($data) {
            $keepIds = collect($data['items'])->pluck('id')->filter()->all();

            // baris yang dihapus di layar ikut dihapus dari master
            // (aman: laporan lama punya snapshot sendiri)
            InsentifMaster::whereNotIn('id', $keepIds)->delete();

            foreach ($data['items'] as $i => $it) {
                $payload = [
                    'tujuan'    => $it['tujuan'],
                    'keperluan' => $it['keperluan'],
                    'nominal'   => $it['nominal'],
                    'aktif'     => $it['aktif'],
                    'urutan'    => $i + 1,
                ];

                if (!empty($it['id'])) {
                    InsentifMaster::where('id', $it['id'])->update($payload);
                } else {
                    InsentifMaster::create($payload);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan insentif berhasil disimpan',
        ]);
    }
}