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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\QueryException;
use App\Helpers\RfidHelper;


class SesiAbsensiController extends Controller
{

    /** Radius akurasi GPS maksimum yang dianggap valid (meter). */
    private const AKURASI_MAKS_METER = 500;


    /**
     * Whitelist kolom tabel anggota yang boleh ditampilkan admin di daftar
     * hadir, plus label tampilannya. Dipakai bareng oleh validasi
     * store()/update(), view _form/show, dan SesiAbsensiExport supaya satu
     * sumber kebenaran.
     */
    public static function kolomOptions(): array
    {
        $exclude = [
            'id',
            'ulid',
            'rfid_code',
            'rfid_paired_at',
            'jenis_identitas',
            'nomor_identitas',
            'tempat_lahir',
            'tanggal_lahir',
            'provinsi_domisili',
            'kota_domisili',
            'kecamatan_domisili',
            'kelurahan_domisili',
            'alamat_domisili',
            'provinsi_ktp',
            'kota_ktp',
            'kecamatan_ktp',
            'kelurahan_ktp',
            'alamat_ktp',
            'pendidikan_terakhir',
            'tanggal_mulai_bekerja',
            'tanggal_masuk_anggota',
            'tanggal_keluar_anggota',
            'is_active',
            'last_imported_at',
            'foto',
            'password',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        return collect(Schema::getColumnListing('anggota'))
            ->reject(fn ($column) => in_array($column, $exclude))
            ->mapWithKeys(function ($column) {
                return [
                    $column => Str::of($column)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),
                ];
            })
            ->toArray();
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
        $bisaKelola = $this->bisaKelolaSesi($sesi);

        return view('landing-page.admin.absensi.show', compact('sesi', 'kolomLabels', 'bisaKelola'));
    }

    public function edit(SesiAbsensi $sesi): View
    {
        abort_if(! $sesi->isBuka(), 403, 'Sesi yang sudah ditutup tidak bisa diedit.');
        abort_if(! $this->bisaKelolaSesi($sesi), 403, 'Hanya pembuat sesi atau Sekretaris yang boleh mengedit sesi ini.');

        return view('landing-page.admin.absensi.edit', compact('sesi'));
    }

