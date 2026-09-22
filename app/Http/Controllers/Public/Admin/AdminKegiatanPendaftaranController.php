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
}