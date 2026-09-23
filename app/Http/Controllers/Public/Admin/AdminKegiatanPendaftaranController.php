<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\KegiatanPendaftaran;
use App\Models\Anggota; 
use Illuminate\Validation\Rule; 
use Illuminate\Http\JsonResponse; 
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminKegiatanPendaftaranController extends Controller
{
    public function index(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->load('customFields');

        $pendaftar = $kegiatan->pendaftar()
            ->with('anggota')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('cari'), function ($q) use ($request) {
                $cari = $request->cari;
                $q->whereHas('anggota', function ($sub) use ($cari) {
                    $sub->where('nama', 'like', "%{$cari}%")
                        ->orWhere('nik', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('landing-page.admin.kegiatan.pendaftar', compact('kegiatan', 'pendaftar'));
    }

    public function tandaiHadir(Kegiatan $kegiatan, KegiatanPendaftaran $pendaftaran): RedirectResponse
    {
        abort_unless($pendaftaran->kegiatan_id === $kegiatan->id, 404);

        $pendaftaran->update([
            'status' => $pendaftaran->status === 'hadir' ? 'diterima' : 'hadir',
        ]);

        return back()->with('success', 'Status kehadiran diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan, KegiatanPendaftaran $pendaftaran): RedirectResponse
    {
        abort_unless($pendaftaran->kegiatan_id === $kegiatan->id, 404);

        $this->authorizePendaftarAccess($kegiatan);

        $pendaftaran->delete();

        return back()->with('success', 'Pendaftar berhasil dihapus.');
    }

    public function exportCsv(Kegiatan $kegiatan): StreamedResponse
    {
        $kegiatan->load('customFields');
        $customFields = $kegiatan->customFields;

        $filename = str($kegiatan->judul)->slug() . '-pendaftar.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($kegiatan, $customFields) {
            $out = fopen('php://output', 'w');

            $header = ['No', 'Nama', 'NIK', 'Departemen', 'Status', 'Tanggal Daftar'];
            foreach ($customFields as $field) {
                $header[] = $field->label;
            }
            fputcsv($out, $header);

            $kegiatan->pendaftar()->with('anggota')->orderBy('created_at')->chunk(200, function ($rows) use ($out, $customFields) {
                foreach ($rows as $i => $row) {
                    $line = [
                        $i + 1,
                        $row->anggota->nama ?? '-',
                        $row->anggota->nik ?? '-',
                        $row->anggota->departemen ?? '-',
                        $row->status,
                        $row->created_at->format('d-m-Y H:i'),
                    ];

                    foreach ($customFields as $field) {
                        $jawaban = $row->custom_values[$field->id] ?? '';
                        $line[] = is_array($jawaban) ? implode(', ', $jawaban) : $jawaban;
                    }

                    fputcsv($out, $line);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cariAnggota(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $sudahDaftarIds = $kegiatan->pendaftar()->pluck('anggota_id');

        $anggota = Anggota::query()
            ->where('is_active', true)
            ->whereNotIn('id', $sudahDaftarIds)
            ->where(function ($query) use ($q) {
                $query->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('nik_karyawan', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'nama_lengkap as nama', 'nik_karyawan', 'departemen']);

        return response()->json($anggota);
    }

    
    public function storeManual(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizePendaftarAccess($kegiatan);
        $kegiatan->load('customFields');

        $rules = [
            'anggota_id' => ['required', 'integer', Rule::exists('anggota', 'id')],
        ];

        foreach ($kegiatan->customFields as $field) {
            $key = "custom_fields.{$field->id}";
            $fieldRules = [$field->wajib ? 'required' : 'nullable'];
            $fieldRules[] = match ($field->tipe) {
                'number' => 'numeric',
                'checkbox' => 'boolean',
                'select' => Rule::in($field->opsi ?? []),
                default => 'string',
            };
            $rules[$key] = $fieldRules;
        }

        $validated = $request->validate($rules);

        $sudahDaftar = KegiatanPendaftaran::where('kegiatan_id', $kegiatan->id)
            ->where('anggota_id', $validated['anggota_id'])
            ->exists();

        if ($sudahDaftar) {
            return back()->with('error', 'Anggota ini sudah terdaftar di kegiatan ini.');
        }

        if ($kegiatan->kuota !== null && $kegiatan->kuota_terisi >= $kegiatan->kuota) {
            return back()->with('error', 'Kuota kegiatan ini sudah penuh.');
        }

        KegiatanPendaftaran::create([
            'kegiatan_id' => $kegiatan->id,
            'anggota_id' => $validated['anggota_id'],
            'status' => 'diterima',
            'custom_values' => $validated['custom_fields'] ?? [],
        ]);

        return back()->with('success', 'Pendaftar berhasil ditambahkan.');
    }

    private function authorizePendaftarAccess(Kegiatan $kegiatan): void
    {   
        $jabatan = session('anggota.jabatan_puk', '');

        abort_unless(
            strcasecmp((string) $jabatan, (string) $kegiatan->created_by) === 0
                || strcasecmp((string) $jabatan, 'sekretaris') === 0,
            403
        );
    }

    public function scanPage(Kegiatan $kegiatan): View
    {
        return view('landing-page.admin.kegiatan.scan', compact('kegiatan'));
    }

    public function scan(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string'],
        ]);

        $kodeRaw = trim($validated['kode']);
        // Buang separator (: - spasi) dan uppercase-kan, supaya UID NFC dari
        // Web NFC ("04:a3:b2:c1") dan dari reader USB ("04A3B2C1") tetap match.
        $kodeNormalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kodeRaw));

        $anggota = Anggota::where('nik_karyawan', $kodeRaw)
            ->orWhere('ulid', $kodeRaw)
            ->orWhereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(rfid_code, ':', ''), '-', ''), ' ', '')) = ?",
                [$kodeNormalized]
            )
            ->first();

        if (!$anggota) {
            return response()->json([
                'status' => 'tidak_dikenali',
                'message' => 'Kartu/kode tidak dikenali.',
            ], 404);
        }

        $pendaftaran = KegiatanPendaftaran::where('kegiatan_id', $kegiatan->id)
            ->where('anggota_id', $anggota->id)
            ->first();

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'belum_daftar',
                'message' => "{$anggota->nama_lengkap} belum terdaftar di kegiatan ini.",
                'nama' => $anggota->nama_lengkap,
            ], 404);
        }

        if ($pendaftaran->status === 'hadir') {
            return response()->json([
                'status' => 'sudah_hadir',
                'message' => "{$anggota->nama_lengkap} sudah check-in sebelumnya (jam {$pendaftaran->updated_at->format('H:i')}).",
                'nama' => $anggota->nama_lengkap,
            ]);
        }

        $pendaftaran->update(['status' => 'hadir']);
        $pendaftaran->load('anggota');

        return response()->json([
            'status' => 'berhasil',
            'message' => "Selamat datang, {$anggota->nama_lengkap}!",
            'nama' => $anggota->nama_lengkap,
            // Dikirim balik lengkap supaya HP yang barusan scan bisa langsung
            // nambah ke listnya sendiri (optimistic), tanpa nunggu polling berikutnya.
            'row' => $this->serializePendaftaran($pendaftaran, $kegiatan),
        ]);
    }

    public function daftarHadirJson(Kegiatan $kegiatan): JsonResponse
    {
        $kegiatan->load('customFields');

        $list = $kegiatan->pendaftar()
            ->with('anggota')
            ->where('status', 'hadir')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($row) => $this->serializePendaftaran($row, $kegiatan));

        return response()->json([
            'fields' => $kegiatan->customFields->pluck('label'),
            'list' => $list,
        ]);
    }

    /**
     * Bentuk satu baris "sudah hadir" — dipakai bareng oleh scan() (buat
     * optimistic update di HP yang scan) dan daftarHadirJson() (buat polling).
     */
    private function serializePendaftaran(KegiatanPendaftaran $row, Kegiatan $kegiatan): array
    {
        $custom = $kegiatan->customFields->map(function ($field) use ($row) {
            $jawaban = $row->custom_values[$field->id] ?? null;

            return [
                'label' => $field->label,
                'value' => $field->tipe === 'checkbox'
                    ? ($jawaban ? 'Ya' : 'Tidak')
                    : (is_array($jawaban) ? implode(', ', $jawaban) : ($jawaban ?: '-')),
            ];
        })->values();

        return [
            'id' => $row->id,
            'nama' => $row->anggota->nama_lengkap ?? '-',
            'nik' => $row->anggota->nik_karyawan ?? '-',
            'departemen' => $row->anggota->departemen ?? '-',
            'custom' => $custom,
            'tgl_hadir' => $row->updated_at->translatedFormat('d F Y, H:i'),
        ];
    }
}