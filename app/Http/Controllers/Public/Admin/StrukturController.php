<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\StrukturOrganisasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StrukturController extends Controller
{
    /**
     * Tampilkan tree struktur organisasi di admin.
     */
    public function index(): View
    {
        $tree = $this->buildTree(null, 0);
        $flatList = $this->buildFlatList($tree);
        
        return view('landing-page.admin.struktur.index', compact('tree', 'flatList'));
    }

    /**
     * Bangun tree secara rekursif.
     */
    private function buildTree($parentId, $level)
    {
        $query = StrukturOrganisasi::orderBy('urutan');
        
        if (is_null($parentId)) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $nodes = $query->get();
        $result = [];

        foreach ($nodes as $node) {
            $item = $node->toArray();
            $item['level'] = $level;
            $item['children'] = collect($this->buildTree($node->id, $level + 1));
            $item['anggota'] = $node->anggota;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Build flat list untuk tabel view
     */
    private function buildFlatList($tree, $level = 0)
    {
        $result = [];
        foreach ($tree as $node) {
            $node['level'] = $level;
            $node['children_count'] = $node['children']->count();
            $result[] = $node;
            
            if ($node['children']->isNotEmpty()) {
                $result = array_merge($result, $this->buildFlatList($node['children'], $level + 1));
            }
        }
        return $result;
    }

    /**
     * Tampilkan form tambah jabatan.
     */
    public function create(Request $request): View
    {
        $struktur = null;
        $parentId = $request->query('parent_id');
        $jabatanOptions = StrukturOrganisasi::withTrashed()->orderBy('jabatan')->get();

        return view('landing-page.admin.struktur.form', compact('struktur', 'jabatanOptions', 'parentId'));
    }

    /**
     * Simpan jabatan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jabatan'   => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:struktur_organisasi,id'],
            'anggota_id'=> ['nullable', 'exists:anggota,id'],
        ]);

        $parentId = !empty($validated['parent_id']) ? $validated['parent_id'] : null;

        $maxUrutan = StrukturOrganisasi::where('parent_id', $parentId)->max('urutan') ?? 0;
        $validated['urutan'] = $maxUrutan + 1;
        $validated['parent_id'] = $parentId;

        StrukturOrganisasi::create($validated);

        return redirect()->route('admin.struktur.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit jabatan.
     */
    public function edit($id): View
    {
        $struktur = StrukturOrganisasi::findOrFail($id);
        $jabatanOptions = StrukturOrganisasi::where('id', '!=', $id)->orderBy('jabatan')->get();
        return view('landing-page.admin.struktur.form', compact('struktur', 'jabatanOptions'));
    }

    /**
     * Update jabatan.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $struktur = StrukturOrganisasi::findOrFail($id);

        $validated = $request->validate([
            'jabatan'   => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:struktur_organisasi,id'],
            'anggota_id'=> ['nullable', 'exists:anggota,id'],
        ]);

        if ($validated['parent_id'] == $id) {
            return back()->withErrors(['parent_id' => 'Tidak bisa menjadikan diri sendiri sebagai parent.']);
        }

        $newParentId = !empty($validated['parent_id']) ? $validated['parent_id'] : null;

        if ($newParentId != $struktur->parent_id) {
            StrukturOrganisasi::where('parent_id', $struktur->parent_id)
                ->where('urutan', '>', $struktur->urutan)
                ->decrement('urutan');

            $newMax = StrukturOrganisasi::where('parent_id', $newParentId)->max('urutan') ?? 0;
            $validated['urutan'] = $newMax + 1;
        }

        $validated['parent_id'] = $newParentId;
        $struktur->update($validated);

        return redirect()->route('admin.struktur.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Hapus jabatan beserta semua anaknya.
     */
    public function destroy($id): RedirectResponse
    {
        $struktur = StrukturOrganisasi::findOrFail($id);
        $struktur->deleteWithChildren();

        return redirect()->route('admin.struktur.index')
            ->with('success', 'Jabatan dan semua sub-jabatan berhasil dihapus.');
    }

    /**
     * Naikkan urutan.
     */
    public function moveUp($id): RedirectResponse
    {
        $struktur = StrukturOrganisasi::findOrFail($id);

        $previous = StrukturOrganisasi::where('parent_id', $struktur->parent_id)
            ->where('urutan', '<', $struktur->urutan)
            ->orderBy('urutan', 'desc')
            ->first();

        if ($previous) {
            $temp = $struktur->urutan;
            $struktur->urutan = $previous->urutan;
            $previous->urutan = $temp;
            $struktur->save();
            $previous->save();
        }

        return redirect()->route('admin.struktur.index')
            ->with('success', 'Urutan berhasil dinaikkan.');
    }

    /**
     * Turunkan urutan.
     */
    public function moveDown($id): RedirectResponse
    {
        $struktur = StrukturOrganisasi::findOrFail($id);

        $next = StrukturOrganisasi::where('parent_id', $struktur->parent_id)
            ->where('urutan', '>', $struktur->urutan)
            ->orderBy('urutan', 'asc')
            ->first();

        if ($next) {
            $temp = $struktur->urutan;
            $struktur->urutan = $next->urutan;
            $next->urutan = $temp;
            $struktur->save();
            $next->save();
        }

        return redirect()->route('admin.struktur.index')
            ->with('success', 'Urutan berhasil diturunkan.');
    }

    /**
     * AJAX: Ambil anggota berdasarkan ID (hanya yang aktif).
     */
    public function anggotaById(Request $request)
    {
        $id = $request->query('id');
        $anggota = Anggota::where('is_active', true)->find($id);
        if (!$anggota) {
            return response()->json(null);
        }
        return response()->json([
            'id'            => $anggota->id,
            'nama_lengkap'  => $anggota->nama_lengkap,
            'nik_karyawan'  => $anggota->nik_karyawan,
            'departemen'    => $anggota->departemen,
            'jabatan'       => $anggota->jabatan,
            'nomor_whatsapp'=> $anggota->nomor_whatsapp,
            'email'         => $anggota->email,
            'foto_url'      => $anggota->foto_url,
        ]);
    }

    /**
     * AJAX: Cari anggota berdasarkan NIK atau nama (hanya yang aktif).
     */
    public function anggotaByNik(Request $request)
    {
        $nik = $request->query('nik');
        if (strlen($nik) < 2) {
            return response()->json([]);
        }
        $anggota = Anggota::where('is_active', true)
            ->where(function ($query) use ($nik) {
                $query->where('nik_karyawan', 'LIKE', "%{$nik}%")
                      ->orWhere('nama_lengkap', 'LIKE', "%{$nik}%");
            })
            ->limit(10)
            ->get();

        return response()->json($anggota->map(function ($item) {
            return [
                'id'            => $item->id,
                'nama_lengkap'  => $item->nama_lengkap,
                'nik_karyawan'  => $item->nik_karyawan,
                'departemen'    => $item->departemen,
                'jabatan'       => $item->jabatan,
                'nomor_whatsapp'=> $item->nomor_whatsapp,
                'email'         => $item->email,
                'foto_url'      => $item->foto_url,
            ];
        }));
    }
}