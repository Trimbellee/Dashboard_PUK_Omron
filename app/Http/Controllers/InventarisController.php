<?php

namespace App\Http\Controllers;

use App\Models\InventarisBarang;
use App\Models\InventarisPeminjaman;
use App\Models\InventarisLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InventarisController extends Controller
{
    private function getUserId()
    {
        $userId = auth()->id();

        if (!$userId && session('user')) {
            $userId = session('user')['id'] ?? null;
        }

        return $userId;
    }

    /**
     * Cek apakah user memiliki akses penuh (Sekretaris atau Ketua)
     */
    private function hasFullAccess()
    {
        $user = session('user');
        $role = $user['role'] ?? '';
        return in_array($role, ['Ketua', 'Sekretaris']);
    }

    /**
     * Buat satu kode barang unik dari nama barang. Dipakai baik untuk
     * penambahan tunggal (kalau kode dikosongkan) maupun bulk (selalu).
     */
    private function generateKodeBarang(string $namaBarang): string
    {
        $prefix = strtoupper(Str::of($namaBarang)->squish()->substr(0, 3)->padRight(3, 'X'));

        do {
            $kode = $prefix . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (InventarisBarang::where('kode_barang', $kode)->exists());

        return $kode;
    }

    // Halaman utama inventaris
    public function index()
    {
        $barang = InventarisBarang::with('creator')->latest()->get();

        // 1 baris = 1 unit fisik, jadi statistik cukup hitung baris.
        $statistik = [
            'total' => InventarisBarang::count(),
            'tersedia' => InventarisBarang::where('status', 'tersedia')->count(),
            'dipinjam' => InventarisPeminjaman::where('status', 'aktif')->count(),
            'dispose' => InventarisBarang::where('status', 'dispose')->count(),
            'dijual' => InventarisBarang::where('status', 'dijual')->count(),
            'hilang' => InventarisBarang::where('status', 'hilang')->count(),
            'rusak' => InventarisBarang::where('kondisi', 'rusak')->count(),
            'diperbaiki' => InventarisBarang::where('kondisi', 'diperbaiki')->count(),
        ];

        return view('inventaris.index', compact('barang', 'statistik'));
    }

    // Form tambah barang
    public function create()
    {
        if (!$this->hasFullAccess()) {
            abort(403, 'Anda tidak memiliki akses untuk menambah barang.');
        }
        
        $kategori = ['Elektronik', 'Furniture', 'Alat Tulis', 'Perlengkapan', 'Lainnya'];
        return view('inventaris.create', compact('kategori'));
    }

    // Simpan barang baru — bisa membuat beberapa unit identik sekaligus,
    // tapi masing-masing selalu jadi baris & kode barang tersendiri.
    public function store(Request $request)
    {
        if (!$this->hasFullAccess()) {
            abort(403, 'Anda tidak memiliki akses untuk menambah barang.');
        }

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'kategori' => 'required|string|in:Elektronik,Furniture,Alat Tulis,Perlengkapan,Lainnya',
            'jumlah_unit' => 'required|integer|min:1|max:50',
            'kondisi' => 'required|in:baik,rusak,diperbaiki',
            'status' => 'required|in:tersedia,dispose,dijual,hilang',
            'tanggal_pembelian' => 'nullable|date',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kode_barang' => 'nullable|string|unique:inventaris_barang,kode_barang',
            'keterangan' => 'nullable|string'
        ]);

        $validator->after(function ($validator) use ($request) {
            if ((int) $request->jumlah_unit > 1 && $request->filled('kode_barang')) {
                $validator->errors()->add(
                    'kode_barang',
                    'Kosongkan kode barang kalau menambahkan lebih dari 1 unit — kode dibuat otomatis per unit.'
                );
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $userId = $this->getUserId();

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'User tidak terdeteksi, silakan login ulang')
                ->withInput();
        }

        $jumlahUnit = (int) $request->jumlah_unit;
        $dibuat = [];

        for ($i = 0; $i < $jumlahUnit; $i++) {
            $kode = ($jumlahUnit === 1 && $request->filled('kode_barang'))
                ? $request->kode_barang
                : $this->generateKodeBarang($request->nama_barang);

            $barang = InventarisBarang::create([
                'kode_barang' => $kode,
                'nama_barang' => $request->nama_barang,
                'type' => $request->type,
                'kategori' => $request->kategori,
                'jumlah' => 1,
                'kondisi' => $request->kondisi,
                'status' => $request->status,
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'harga_perolehan' => $request->harga_perolehan,
                'keterangan' => $request->keterangan,
                'created_by' => $userId
            ]);

            InventarisLog::create([
                'barang_id' => $barang->id,
                'user_id' => $userId,
                'aksi' => 'tambah',
                'detail' => $barang->toArray()
            ]);

            $dibuat[] = $kode;
        }

        $pesan = $jumlahUnit === 1
            ? 'Barang berhasil ditambahkan'
            : "{$jumlahUnit} unit berhasil ditambahkan (kode: " . implode(', ', $dibuat) . ')';

        return redirect()->route('inventaris.index')->with('success', $pesan);
    }

    // Detail barang
    public function show($id)
    {
        $barang = InventarisBarang::with(['creator', 'peminjaman.petugas', 'peminjaman.penerima'])->findOrFail($id);
        $riwayatPeminjaman = $barang->peminjaman()->orderBy('created_at', 'desc')->get();
        return view('inventaris.show', compact('barang', 'riwayatPeminjaman'));
    }

    // Form edit barang
    public function edit($id)
    {
        if (!$this->hasFullAccess()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit barang.');
        }
        
        $barang = InventarisBarang::findOrFail($id);
        $kategori = ['Elektronik', 'Furniture', 'Alat Tulis', 'Perlengkapan', 'Lainnya'];
        return view('inventaris.edit', compact('barang', 'kategori'));
    }

    // Update barang (satu unit — jumlah selalu 1, tidak bisa diedit)
    public function update(Request $request, $id)
    {
        if (!$this->hasFullAccess()) {
            abort(403, 'Anda tidak memiliki akses untuk mengupdate barang.');
        }

        $barang = InventarisBarang::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'kategori' => 'required|string|in:Elektronik,Furniture,Alat Tulis,Perlengkapan,Lainnya',
            'kondisi' => 'required|in:baik,rusak,diperbaiki',
            'status' => 'required|in:tersedia,dispose,dijual,hilang',
            'tanggal_pembelian' => 'nullable|date',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kode_barang' => 'nullable|string|unique:inventaris_barang,kode_barang,' . $id,
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $userId = $this->getUserId();

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'User tidak terdeteksi, silakan login ulang')
                ->withInput();
        }

        $oldData = $barang->toArray();

        $barang->update([
            'nama_barang' => $request->nama_barang,
            'type' => $request->type,
            'kategori' => $request->kategori,
            'kode_barang' => $request->kode_barang,
            'kondisi' => $request->kondisi,
            'status' => $request->status,
            'tanggal_pembelian' => $request->tanggal_pembelian,
            'harga_perolehan' => $request->harga_perolehan,
            'keterangan' => $request->keterangan,
        ]);

        InventarisLog::create([
            'barang_id' => $barang->id,
            'user_id' => $userId,
            'aksi' => 'edit',
            'detail' => ['old' => $oldData, 'new' => $request->all()]
        ]);

        return redirect()->route('inventaris.index')->with('success', 'Barang berhasil diupdate');
    }

    // Hapus barang (soft delete)
    public function destroy($id)
    {
        if (!$this->hasFullAccess()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus barang.');
        }
        
        $barang = InventarisBarang::findOrFail($id);

        if ($barang->peminjamanAktif()->exists()) {
            return redirect()->route('inventaris.index')
                ->with('error', 'Barang sedang dipinjam, tidak dapat dihapus');
        }

        $barang->delete();

        $userId = $this->getUserId();

        InventarisLog::create([
            'barang_id' => $barang->id,
            'user_id' => $userId,
            'aksi' => 'hapus',
            'detail' => $barang->toArray()
        ]);

        return redirect()->route('inventaris.index')->with('success', 'Barang berhasil dihapus');
    }

    // Form peminjaman — untuk SATU unit spesifik (barang_id)
    public function pinjamForm($id)
    {
        $barang = InventarisBarang::findOrFail($id);
        if (!$barang->isTersedia()) {
            return redirect()->route('inventaris.index')
                ->with('error', 'Barang tidak tersedia untuk dipinjam');
        }
        return view('inventaris.pinjam', compact('barang'));
    }

    // Proses peminjaman — meminjam 1 unit spesifik, tidak ada input kuantitas
    public function pinjam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barang_id' => 'required|exists:inventaris_barang,id',
            'peminjam_nama' => 'required|string|max:255',
            'peminjam_kontak' => 'nullable|string|max:255',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $barang = InventarisBarang::findOrFail($request->barang_id);

        if (!$barang->isTersedia()) {
            return redirect()->back()
                ->with('error', 'Barang tidak tersedia untuk dipinjam')
                ->withInput();
        }

        $userId = $this->getUserId();

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'User tidak terdeteksi, silakan login ulang')
                ->withInput();
        }

        $peminjaman = InventarisPeminjaman::create([
            'barang_id' => $barang->id,
            'jumlah_pinjam' => 1,
            'peminjam_nama' => $request->peminjam_nama,
            'peminjam_kontak' => $request->peminjam_kontak,
            'petugas_id' => $userId,
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'tanggal_kembali' => $request->tanggal_kembali,
            'status' => 'aktif',
            'keterangan' => $request->keterangan
        ]);

        

        InventarisLog::create([
            'barang_id' => $barang->id,
            'user_id' => $userId,
            'aksi' => 'pinjam',
            'detail' => $peminjaman->toArray()
        ]);

        return redirect()->route('inventaris.index')->with('success', 'Peminjaman berhasil diproses');
    }

    
    // Proses pengembalian — mencatat siapa yang menerima (user login)
    public function kembali(Request $request, $id)
    {
        $peminjaman = InventarisPeminjaman::with('barang')->findOrFail($id);

        if ($peminjaman->status !== 'aktif') {
            return redirect()->back()->with('error', 'Peminjaman ini sudah selesai');
        }

        $barang = $peminjaman->barang;
        $userId = $this->getUserId();

        if (!$userId) {
            return redirect()->back()->with('error', 'User tidak terdeteksi, silakan login ulang');
        }

        // Validasi kondisi barang saat dikembalikan
        $validator = Validator::make($request->all(), [
            'kondisi_kembali' => 'required|in:baik,rusak,perbaikan',
            'catatan_kembali' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update data peminjaman
        $peminjaman->update([
            'status' => 'selesai',
            'tanggal_kembali' => now(),
            'penerima_id' => $userId,
            'kondisi_kembali' => $request->kondisi_kembali,
            'catatan_kembali' => $request->catatan_kembali
        ]);

        $kondisiBerubah = $barang->kondisi !== $request->kondisi_kembali;
        if ($kondisiBerubah) {
            $barang->update(['kondisi' => $request->kondisi_kembali]);
        }

        InventarisLog::create([
            'barang_id' => $barang->id,
            'user_id' => $userId,
            'aksi' => 'kembali',
            'detail' => [
                'peminjaman' => $peminjaman->toArray(),
                'kondisi_sebelum' => $barang->getOriginal('kondisi'),
                'kondisi_sesudah' => $request->kondisi_kembali,
                'kondisi_berubah' => $kondisiBerubah
            ]
        ]);

        $pesan = 'Barang berhasil dikembalikan';
        if ($kondisiBerubah) {
            $pesan .= " dengan perubahan kondisi menjadi " . ucfirst($request->kondisi_kembali);
        }

        return redirect()->back()->with('success', $pesan);
    }

    // Tandai peminjaman sebagai hilang — barang tidak kembali sama sekali
    public function hilang(Request $request, $id)
    {
        $peminjaman = InventarisPeminjaman::with('barang')->findOrFail($id);

        if (!in_array($peminjaman->status, ['aktif', 'terlambat'])) {
            return redirect()->back()->with('error', 'Peminjaman ini sudah tidak aktif');
        }

        $userId = $this->getUserId();

        if (!$userId) {
            return redirect()->back()->with('error', 'User tidak terdeteksi, silakan login ulang');
        }

        $validator = Validator::make($request->all(), [
            'catatan_kembali' => 'required|string|max:500',
        ], [
            'catatan_kembali.required' => 'Jelaskan kronologi kehilangan barang ini.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $barang = $peminjaman->barang;

        $peminjaman->update([
            'status' => 'hilang',
            'tanggal_kembali' => now(),
            'penerima_id' => $userId,
            'catatan_kembali' => $request->catatan_kembali,
        ]);

        $barang->update(['status' => 'hilang']);

        InventarisLog::create([
            'barang_id' => $barang->id,
            'user_id' => $userId,
            'aksi' => 'hilang',
            'detail' => [
                'peminjaman' => $peminjaman->toArray(),
                'catatan' => $request->catatan_kembali,
            ]
        ]);

        return redirect()->back()->with('success', 'Barang ditandai hilang');
    }
}
