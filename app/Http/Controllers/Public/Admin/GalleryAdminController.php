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
        return view('landing-page.admin.galeri.form');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('create_gallery'), 403);

        // Validasi input baru
        $validated = $request->validate([
            'judul'       => ['nullable', 'string', 'max:55'],
            'kegiatan'    => ['nullable', 'string', 'max:255'],
            'kategori'    => ['nullable', 'string', 'in:' . implode(',', array_keys(\App\Models\Gallery::KATEGORI_LIST))],
            'url'         => ['required', 'url'],
            'type'        => ['required', 'in:image,video'],
            'thumbnail'   => ['nullable', 'url'],
        ]);

        // Proses URL dengan MediaHelper
        $media = MediaHelper::processUrl($validated['url']);
        if (!$media) {
            return back()
                ->withInput()
                ->withErrors(['url' => 'URL tidak valid. Hanya mendukung YouTube dan Google Drive.']);
        }

        // Override type dari pilihan admin
        $media['type'] = $validated['type'];

        // Jika admin memberikan thumbnail manual, gunakan itu
        if (!empty($validated['thumbnail'])) {
            $media['thumbnail_url'] = $validated['thumbnail'];
        }

        // Jika thumbnail_url masih kosong (Google Drive), set placeholder
        if (empty($media['thumbnail_url'])) {
            $media['thumbnail_url'] = asset('images/placeholder-media.png');
        }

        // Simpan ke database
        $gallery = Gallery::create([
            'judul'         => $validated['judul'],
            'kegiatan'      => $validated['kegiatan'],
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

    // Opsional: endpoint untuk preview via AJAX (jika dibutuhkan)
    public function preview(Request $request)
    {
        $url = $request->query('url');
        $data = MediaHelper::processUrl($url);
        return response()->json($data ?: ['error' => 'Invalid URL']);
    }
}