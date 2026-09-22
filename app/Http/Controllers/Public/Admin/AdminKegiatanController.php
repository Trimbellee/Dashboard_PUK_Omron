<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\KegiatanCustomField;
use App\Helpers\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminKegiatanController extends Controller
{
    public function index(): View
    {
        $kegiatan = Kegiatan::withCount([
                'pendaftar as pendaftar_diterima_count' => fn ($q) => $q->whereIn('status', ['diterima', 'hadir']),
            ])
            ->orderByDesc('tanggal_mulai')
            ->paginate(15);

        return view('landing-page.admin.kegiatan.index', compact('kegiatan'));
    }

    public function create(): View
    {
        return view('landing-page.admin.kegiatan.create', ['kegiatan' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request) {
            $kegiatan = Kegiatan::create([
                'judul' => $data['nama'],
                'slug' => $this->uniqueSlug($data['nama']),
                'jenis' => $data['jenis'],
                'tingkat_kategori' => $data['jenis'] === 'pendidikan' ? $data['tingkat_kategori'] : null,
                'tingkat_keterangan' => $this->resolveTingkatKeterangan($data),
                'deskripsi' => $data['deskripsi'] ?? null,
                'tanggal_mulai' => $data['tanggal'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null, 
                'batas_pendaftaran' => $data['batas_pendaftaran'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'kuota' => $data['kuota'] ?? null,
                'status' => 'dibuka', 
                'thumbnail' => $this->handleThumbnail($request),
                'created_by' => session('anggota.jabatan_puk'),
            ]);

            $this->syncCustomFields($kegiatan, $data['custom_fields'] ?? []);
        });

        return redirect()
            ->route('admin.kegiatan.index')
            ->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    public function edit(Kegiatan $kegiatan): View
    {
        $kegiatan->load('customFields');

        return view('landing-page.admin.kegiatan.edit', compact('kegiatan'));
    }

    public function update(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $data = $this->validated($request, $kegiatan);

        DB::transaction(function () use ($data, $request, $kegiatan) {
            $thumbnail = $this->handleThumbnail($request, $kegiatan);

            $kegiatan->update([
                'judul' => $data['nama'],
                'jenis' => $data['jenis'],
                'tingkat_kategori' => $data['jenis'] === 'pendidikan' ? $data['tingkat_kategori'] : null,
                'tingkat_keterangan' => $this->resolveTingkatKeterangan($data),
                'deskripsi' => $data['deskripsi'] ?? null,
                'tanggal_mulai' => $data['tanggal'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                'batas_pendaftaran' => $data['batas_pendaftaran'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'kuota' => $data['kuota'] ?? null,
                'thumbnail' => $thumbnail ?? $kegiatan->thumbnail,
            ]);

            $this->syncCustomFields($kegiatan, $data['custom_fields'] ?? []);
        });

        return redirect()
            ->route('admin.kegiatan.index')
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan): RedirectResponse
    { 
        $this->authorizeKegiatanAccess($kegiatan);

        if ($kegiatan->thumbnail) {
            Storage::disk('public')->delete($kegiatan->thumbnail);
        }

        $kegiatan->delete();

        return back()->with('success', 'Kegiatan berhasil dihapus.');
    }

    // Method baru — taruh dekat destroy()
    public function tutup(Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizeKegiatanAccess($kegiatan);

        abort_unless($kegiatan->status === 'dibuka', 422, 'Kegiatan ini tidak sedang dibuka.');

        $kegiatan->update(['status' => 'ditutup']);

        return back()->with('success', 'Pendaftaran kegiatan berhasil ditutup.');
    }

    public function bukaKembali(Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizeKegiatanAccess($kegiatan);

        abort_unless($kegiatan->status === 'ditutup', 422, 'Kegiatan ini tidak sedang ditutup.');

        $kegiatan->update(['status' => 'dibuka']);

        return back()->with('success', 'Pendaftaran kegiatan dibuka kembali.');
    }
    /**
     * Hanya pembuat kegiatan (berdasarkan jabatan_puk saat membuat) atau
     * Sekretaris yang boleh mengubah/menghapus kegiatan ini.
     */
    private function authorizeKegiatanAccess(Kegiatan $kegiatan): void
    {
        $jabatan = session('anggota.jabatan_puk');

        abort_unless(
            strcasecmp((string) $jabatan, (string) $kegiatan->created_by) === 0
                || strcasecmp((string) $jabatan, 'sekretaris') === 0,
            403
        );
    }

    private function validated(Request $request, ?Kegiatan $kegiatan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal'], 
            'batas_pendaftaran' => ['nullable', 'date', 'before_or_equal:tanggal'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kuota' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],

            'jenis' => ['required', 'in:pendidikan,seminar,aksi,lainnya'],
            'tingkat_kategori' => ['nullable', 'required_if:jenis,pendidikan', 'in:dasar,lanjutan,spesialis,lainnya'],
            'spesialis' => ['nullable', 'required_if:tingkat_kategori,spesialis', 'in:pkb,public_speaking,negosiasi,advokasi,k3,lainnya'],
            'spesialis_manual' => ['nullable', 'required_if:spesialis,lainnya', 'string', 'max:255'],
            'tingkat_manual' => ['nullable', 'required_if:tingkat_kategori,lainnya', 'string', 'max:255'],

            'thumbnail' => ['nullable', 'image', 'max:2048'],

            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.label' => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.tipe' => ['required_with:custom_fields', 'in:text,select,number,checkbox'],
            'custom_fields.*.opsi' => ['nullable', 'string'],
            'custom_fields.*.wajib' => ['nullable'],
        ]);
    }

    /**
     * Gabungkan spesialis/spesialis_manual/tingkat_manual jadi satu nilai
     * tingkat_keterangan, sesuai jenis & tingkat_kategori yang dipilih.
     * Field-field sumbernya tidak disimpan sebagai kolom sendiri di DB.
     */
    private function resolveTingkatKeterangan(array $data): ?string
    {
        if (($data['jenis'] ?? null) !== 'pendidikan') {
            return null;
        }

        return match ($data['tingkat_kategori'] ?? null) {
            'spesialis' => ($data['spesialis'] ?? null) === 'lainnya'
                ? ($data['spesialis_manual'] ?? null)
                : ($data['spesialis'] ?? null),
            'lainnya' => $data['tingkat_manual'] ?? null,
            default => null, // dasar / lanjutan -> tidak ada keterangan
        };
    }

    /**
     * Sinkronkan custom_fields ke tabel kegiatan_custom_field.
     *
     * Form ini mengirim custom_fields sebagai array native
     * (custom_fields[i][label], dst), tanpa id per baris — jadi strateginya
     * hapus semua field lama lalu buat ulang dari payload saat ini.
     *
     * Trade-off: kalau kegiatan ini sudah pernah punya pendaftar dan
     * jawabannya tersimpan di kegiatan_pendaftaran.custom_values (keyed by
     * kegiatan_custom_field.id lama), jawaban itu akan "nyasar" setelah edit
     * karena id field-nya berubah. Kalau itu jadi masalah nyata di kamu,
     * kabari — solusinya nambah hidden input custom_fields[i][id] di form
     * biar bisa diff seperti versi sebelumnya.
     */
    private function syncCustomFields(Kegiatan $kegiatan, array $customFields): void
    {
        $kegiatan->customFields()->delete();

        foreach (array_values($customFields) as $urutan => $field) {
            if (blank($field['label'] ?? null)) {
                continue;
            }

            $tipe = in_array($field['tipe'] ?? null, ['text', 'select', 'number', 'checkbox'], true)
                ? $field['tipe']
                : 'text';

            KegiatanCustomField::create([
                'kegiatan_id' => $kegiatan->id,
                'label' => $field['label'],
                'tipe' => $tipe,
                'opsi' => $tipe === 'select'
                    ? array_values(array_filter(array_map('trim', explode(',', $field['opsi'] ?? ''))))
                    : null,
                'wajib' => filter_var($field['wajib'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'urutan' => $urutan,
            ]);
        }
    }

    private function handleThumbnail(Request $request, ?Kegiatan $kegiatan = null): ?string
    {
        if (!$request->hasFile('thumbnail')) {
            return null;
        }

        if ($kegiatan?->thumbnail) {
            Storage::disk('public')->delete($kegiatan->thumbnail);
        }

        return ImageOptimizer::storeAsWebp(
            file: $request->file('thumbnail'),
            directory: 'kegiatan',
            quality: 70,
            maxWidth: 1200, // thumbnail kartu, nggak perlu setinggi 1600 kayak gambar berita
            titleForFilename: $request->input('nama'),
        );
    }

    private function uniqueSlug(string $judul): string
    {
        $base = Str::slug($judul);
        $slug = $base;
        $i = 1;

        while (Kegiatan::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . $i++;
        }

        return $slug;
    }
}