    public function update(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        abort_if(! $sesi->isBuka(), 403, 'Sesi yang sudah ditutup tidak bisa diedit.');
        abort_if(! $this->bisaKelolaSesi($sesi), 403, 'Hanya pembuat sesi atau Sekretaris yang boleh mengedit sesi ini.');

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
        abort_if(! $this->bisaKelolaSesi($sesi), 403, 'Hanya pembuat sesi atau Sekretaris yang boleh menghapus sesi ini.');

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

        $isMobile = (bool) preg_match('/Mobi|Android|iPhone|iPad|iPod/i', (string) $request->userAgent());

        if ($isMobile && (! $request->filled('lat') || ! $request->filled('lng'))) {
            return response()->json([
                'status' => 'gps_tidak_aktif',
                'message' => 'Absen gagal: lokasi (GPS) tidak terdeteksi.',
            ], 422);
        }

        $akurasiBuruk = $request->filled('akurasi') && (float) $request->input('akurasi') > self::AKURASI_MAKS_METER;

        // HP wajib akurat karena GPS-nya wajib. PC boleh lanjut, cuma ditandai.
        if ($isMobile && $akurasiBuruk) {
            return response()->json([
                'status' => 'gps_tidak_akurat',
                'message' => 'Absen gagal: sinyal GPS kurang akurat (radius > ' . self::AKURASI_MAKS_METER . 'm). Coba di tempat terbuka.',
            ], 422);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string'],
            'metode' => ['required', 'string', 'in:web,scanner,nfc'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric'],
        ]);

        $kodeRaw = trim($validated['kode']);
        $kodeNormalized = RfidHelper::normalize($kodeRaw);

        $anggota = Anggota::where('nik_karyawan', $kodeRaw)
        ->orWhere('ulid', $kodeRaw)
        ->orWhere('rfid_code', $kodeNormalized)
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

        $snapshot['_lat'] = $validated['lat'] ?? null;
        $snapshot['_lng'] = $validated['lng'] ?? null;
        $snapshot['_akurasi'] = $validated['akurasi'] ?? null;
        $snapshot['_akurasi_buruk'] = $akurasiBuruk;
        $snapshot['_lokasi_nama'] = $this->namaLokasi($snapshot['_lat'], $snapshot['_lng']);

        try {
            $entri = $sesi->entri()->create([
                'anggota_id' => $anggota->id,
                'data' => $snapshot,
                'metode' => $validated['metode'],
            ]);
        } catch (QueryException $e) {
            // Kode 23000 = duplicate/unique constraint violation (MySQL & Postgres)
            if ($e->getCode() === '23000') {
                $existing = $sesi->entri()->where('anggota_id', $anggota->id)->first();

                return response()->json([
                    'status' => 'sudah_hadir',
                    'message' => "{$anggota->nama_lengkap} sudah tercatat hadir" .
                        ($existing ? " (jam {$existing->created_at->format('H:i')})." : '.'),
                    'nama' => $anggota->nama_lengkap,
                ]);
            }

            throw $e;
        }

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
    public function entries(Request $request, SesiAbsensi $sesi): JsonResponse
    {
        $sejakId = (int) $request->query('sejak_id', 0);

        $query = $sesi->entri()->orderByDesc('created_at');

        if ($sejakId > 0) {
            // Hanya entri yang lebih baru dari id terakhir yang sudah dimiliki frontend
            $query->where('id', '>', $sejakId);
        }

        $list = $query->get()->map(fn ($entri) => [
            'id' => $entri->id,
            'data' => $entri->data,
            'jam' => $entri->created_at->format('H:i'),
        ]);

        return response()->json([
            'total' => $sesi->entri()->count(), // total keseluruhan, bukan cuma yang baru
            'list' => $list,
            'terakhir_id' => $sesi->entri()->max('id') ?? $sejakId,
        ]);
    }

    public function tutup(SesiAbsensi $sesi): RedirectResponse
    {
        abort_if(! $this->bisaKelolaSesi($sesi), 403, 'Hanya pembuat sesi atau Sekretaris yang boleh menutup sesi ini.');

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

    public function buka(SesiAbsensi $sesi): RedirectResponse
    {
        abort_if(! $this->bisaKelolaSesi($sesi), 403, 'Hanya pembuat sesi atau Sekretaris yang boleh membuka kembali sesi ini.');

        if (! $sesi->isBuka()) {
            $sesi->update([
                'status' => 'buka',
                'ditutup_at' => null,
            ]);
        }

        return redirect()
            ->route('admin.sesi-absensi.show', $sesi)
            ->with('success', 'Sesi absensi berhasil dibuka kembali.');
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

    /**
     * Hanya pembuat sesi (dibuat_oleh) atau Sekretaris yang boleh
     * mengelola sesi ini: buka/tutup, edit, hapus.
     */
    private function bisaKelolaSesi(SesiAbsensi $sesi): bool
    {
        $namaSaya = session('anggota.nama');
        $jabatanSaya = session('anggota.jabatan');

        return $namaSaya === $sesi->dibuat_oleh || $jabatanSaya === 'Sekretaris';
    }

    /**
     * Ubah koordinat menjadi nama tempat. Gagal diam-diam (return null)
     * supaya absensi tetap tercatat meski layanan geocoding bermasalah.
     */
    private function namaLokasi(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        try {
            $res = Http::withHeaders([
                    'User-Agent' => config('app.name', 'app') . '/1.0',
                ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'accept-language' => 'id',
                ]);

            return $res->successful() ? Arr::get($res->json(), 'display_name') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}