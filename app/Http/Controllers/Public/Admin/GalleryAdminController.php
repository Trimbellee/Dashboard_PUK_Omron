<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Admin\Concerns\HandlesApprovalWorkflow;
use App\Helpers\MediaHelper;
use App\Helpers\PermissionHelper;
use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $query = (clone $statBase)->latest();

        $filter = $request->string('filter')->toString();
        match ($filter) {
            'draft'     => $query->whereIn('status', ['draft', 'revisi']),
            'menunggu'  => $query->whereIn('status', ['submitted_bidang5', 'submitted_sekretaris']),
            'published' => $query->where('status', 'published'),
            default     => null,
        };

        return view('landing-page.admin.galeri.index', [
            'galeriList'   => $query->paginate(20)->withQueryString(),
            'stats'        => $stats,
            'activeFilter' => $filter ?: 'semua',
        ]);
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

        // Jika admin memberikan thumbnail manual, gunakan itu
        if (!empty($validated['thumbnail'])) {
            $media['thumbnail_url'] = $validated['thumbnail'];
        }

        // Jika thumbnail_url masih kosong (Google Drive / TikTok gagal ambil), set placeholder
        if (empty($media['thumbnail_url'])) {
            $media['thumbnail_url'] = asset('images/placeholder-media.png');
        }

        Gallery::create([
            'judul'         => $validated['judul'] ?? null,
            'kegiatan'      => $validated['kegiatan'] ?? null,
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

        $this->reviewByBidang5($foto, $approve, $request->catatan_revisi, 'gallery');

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

    // Opsional: endpoint untuk preview via AJAX (jika dibutuhkan).
    // Thumbnail TikTok tidak diunduh di sini (false), hanya mengembalikan URL aslinya.
    public function preview(Request $request)
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);

        $url = (string) $request->query('url', '');
        $data = $url !== '' ? MediaHelper::processUrl($url, false) : null;

        return response()->json($data ?: ['error' => 'Invalid URL']);
    }
}