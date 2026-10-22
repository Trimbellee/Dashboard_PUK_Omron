<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Admin\Concerns\HandlesApprovalWorkflow;
use App\Helpers\MediaHelper;
use App\Helpers\PermissionHelper;
use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Str;

class GalleryAdminController extends Controller
{
    use HandlesApprovalWorkflow;

    public function index(Request $request): View
    {
        $statBase = Gallery::query();
    
        $stats = [
            'draft'     => (clone $statBase)->whereIn('status', ['draft', 'revisi'])->count(),
            'menunggu'  => (clone $statBase)->whereIn('status', ['submitted_bidang5', 'submitted_sekretaris'])->count(),
            'published' => (clone $statBase)->where('status', 'published')->count(),
        ];
    
        $filter = $request->string('filter')->toString();
        $filterStatuses = match ($filter) {
            'draft'     => ['draft', 'revisi'],
            'menunggu'  => ['submitted_bidang5', 'submitted_sekretaris'],
            'published' => ['published'],
            default     => null,
        };
    
        // ?kegiatan=NAMA  -> tampilkan foto dalam satu album ("__none" = tanpa kegiatan)
        $kegiatanAktif = $request->query('kegiatan');
        $kegiatanAktif = (is_string($kegiatanAktif) && $kegiatanAktif !== '') ? $kegiatanAktif : null;
    
        // Default: daftar album. ?tampilan=foto atau ?kegiatan=... -> grid foto
        $mode = ($kegiatanAktif !== null || $request->query('tampilan') === 'foto') ? 'foto' : 'album';
    
        $data = [
            'stats'          => $stats,
            'activeFilter'   => $filter ?: 'semua',
            'mode'           => $mode,
            'kegiatanAktif'  => $kegiatanAktif,
            'albums'         => null,
            'covers'         => [],
            'tanpaKegiatan'  => null,
            'albumInfo'      => null,
            'galeriList'     => null,
        ];
    
        if ($mode === 'album') {
            $albums = Gallery::query()
                ->whereNotNull('kegiatan')
                ->where('kegiatan', '!=', '')
                ->select(
                    'kegiatan',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(status IN ('draft','revisi')) as draft"),
                    DB::raw("SUM(status IN ('submitted_bidang5','submitted_sekretaris')) as menunggu"),
                    DB::raw("SUM(status = 'published') as published"),
                    DB::raw('MIN(id) as cover_id'),
                    DB::raw('MAX(created_at) as terbaru')
                )
                ->groupBy('kegiatan')
                // nama filter (draft/menunggu/published) sama dengan alias kolom di atas
                ->when($filterStatuses, fn ($q) => $q->having($filter, '>', 0))
                ->orderByDesc('terbaru')
                ->paginate(12)
                ->withQueryString();
    
            $data['albums'] = $albums;
            $data['covers'] = Gallery::whereIn('id', $albums->pluck('cover_id'))->pluck('thumbnail_url', 'id');
    
            // Media yang belum punya kegiatan tetap bisa diakses lewat kartu khusus
            $tanpa = Gallery::query()
                ->where(fn ($w) => $w->whereNull('kegiatan')->orWhere('kegiatan', ''))
                ->when($filterStatuses, fn ($q) => $q->whereIn('status', $filterStatuses));
    
            $totalTanpa = (clone $tanpa)->count();
            if ($totalTanpa > 0) {
                $data['tanpaKegiatan'] = [
                    'total' => $totalTanpa,
                    'cover' => (clone $tanpa)->orderBy('id')->value('thumbnail_url'),
                ];
            }
        } else {
            $query = $kegiatanAktif !== null
                ? $this->albumScope($kegiatanAktif)->orderBy('id')   // urutan sesuai impor
                : Gallery::query()->latest();
    
            $query->when($filterStatuses, fn ($q) => $q->whereIn('status', $filterStatuses));
    
            $data['galeriList'] = $query->paginate(20)->withQueryString();
    
            if ($kegiatanAktif !== null) {
                $data['albumInfo'] = $this->albumInfo($kegiatanAktif);
            }
        }
    
        return view('landing-page.admin.galeri.index', $data);
    }
    

