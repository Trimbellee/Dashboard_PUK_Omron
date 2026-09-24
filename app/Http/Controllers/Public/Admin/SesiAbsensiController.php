<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Exports\SesiAbsensiExport;
use App\Models\Anggota;
use App\Models\SesiAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SesiAbsensiController extends Controller
{
    /**
     * Whitelist kolom tabel anggota yang boleh ditampilkan admin di daftar
     * hadir, plus label tampilannya. Dipakai bareng oleh validasi
     * store()/update(), view _form/show, dan SesiAbsensiExport supaya satu
     * sumber kebenaran.
     */
    public static function kolomOptions(): array
    {
        return [
            'nama_lengkap'    => 'Nama Lengkap',
            'nik_karyawan'    => 'NIK',
            'departemen'      => 'Departemen',
            'jabatan'         => 'Jabatan',
            'jenis_kelamin'   => 'Jenis Kelamin',
            'nomor_whatsapp'  => 'No. WhatsApp',
            'email'           => 'Email',
            'status_karyawan' => 'Status Karyawan',
        ];
    }

    public function index(): View
    {
        $sesiList = SesiAbsensi::withCount('entri')
            ->latest()
            ->paginate(15);

        return view('landing-page.admin.absensi.index', compact('sesiList'));
    }

    public function create(): View
    {
        return view('landing-page.admin.absensi.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $sesi = SesiAbsensi::create([
            'judul' => $validated['judul'],
            'lokasi' => $validated['lokasi'] ?? null,
            'kolom_ditampilkan' => $validated['kolom_ditampilkan'],
            'status' => 'buka',
            'dibuat_oleh' => session('anggota.nama'),
        ]);

        return redirect()
            ->route('admin.sesi-absensi.show', $sesi)
            ->with('success', 'Sesi absensi berhasil dibuat.');
    }

    public function show(SesiAbsensi $sesi): View
    {
        $kolomLabels = self::kolomOptions();

        return view('landing-page.admin.absensi.show', compact('sesi', 'kolomLabels'));
    }

    public function edit(SesiAbsensi $sesi): View
    {
        abort_if(! $sesi->isBuka(), 403, 'Sesi yang sudah ditutup tidak bisa diedit.');

        return view('landing-page.admin.absensi.edit', compact('sesi'));
    }

    public function update(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        abort_if(! $sesi->isBuka(), 403, 'Sesi yang sudah ditutup tidak bisa diedit.');

        $validated = $this->validated($request);

        $sesi->update([
            'judul' => $validated['judul'],
            'lokasi' => $validated['lokasi'] ?? null,
            'kolom_ditampilkan' => $validated['kolom_ditampilkan'],
        ]);

        return redirect()
            ->route('admin.sesi-absensi.show', $sesi)
            ->with('success', 'Sesi absensi berhasil diperbarui.');
    }

    public function destroy(SesiAbsensi $sesi): RedirectResponse
    {
        abort_if(! $sesi->isBuka(), 403, 'Sesi yang sudah ditutup tidak bisa dihapus.');

        $judul = $sesi->judul;
        $sesi->delete();

        return redirect()
            ->route('admin.sesi-absensi.index')
            ->with('success', "Sesi absensi \"{$judul}\" berhasil dihapus.");
    }

    /**
     * Dipanggil begitu kartu di-tap / kode di-scan di halaman show().
     */
    public function scan(Request $request, SesiAbsensi $sesi): JsonResponse
    {
        if (! $sesi->isBuka()) {
            return response()->json([
                'status' => 'sesi_tertutup',
                'message' => 'Sesi absensi ini sudah ditutup.',
            ], 422);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string'],
            'metode' => ['required', 'string', 'in:web,scanner,nfc'],
        ]);

        $kodeRaw = trim($validated['kode']);
        $kodeNormalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kodeRaw));

        $anggota = Anggota::where('nik_karyawan', $kodeRaw)
            ->orWhere('ulid', $kodeRaw)
            ->orWhereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(rfid_code, ':', ''), '-', ''), ' ', '')) = ?",
                [$kodeNormalized]
            )
            ->first();

        if (! $anggota) {
            return response()->json([
                'status' => 'tidak_dikenali',
                'message' => 'Kartu/kode tidak dikenali.',
            ], 404);
        }

        $existing = $sesi->entri()->where('anggota_id', $anggota->id)->first();

        if ($existing) {
            return response()->json([
                'status' => 'sudah_hadir',
                'message' => "{$anggota->nama_lengkap} sudah tercatat hadir (jam {$existing->created_at->format('H:i')}).",
                'nama' => $anggota->nama_lengkap,
            ]);
        }

        $snapshot = collect($sesi->kolom_ditampilkan)
            ->mapWithKeys(fn ($kolom) => [$kolom => $anggota->{$kolom} ?? null])
            ->toArray();

        $entri = $sesi->entri()->create([
            'anggota_id' => $anggota->id,
            'data' => $snapshot,
            'metode' => $validated['metode'],
        ]);

        return response()->json([
            'status' => 'berhasil',
            'message' => "Selamat datang, {$anggota->nama_lengkap}!",
            'nama' => $anggota->nama_lengkap,
            'row' => [
                'id' => $entri->id,
                'data' => $entri->data,
                'jam' => $entri->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Polling live list — dipanggil berkala oleh halaman show().
     */
    public function entries(SesiAbsensi $sesi): JsonResponse
    {
        $list = $sesi->entri()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($entri) => [
                'id' => $entri->id,
                'data' => $entri->data,
                'jam' => $entri->created_at->format('H:i'),
            ]);

        return response()->json([
            'total' => $list->count(),
            'list' => $list,
        ]);
    }

    public function tutup(SesiAbsensi $sesi): RedirectResponse
    {
        if ($sesi->isBuka()) {
            $sesi->update([
                'status' => 'tutup',
                'ditutup_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.sesi-absensi.show', $sesi)
            ->with('success', 'Sesi absensi berhasil ditutup.');
    }

    public function export(SesiAbsensi $sesi)
    {
        $namaFile = str($sesi->judul)->slug() . '-daftar-hadir.xlsx';

        return Excel::download(
            new SesiAbsensiExport($sesi),
            $namaFile
        );
    }

    /**
     * Validasi bersama untuk store() dan update() — supaya aturan validasi
     * tidak dobel-tulis dan selalu konsisten antara buat baru dan edit.
     */
    private function validated(Request $request): array
    {
        $allowedKolom = array_keys(self::kolomOptions());

        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kolom_ditampilkan' => ['required', 'array', 'min:1'],
            'kolom_ditampilkan.*' => ['string', 'in:' . implode(',', $allowedKolom)],
        ]);
    }
}