    public function create(): View
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);

        // Saran nama kegiatan yang sudah ada (datalist) supaya album tidak terpecah karena salah ketik
        $kegiatanList = Gallery::query()
            ->whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->distinct()
            ->orderBy('kegiatan')
            ->pluck('kegiatan');

        return view('landing-page.admin.galeri.form', compact('kegiatanList'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);

        $validated = $request->validate([
            'judul'       => ['nullable', 'string', 'max:55'],
            'kegiatan'    => ['nullable', 'string', 'max:255'],
            'kategori'    => ['nullable', 'string', 'in:' . implode(',', array_keys(Gallery::KATEGORI_LIST))],
            'url'         => ['required', 'url', 'max:2000'],
            'type'        => ['required', 'in:image,video'],
            'thumbnail'   => ['nullable', 'url', 'max:2000'],
            // Unggah gambar sendiri (otomatis dikecilkan); prioritas di atas thumbnail otomatis/URL
            'thumbnail_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Proses URL dengan MediaHelper (YouTube, Google Drive, TikTok)
        $media = MediaHelper::processUrl($validated['url']);
        if (!$media) {
            return back()
                ->withInput()
                ->withErrors(['url' => 'URL tidak valid. Hanya mendukung YouTube, Google Drive, dan TikTok.']);
        }

        // Cegah media dobel (sumber + ID yang sama)
        $duplikat = Gallery::where('source', $media['source'])
            ->where('external_id', $media['external_id'])
            ->exists();

        if ($duplikat) {
            return back()
                ->withInput()
                ->withErrors(['url' => 'Media ini sudah pernah ditambahkan.']);
        }

        // Override type dari pilihan admin; TikTok selalu video
        $media['type'] = $media['source'] === 'tiktok'
            ? 'video'
            : $validated['type'];

        // Thumbnail manual (jika diisi admin); URL CDN TikTok tidak pernah disimpan mentah
        if (!empty($validated['thumbnail'])) {
            $manual = $this->resolveManualThumbnail(
                $validated['thumbnail'],
                $media['source'],
                $media['external_id'],
                (string) ($media['thumbnail_url'] ?? '')
            );
            if ($manual !== '') {
                $media['thumbnail_url'] = $manual;
            }
        }

        // Gambar yang diunggah admin menimpa semua sumber thumbnail lain
        if ($request->hasFile('thumbnail_file')) {
            $uploaded = MediaHelper::storeUploadedThumbnail($request->file('thumbnail_file'));
            if ($uploaded === '') {
                return back()
                    ->withInput()
                    ->withErrors(['thumbnail_file' => 'Gambar tidak bisa diproses. Coba format JPG atau PNG.']);
            }
            // Hasil unduhan otomatis (jika ada) tidak terpakai lagi -> hapus agar tidak jadi sampah
            $this->deleteLocalThumbnail($media['thumbnail_url'] ?? null);
            $media['thumbnail_url'] = $uploaded;
        }

        // Jika thumbnail_url masih kosong (Google Drive / TikTok gagal ambil), set placeholder
        if (empty($media['thumbnail_url'])) {
            $media['thumbnail_url'] = asset('images/placeholder-media.png');
        }

        Gallery::create([
            'judul'         => $validated['judul'] ?? null,
            'kegiatan'      => self::normalizeKegiatan($validated['kegiatan'] ?? null),
            'kategori'      => $validated['kategori'] ?? null,
            'type'          => $media['type'],
            'source'        => $media['source'],
            'external_id'   => $media['external_id'],
            'thumbnail_url' => $media['thumbnail_url'],
            'embed_url'     => $media['embed_url'],
            'status'        => 'draft',
            'created_by'    => session('anggota.id'),
        ]);

        return redirect()
            ->route('admin.galeri.index')
            ->with('success', 'Media berhasil ditambahkan sebagai draft.');
    }

    public function destroy(Gallery $foto): RedirectResponse
    {
        $userId = session('anggota.id');

        $isOwnerDraft = $foto->created_by === $userId
            && in_array($foto->status, ['draft', 'revisi'], true);

        $isApproverDeletingPublished = PermissionHelper::can('approve_gallery')
            && $foto->status === 'published';

        abort_unless(
            $isOwnerDraft || $isApproverDeletingPublished,
            403,
            'Anda tidak berhak menghapus media ini.'
        );

        // Hapus file thumbnail lokal (TikTok) agar tidak jadi sampah di disk
        $this->deleteLocalThumbnail($foto->thumbnail_url);

        $foto->delete();

        return back()->with('success', 'Media berhasil dihapus.');
    }

    public function submit(Gallery $foto): RedirectResponse
    {
        $this->authorizeOwner($foto);
        $this->submitForApproval($foto, session('anggota.jabatan_puk'), 'gallery');
        return back()->with('success', 'Media dikirim untuk direview.');
    }

    public function reviewBidang5(Request $request, Gallery $foto): RedirectResponse
    {
        abort_unless(PermissionHelper::can('review_gallery'), 403);

        $approve = $request->boolean('approve');
        if (!$approve) {
            $request->validate(['catatan_revisi' => ['required', 'string', 'max:1000']]);
        }

        // FIX: tambahkan null untuk $editedData, baru 'gallery' untuk $type
        $this->reviewByBidang5($foto, $approve, $request->catatan_revisi, null, 'gallery');

        return back()->with('success', $approve
            ? 'Media diteruskan ke Sekretaris untuk approval final.'
            : 'Media dikembalikan untuk direvisi.');
    }

    public function approveSekretaris(Request $request, Gallery $foto): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_gallery'), 403);

        $approve = $request->boolean('approve');
        if (!$approve) {
            $request->validate(['catatan_revisi' => ['required', 'string', 'max:1000']]);
        }

        $this->approveBySekretaris($foto, $approve, $request->catatan_revisi, 'gallery');

        return back()->with('success', $approve
            ? 'Media disetujui dan dipublikasikan.'
            : 'Media dikembalikan untuk direvisi.');
    }

    private function authorizeOwner(Gallery $foto): void
    {
        $hasAccess = $foto->created_by === session('anggota.id')
            || PermissionHelper::can('review_gallery')
            || PermissionHelper::can('approve_gallery');

        abort_unless($hasAccess, 403, 'Anda tidak berhak mengelola media ini.');
    }

    // ------------------------------------------------------------------
    //  EDIT & UPDATE
    // ------------------------------------------------------------------

    public function edit(Gallery $foto): View
    {
        abort_unless($this->canEdit($foto), 403, 'Anda tidak berhak mengedit media ini.');

        return view('landing-page.admin.galeri.edit', [
            'foto'         => $foto,
            'kegiatanList' => $this->daftarKegiatan(),
        ]);
    }

    public function update(Request $request, Gallery $foto): RedirectResponse
    {
        abort_unless($this->canEdit($foto), 403, 'Anda tidak berhak mengedit media ini.');

        $data = $request->validate([
            'judul'           => ['nullable', 'string', 'max:55'],
            'kegiatan'        => ['nullable', 'string', 'max:255'],
            'kategori'        => ['nullable', 'string', 'in:' . implode(',', array_keys(Gallery::KATEGORI_LIST))],
            'type'            => ['nullable', 'in:image,video'],
            'thumbnail'       => ['nullable', 'url', 'max:2000'],
            'reset_thumbnail' => ['nullable', 'boolean'],
            'thumbnail_file'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            // Opsional (khusus TikTok): tempel ulang link video bila ambil-ulang otomatis gagal
            'url_ulang'       => ['nullable', 'url', 'max:2000'],
        ]);

        // Tipe hanya bisa diubah untuk Google Drive; YouTube dan TikTok selalu video
        $type = $foto->source === 'googledrive'
            ? ($data['type'] ?? $foto->type)
            : 'video';

        // Thumbnail: kosong = tidak diubah; "ambil ulang otomatis" berlaku untuk YouTube, Drive, dan TikTok
        $oldThumb = (string) $foto->thumbnail_url;
        $newThumb = $oldThumb;

        if ($request->hasFile('thumbnail_file')) {
            $uploaded = MediaHelper::storeUploadedThumbnail($request->file('thumbnail_file'));
            if ($uploaded === '') {
                return back()
                    ->withInput()
                    ->withErrors(['thumbnail_file' => 'Gambar tidak bisa diproses. Coba format JPG atau PNG.']);
            }
            $newThumb = $uploaded;
        } elseif ($request->boolean('reset_thumbnail') && $foto->source === 'youtube') {
            $newThumb = MediaHelper::getYouTubeThumbnail($foto->external_id);
        } elseif ($request->boolean('reset_thumbnail') && $foto->source === 'googledrive') {
            $newThumb = MediaHelper::getGoogleDriveThumbnail($foto->external_id);
        } elseif ($request->boolean('reset_thumbnail') && $foto->source === 'tiktok') {
            $local = '';

            // 1) Jika admin menempel ulang link TikTok, pakai itu (paling andal)
            if (!empty($data['url_ulang']) && MediaHelper::isTikTokUrl($data['url_ulang'])) {
                $media = MediaHelper::processUrl($data['url_ulang']);
                if ($media && ($media['source'] ?? null) === 'tiktok'
                    && (string) $media['external_id'] === (string) $foto->external_id) {
                    $local = (string) ($media['thumbnail_url'] ?? '');
                }
            }

            // 2) Kalau tidak, coba ambil ulang dari ID video yang tersimpan
            if ($local === '') {
                $local = MediaHelper::refreshTikTokThumbnail((string) $foto->external_id);
            }

            if ($local === '') {
                return back()
                    ->withInput()
                    ->withErrors(['thumbnail' => 'Thumbnail TikTok gagal diambil otomatis. Tempel ulang link video TikTok di kolom link, atau isi URL gambar manual.']);
            }

            $newThumb = $local;
        } elseif (!empty($data['thumbnail'])) {
            $manual = $this->resolveManualThumbnail(
                $data['thumbnail'],
                (string) $foto->source,
                (string) $foto->external_id,
                ''
            );
            if ($manual !== '') {
                $newThumb = $manual;
            }
        }

        // File lokal lama dihapus hanya jika thumbnail benar-benar berganti ke file/URL lain
        if ($newThumb !== $oldThumb) {
            $this->deleteLocalThumbnail($oldThumb);
        }

        $foto->update([
            'judul'         => $data['judul'] ?? null,
            'kegiatan'      => self::normalizeKegiatan($data['kegiatan'] ?? null),
            'kategori'      => $data['kategori'] ?? null,
            'type'          => $type,
            'thumbnail_url' => $newThumb,
        ]);

        return redirect()
            ->route('admin.galeri.index')
            ->with('success', 'Perubahan media berhasil disimpan.');
    }

    // ------------------------------------------------------------------
    //  KELOLA KEGIATAN (ALBUM): ganti nama / gabungkan massal
    // ------------------------------------------------------------------

    public function kegiatan(): View
    {
        abort_unless(PermissionHelper::can('approve_gallery'), 403);

        $daftar = Gallery::query()
            ->whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->select(
                'kegiatan',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(status = 'published') as published")
            )
            ->groupBy('kegiatan')
            ->orderBy('kegiatan')
            ->get();

        return view('landing-page.admin.galeri.kegiatan', compact('daftar'));
    }

    public function renameKegiatan(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_gallery'), 403);

        $data = $request->validate([
            'dari' => ['required', 'string', 'max:255'],
            'ke'   => ['required', 'string', 'max:255'],
        ]);

        $ke = self::normalizeKegiatan($data['ke']);
        if ($ke === null) {
            return back()->withErrors(['ke' => 'Nama kegiatan baru tidak boleh kosong.']);
        }

        // Pencocokan mengikuti collation database (umumnya tidak peka huruf besar/kecil),
        // jadi varian ejaan seperti "Rapat Anggota" dan "rapat anggota" ikut tergabung.
        $jumlah = Gallery::where('kegiatan', $data['dari'])->update(['kegiatan' => $ke]);

        return back()->with('success', "{$jumlah} media dipindahkan ke album \"{$ke}\".");
    }

    // ------------------------------------------------------------------
    //  HELPER
    // ------------------------------------------------------------------

    /** Pembuat media boleh mengedit draft/revisi miliknya; approver boleh mengedit semuanya. */
    private function canEdit(Gallery $foto): bool
    {
        if (PermissionHelper::can('approve_gallery')) {
            return true;
        }

        return $foto->created_by === session('anggota.id')
            && in_array($foto->status, ['draft', 'revisi'], true);
    }

    private function daftarKegiatan()
    {
        return Gallery::query()
            ->whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->distinct()
            ->orderBy('kegiatan')
            ->pluck('kegiatan');
    }

    /** Rapikan spasi ganda dan spasi di tepi; string kosong menjadi null. */
    private static function normalizeKegiatan(?string $nama): ?string
    {
        if ($nama === null) {
            return null;
        }
        $nama = trim(preg_replace('/\s+/u', ' ', $nama));

        return $nama === '' ? null : $nama;
    }

    /**
     * Tentukan thumbnail dari input manual admin.
     * - Untuk video TikTok, URL CDN TikTok (signed, cepat kedaluwarsa, sering terisi otomatis
     *   oleh JS preview) TIDAK disimpan mentah: diunduh, dikecilkan, lalu disimpan lokal.
     *   Jika hasil otomatis ($currentThumb) sudah berupa file lokal, hasil itu dipertahankan.
     * - Selain itu, URL manual dipakai apa adanya.
     * Mengembalikan string kosong jika tidak ada yang perlu diganti / gagal.
     */
    private function resolveManualThumbnail(
        string $thumbnail,
        string $source,
        string $externalId,
        string $currentThumb
    ): string {
        $host = strtolower(parse_url($thumbnail, PHP_URL_HOST) ?? '');
        $isTikTokCdn = $source === 'tiktok'
            && (bool) preg_match('/(tiktok|ibyteimg|byteimg)/', $host);

        if (!$isTikTokCdn) {
            return $thumbnail;
        }

        // Hasil unduhan otomatis sudah ada dan berupa file lokal -> pertahankan
        if ($currentThumb !== '' && str_contains($currentThumb, '/storage/gallery-thumbs/')) {
            return $currentThumb;
        }

        return MediaHelper::storeTikTokThumbnail($thumbnail, $externalId);
    }

    private function deleteLocalThumbnail(?string $url): void
    {
        if ($url && preg_match('#/storage/(gallery-thumbs/[\w.\-]+)$#', $url, $m)) {
            Storage::disk('public')->delete($m[1]);
        }
    }

    // Opsional: endpoint untuk preview via AJAX (jika dibutuhkan).
    // Thumbnail TikTok tidak diunduh di sini (false), hanya mengembalikan URL aslinya.
    public function preview(Request $request)
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);

        $url = (string) $request->query('url', '');
        $data = $url !== '' ? MediaHelper::processUrl($url, false) : null;

        return response()->json($data ?: ['error' => 'Invalid URL']);
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);
    
        $data = $request->validate([
            'folder_url' => ['required', 'url', 'max:2000'],
            'kegiatan'   => ['nullable', 'string', 'max:255'],
            'kategori'   => ['nullable', 'string', 'in:' . implode(',', array_keys(Gallery::KATEGORI_LIST))],
            // maks 45 supaya masih muat nomor urut dalam batas 55 karakter kolom judul
            'judul'      => ['nullable', 'string', 'max:45'],
            'nomor_urut' => ['nullable', 'boolean'],
        ]);
    
        $folderId = MediaHelper::extractGoogleDriveFolderId($data['folder_url']);
        if (!$folderId) {
            return back()->withInput()->withErrors(['folder_url' => 'Link folder Google Drive tidak valid.']);
        }
    
        $files = MediaHelper::listGoogleDriveFolderImages($folderId);
        if ($files === null) {
            return back()->withInput()->withErrors(['folder_url' => 'Gagal membaca folder. Pastikan folder dibagikan publik dan API key sudah diatur.']);
        }
        if (empty($files)) {
            return back()->withInput()->withErrors(['folder_url' => 'Tidak ada gambar di folder ini.']);
        }
    
        $existing = Gallery::where('source', 'googledrive')
            ->whereIn('external_id', collect($files)->pluck('id'))
            ->pluck('external_id')->all();
    
        $kegiatan = self::normalizeKegiatan($data['kegiatan'] ?? null);
        $userId   = session('anggota.id');
    
        // Dasar judul: isian judul, kalau kosong pakai nama kegiatan
        $dasarJudul = self::normalizeKegiatan($data['judul'] ?? null)
            ?? ($kegiatan !== null ? Str::limit($kegiatan, 45, '') : null);
        $pakaiNomor = $request->boolean('nomor_urut');
        $lebar      = strlen((string) count($files));
    
        $dibuat = 0;
    
        DB::transaction(function () use ($files, $existing, $data, $kegiatan, $userId, $dasarJudul, $pakaiNomor, $lebar, &$dibuat) {
            foreach ($files as $i => $f) {
                if (in_array($f['id'], $existing, true)) continue; // lewati duplikat
    
                $judul = $dasarJudul;
                if ($judul !== null && $pakaiNomor) {
                    // nomor mengikuti urutan file di Drive (bukan hanya yang baru dibuat)
                    $judul .= ' ' . str_pad((string) ($i + 1), $lebar, '0', STR_PAD_LEFT);
                }
    
                Gallery::create([
                    'judul'         => $judul,
                    'kegiatan'      => $kegiatan,
                    'kategori'      => $data['kategori'] ?? null,
                    'type'          => 'image',
                    'source'        => 'googledrive',
                    'external_id'   => $f['id'],
                    'thumbnail_url' => MediaHelper::getGoogleDriveThumbnail($f['id']),
                    'embed_url'     => MediaHelper::getGoogleDriveEmbedUrl($f['id']),
                    'status'        => 'draft',
                    'created_by'    => $userId,
                ]);
                $dibuat++;
            }
        });
    
        $dilewati = count($files) - $dibuat;
    
        // Langsung arahkan ke album yang baru dibuat
        $tujuan = $kegiatan !== null
            ? route('admin.galeri.index', ['kegiatan' => $kegiatan])
            : route('admin.galeri.index', ['tampilan' => 'foto']);
    
        return redirect($tujuan)
            ->with('success', "{$dibuat} foto ditambahkan sebagai draft" . ($dilewati ? ", {$dilewati} dilewati (sudah ada)." : '.'));
    }

    public function albumAksi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kegiatan' => ['required', 'string', 'max:255'],
            'aksi'     => ['required', 'in:submit,review,approve,hapus'],
        ]);
    
        abort_unless(match ($data['aksi']) {
            'submit'  => PermissionHelper::can('create_gallery'),
            'review'  => PermissionHelper::can('review_gallery'),
            'approve' => PermissionHelper::can('approve_gallery'),
            'hapus'   => true, // dicek per foto lewat bolehHapus()
        }, 403);
    
        $userId  = session('anggota.id');
        $jabatan = session('anggota.jabatan_puk');
        $items   = $this->albumScope($data['kegiatan'])->get();
    
        $berhasil = 0;
        $gagal    = 0;
    
        foreach ($items as $foto) {
            try {
                switch ($data['aksi']) {
                    case 'submit':
                        if ($foto->created_by !== $userId || !in_array($foto->status, ['draft', 'revisi'], true)) {
                            continue 2;
                        }
                        $this->submitForApproval($foto, $jabatan, 'gallery');
                        break;
    
                    case 'review':
                        if ($foto->status !== 'submitted_bidang5') continue 2;
                        $this->reviewByBidang5($foto, true, null, null, 'gallery');
                        break;
    
                    case 'approve':
                        if ($foto->status !== 'submitted_sekretaris') continue 2;
                        $this->approveBySekretaris($foto, true, null, 'gallery');
                        break;
    
                    case 'hapus':
                        if (!$this->bolehHapus($foto)) continue 2;
                        $this->deleteLocalThumbnail($foto->thumbnail_url);
                        $foto->delete();
                        break;
                }
                $berhasil++;
            } catch (\Throwable $e) {
                report($e);
                $gagal++;
            }
        }
    
        $label = [
            'submit'  => 'dikirim / diterbitkan',
            'review'  => 'diteruskan ke approval',
            'approve' => 'disetujui dan dipublikasikan',
            'hapus'   => 'dihapus',
        ][$data['aksi']];
    
        $pesan = "{$berhasil} foto {$label}" . ($gagal ? ", {$gagal} gagal (lihat log)." : '.');
    
        // Album yang sudah kosong -> kembali ke daftar album
        $redirect = ($data['aksi'] === 'hapus' && $this->albumScope($data['kegiatan'])->doesntExist())
            ? redirect()->route('admin.galeri.index')
            : back();
    
        return $redirect->with('success', $pesan);
    }

    private function albumScope(string $kegiatan)
    {
        $query = Gallery::query();
    
        return $kegiatan === '__none'
            ? $query->where(fn ($w) => $w->whereNull('kegiatan')->orWhere('kegiatan', ''))
            : $query->where('kegiatan', $kegiatan);
    }
    
    /** Jumlah foto per aksi yang boleh dilakukan user ini di satu album. */
    private function albumInfo(string $kegiatan): array
    {
        $userId = session('anggota.id');
        $rows   = $this->albumScope($kegiatan)->get(['id', 'status', 'created_by']);
    
        return [
            'total'      => $rows->count(),
            'bisa_kirim' => $rows->filter(fn ($f) => $f->created_by === $userId
                                && in_array($f->status, ['draft', 'revisi'], true))->count(),
            'review'     => $rows->where('status', 'submitted_bidang5')->count(),
            'approval'   => $rows->where('status', 'submitted_sekretaris')->count(),
            'bisa_hapus' => $rows->filter(fn ($f) => $this->bolehHapus($f))->count(),
        ];
    }
    
    /** Aturan hapus yang sama dengan destroy(): pemilik (draft/revisi) atau approver (published). */
    private function bolehHapus(Gallery $foto): bool
    {
        $userId = session('anggota.id');
    
        return ($foto->created_by === $userId && in_array($foto->status, ['draft', 'revisi'], true))
            || (PermissionHelper::can('approve_gallery') && $foto->status === 'published');
    }


}