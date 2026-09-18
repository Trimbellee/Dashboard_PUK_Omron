<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Admin\Concerns\HandlesApprovalWorkflow;
use App\Helpers\PermissionHelper;
use App\Helpers\QuillSanitizer;
use App\Helpers\ImageOptimizer;
use App\Models\News;
use App\Models\Tag;
use App\Models\Media;
use App\Models\ActivityLog;
use App\Helpers\ImageWatermark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsAdminController extends Controller
{
    use HandlesApprovalWorkflow;

    public function index(Request $request): View
    {
        $userId = session('anggota.id');

        $statBase = News::query();

        // Dashboard Stats
        $stats = [
            'draft'     => (clone $statBase)->whereIn('status', ['draft', 'revisi'])->count(),
            'menunggu'  => (clone $statBase)->whereIn('status', ['submitted_bidang5', 'submitted_sekretaris'])->count(),
            'published' => (clone $statBase)->where('status', 'published')->count(),
            'takedown'  => (clone $statBase)->where('status', 'takedown')->count(),
            'scheduled' => (clone $statBase)->where('status', 'scheduled')->count(),
            'featured'  => (clone $statBase)->where('is_featured', true)->count(),
            'breaking'  => (clone $statBase)->where('is_breaking', true)->count(),
        ];

        // Dashboard Cards
        $totalBerita = (clone $statBase)->count();
        $totalViews = (clone $statBase)->sum('views');
        $popularNews = (clone $statBase)->where('status', 'published')->orderBy('views', 'desc')->first();
        $activeAuthors = (clone $statBase)->distinct('created_by')->count('created_by');

        // Top Authors
        $topAuthors = (clone $statBase)
            ->selectRaw('created_by, count(*) as news_count')
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->orderBy('news_count', 'desc')
            ->limit(5)
            ->with('creator')
            ->get();

        // Most Read Articles
        $mostRead = (clone $statBase)
            ->where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        // Recent Activity Logs
        $recentLogs = ActivityLog::with('user')
            ->where('loggable_type', News::class)
            ->latest()
            ->limit(10)
            ->get();

        // Query untuk daftar berita
        $query = (clone $statBase)->latest();

        $filter = $request->string('filter')->toString();
        match ($filter) {
            'draft'     => $query->whereIn('status', ['draft', 'revisi']),
            'menunggu'  => $query->whereIn('status', ['submitted_bidang5', 'submitted_sekretaris']),
            'published' => $query->where('status', 'published'),
            'takedown'  => $query->where('status', 'takedown'),
            'scheduled' => $query->where('status', 'scheduled'),
            'featured'  => $query->where('is_featured', true),
            'breaking'  => $query->where('is_breaking', true),
            default     => null,
        };

        // Advanced Filters
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('author')) {
            $query->where('created_by', $request->author);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tags.id', $request->tag);
            });
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->filled('q')) {
            $query->where('judul', 'like', '%' . $request->string('q') . '%');
        }

        $canReview = PermissionHelper::can('review_news');
        $canApprove = PermissionHelper::can('approve_news');
        $canCreate = PermissionHelper::can('create_news');

        // Untuk filter dropdown
        $kategoriList = (clone $statBase)->distinct()->pluck('kategori')->filter()->values()->toArray();
        $authors = \App\Models\Anggota::whereIn('id', (clone $statBase)->distinct()->pluck('created_by')->toArray())->get();

        return view('landing-page.admin.berita.index', [
            'beritaList' => $query->paginate(15)->withQueryString(),
            'stats' => $stats,
            'activeFilter' => $filter ?: 'semua',
            'searchTerm' => $request->string('q')->toString(),
            'canReview' => $canReview,
            'canApprove' => $canApprove,
            'canCreate' => $canCreate,
            'userId' => $userId,

            // Dashboard Data
            'totalBerita' => $totalBerita,
            'totalViews' => $totalViews,
            'popularNews' => $popularNews,
            'activeAuthors' => $activeAuthors,
            'topAuthors' => $topAuthors,
            'mostRead' => $mostRead,
            'recentLogs' => $recentLogs,
            'kategoriList' => $kategoriList,
            'authors' => $authors,
        ]);
    }

    public function create(): View
    {
        abort_unless(PermissionHelper::can('create_news'), 403);
        return view('landing-page.admin.berita.form', ['berita' => new News()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('create_news'), 403);

        $validated = $this->validated($request);
        $validated['konten'] = $this->sanitizeKonten($validated['konten']);
        $validated['konten_delta'] = $request->konten_delta;
        $validated['slug'] = Str::slug($validated['judul']) . '-' . Str::random(5);
        $validated['created_by'] = session('anggota.id');

        // REVISI: jadwal publikasi TIDAK lagi langsung mengubah status saat
        // disimpan. scheduled_at sekarang murni disimpan sebagai DATA di atas
        // berita berstatus draft — status baru berubah jadi 'scheduled' pada
        // saat berita ini disetujui (approve) oleh Sekretaris, lihat method
        // approve() di bawah. Jadi berita baru selalu mulai sebagai 'draft',
        // terlepas dari apakah scheduled_at diisi atau tidak.
        $validated['status'] = 'draft';

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_breaking'] = $request->boolean('is_breaking');
        $validated['is_headline'] = $request->boolean('is_headline');
        $validated['meta_title'] = $request->meta_title ?? $validated['judul'];
        $validated['meta_description'] = $request->meta_description ?? $validated['ringkasan'];

        if ($request->hasFile('gambar')) {
            // Gambar utama dikonversi & dikompres ke .webp dulu (hemat storage),
            // dengan nama file dari judul berita (maks 20 huruf) + timestamp,
            // baru watermark diterapkan di atas hasil webp tersebut.
            $validated['gambar'] = ImageOptimizer::storeAsWebp(
                $request->file('gambar'),
                'berita',
                titleForFilename: $validated['judul']
            );
            ImageWatermark::apply($validated['gambar']);
        }

        $berita = News::create($validated);

        // Handle Tags
        if ($request->has('tags')) {
            $tagIds = $this->syncTags($request->tags);
            $berita->tags()->sync($tagIds);
        }

        ActivityLog::log($berita, 'created', 'Berita baru dibuat');

        return redirect()
            ->route('admin.berita.index')
            ->with('success', $berita->scheduled_at
                ? 'Draft berita berhasil disimpan. Jadwal publikasi (' . $berita->scheduled_at->translatedFormat('d M Y H:i') . ') akan aktif setelah disetujui Sekretaris.'
                : 'Draft berita berhasil disimpan.');
    }

    public function edit(News $berita): View
    {
        $this->authorizeEdit($berita);

        // Deteksi apakah ini mode review edit
        $isReviewEdit = request()->query('mode') === 'review'
            && PermissionHelper::can('review_news')
            && $berita->status === 'submitted_bidang5';

        return view('landing-page.admin.berita.form', [
            'berita' => $berita,
            'isReviewEdit' => $isReviewEdit,
        ]);
    }

    public function update(Request $request, News $berita): RedirectResponse
    {
        $this->authorizeEdit($berita);
        abort_unless(in_array($berita->status, ['draft', 'revisi' , 'scheduled'], true), 400, 'Berita yang sudah disubmit tidak bisa diedit.');

        $validated = $this->validated($request);
        $validated['konten'] = $this->sanitizeKonten($validated['konten']);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_breaking'] = $request->boolean('is_breaking');
        $validated['is_headline'] = $request->boolean('is_headline');
        $validated['meta_title'] = $request->meta_title ?? $validated['judul'];
        $validated['meta_description'] = $request->meta_description ?? $validated['ringkasan'];

        // REVISI: status TIDAK disentuh di sini sama sekali. scheduled_at
        // (sudah termasuk di $validated lewat rule di method validated())
        // cuma diperbarui sebagai data biasa — berita tetap berstatus
        // draft/revisi seperti sebelum disimpan. Status baru berubah jadi
        // 'scheduled' saat disetujui Sekretaris lewat approve() di bawah.

        if ($request->hasFile('gambar')) {
            // Hapus dulu gambar lama sebelum simpan yang baru, supaya file
            // lama tidak menumpuk terus di storage tiap kali diganti.
            if ($berita->gambar) {
                Storage::disk('public')->delete($berita->gambar);
            }
            $validated['gambar'] = ImageOptimizer::storeAsWebp(
                $request->file('gambar'),
                'berita',
                titleForFilename: $validated['judul']
            );
            ImageWatermark::apply($validated['gambar']);
        }

        $userId = session('anggota.id');
        $isCreator = $berita->created_by === $userId;
        $canReview = PermissionHelper::can('review_news');

        if ($canReview && !$isCreator) {
            $this->updateByReviewer($berita, $validated, 'news');
        } else if ($isCreator) {
            $this->updateByCreator($berita, $validated);
        } else {
            abort(403, 'Anda tidak berhak mengedit berita ini.');
        }

        // Handle Tags
        if ($request->has('tags')) {
            $tagIds = $this->syncTags($request->tags);
            $berita->tags()->sync($tagIds);
        }

        return redirect()
            ->route('admin.berita.index')
            ->with('success', $request->filled('scheduled_at')
                ? 'Berita berhasil diperbarui. Jadwal publikasi (' . \Carbon\Carbon::parse($request->scheduled_at)->translatedFormat('d M Y H:i') . ') akan aktif setelah disetujui Sekretaris.'
                : 'Berita berhasil diperbarui.');
    }

    public function destroy(News $berita): RedirectResponse
    {
        $this->authorizeEdit($berita);
        abort_unless(in_array($berita->status, ['draft', 'revisi'], true), 400, 'Berita yang sudah disubmit tidak bisa dihapus.');

        // FIX: sebelumnya file gambar tidak pernah dihapus saat berita
        // dihapus, sehingga file lama menumpuk terus di storage.
        if ($berita->gambar) {
            Storage::disk('public')->delete($berita->gambar);
        }

        ActivityLog::log($berita, 'deleted', 'Berita dihapus');
        $berita->delete();

        return back()->with('success', 'Berita berhasil dihapus.');
    }

    public function submit(News $berita): RedirectResponse
    {
        $this->authorizeEdit($berita);
        $status = $this->submitForApproval($berita, session('anggota.jabatan_puk'), 'news');
        return back()->with('success', match ($status) {
            'published' => 'Berita berhasil dipublikasikan.',
            default => 'Berita berhasil dikirim untuk approval.',
        });
    }

    public function review(Request $request, News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('review_news'), 403);

        $approve = $request->boolean('approve');

        if (!$approve) {
            $request->validate(['catatan_revisi' => ['required', 'string', 'max:1000']]);
            $this->reviewByBidang5($berita, false, $request->catatan_revisi, null, 'news');
            return back()->with('success', 'Berita dikembalikan untuk direvisi.');
        }

        $editedData = null;
        if ($request->has('edited_konten') && !empty($request->edited_konten)) {
            $editedData = ['konten' => $this->sanitizeKonten($request->edited_konten)];
        }
        if ($request->has('edited_judul') && !empty($request->edited_judul)) {
            $editedData = $editedData ?? [];
            $editedData['judul'] = $request->edited_judul;
        }

        $this->reviewByBidang5($berita, true, null, $editedData, 'news');

        return back()->with('success', $editedData
            ? 'Berita diedit oleh reviewer dan diteruskan ke Sekretaris.'
            : 'Berita diteruskan ke Sekretaris untuk approval final.');
    }

    public function approve(News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);

        // FIX: hormati jadwal publikasi. Sebelumnya approve() SELALU langsung
        // memanggil approveBySekretaris() yang mempublikasikan berita saat
        // itu juga — walau berita punya scheduled_at di masa depan. Sekarang,
        // kalau scheduled_at masih di masa depan, berita TIDAK langsung
        // dipublikasikan; statusnya dikembalikan/dipertahankan sebagai
        // 'scheduled' (sudah disetujui Sekretaris, tinggal menunggu waktunya).
        //
        // Publikasi sesungguhnya pada waktu yang dijadwalkan dilakukan oleh
        // command `news:publish-scheduled` (lihat
        // app/Console/Commands/PublishScheduledNews.php), yang idealnya
        // dijalankan tiap menit lewat scheduler Laravel.
        if ($berita->scheduled_at && $berita->scheduled_at->isFuture()) {
            $berita->update([
                'status' => 'scheduled',
                'approved_by_sekretaris' => session('anggota.id'),
            ]);

            ActivityLog::log(
                $berita,
                'approved',
                'Berita disetujui Sekretaris, akan otomatis terbit pada ' . $berita->scheduled_at->translatedFormat('d M Y H:i')
            );

            return back()->with(
                'success',
                'Berita disetujui. Akan otomatis terbit pada ' . $berita->scheduled_at->translatedFormat('d M Y H:i') . '.'
            );
        }

        // Tidak ada jadwal, atau jadwalnya sudah lewat -> terbitkan sekarang
        // seperti biasa.
        $this->approveBySekretaris($berita, true, null, 'news');
        return back()->with('success', 'Berita disetujui dan dipublikasikan.');
    }

    public function reject(Request $request, News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);
        $request->validate(['catatan_revisi' => ['required', 'string', 'max:1000']]);
        $this->approveBySekretaris($berita, false, $request->catatan_revisi, 'news');
        return back()->with('success', 'Berita dikembalikan untuk direvisi.');
    }

    public function reviewWithEdit(Request $request, News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('review_news'), 403);
        abort_unless($berita->status === 'submitted_bidang5', 400, 'Berita tidak sedang menunggu review.');

        $validated = $request->validate([
            'judul' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten' => ['nullable', 'string'],
            'gambar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $editedData = [];
        $hasChanges = false;

        if ($request->has('judul') && $request->judul !== $berita->judul) {
            $editedData['judul'] = $request->judul;
            $hasChanges = true;
        }

        if ($request->has('ringkasan') && $request->ringkasan !== $berita->ringkasan) {
            $editedData['ringkasan'] = $request->ringkasan;
            $hasChanges = true;
        }

        if ($request->has('konten') && $request->konten !== $berita->konten) {
            $editedData['konten'] = $this->sanitizeKonten($request->konten);
            $hasChanges = true;
        }

        if ($request->hasFile('gambar')) {
            // Hapus dulu gambar lama sebelum simpan yang baru.
            if ($berita->gambar) {
                Storage::disk('public')->delete($berita->gambar);
            }
            $editedData['gambar'] = ImageOptimizer::storeAsWebp(
                $request->file('gambar'),
                'berita',
                titleForFilename: $editedData['judul'] ?? $berita->judul
            );
            ImageWatermark::apply($editedData['gambar']);
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return back()->with('info', 'Tidak ada perubahan yang dilakukan. Gunakan tombol "Setujui" tanpa edit.');
        }

        $this->reviewByBidang5($berita, true, null, $editedData, 'news');

        return redirect()
            ->route('admin.berita.index')
            ->with('success', 'Berita diedit oleh reviewer dan diteruskan ke Sekretaris.');
    }

    public function takedown(Request $request, News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);

        $request->validate([
            'takedown_reason' => ['required', 'string', 'max:500', 'min:5'],
        ]);

        $this->takedownNews($berita, $request->takedown_reason, 'news');

        return redirect()
            ->route('admin.berita.index')
            ->with('success', 'Berita berhasil di-takedown. Alasan: ' . $request->takedown_reason);
    }

    public function restore(News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);
        $this->restoreNews($berita, 'news');
        return redirect()
            ->route('admin.berita.index')
            ->with('success', 'Berita berhasil dipulihkan dan diterbitkan kembali.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:news,id'],
            'action' => ['required', 'string', 'in:published,takedown,delete,feature,unfeature,breaking,unbreaking'],
        ]);

        $ids = $request->ids;
        $action = $request->action;

        switch ($action) {
            case 'published':
                News::whereIn('id', $ids)->where('status', 'submitted_sekretaris')->update(['status' => 'published', 'published_at' => now()]);
                break;
            case 'takedown':
                News::whereIn('id', $ids)->where('status', 'published')->update(['status' => 'takedown', 'takedown_at' => now(), 'takedown_by' => session('anggota.id')]);
                break;
            case 'delete':
                // FIX: sebelumnya bulk delete tidak menghapus file gambar
                // dari storage sama sekali. Sekarang ambil dulu berita yang
                // akan dihapus, bersihkan gambarnya, baru hapus recordnya.
                $beritaToDelete = News::whereIn('id', $ids)
                    ->whereIn('status', ['draft', 'revisi'])
                    ->get();

                foreach ($beritaToDelete as $item) {
                    if ($item->gambar) {
                        Storage::disk('public')->delete($item->gambar);
                    }
                }

                News::whereIn('id', $beritaToDelete->pluck('id'))->delete();
                break;
            case 'feature':
                News::whereIn('id', $ids)->update(['is_featured' => true]);
                break;
            case 'unfeature':
                News::whereIn('id', $ids)->update(['is_featured' => false]);
                break;
            case 'breaking':
                News::whereIn('id', $ids)->update(['is_breaking' => true]);
                break;
            case 'unbreaking':
                News::whereIn('id', $ids)->update(['is_breaking' => false]);
                break;
        }

        return back()->with('success', 'Aksi massal berhasil diterapkan.');
    }

    public function schedulePublish(Request $request, News $berita): RedirectResponse
    {
        abort_unless(PermissionHelper::can('approve_news'), 403);

        $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $berita->update([
            'status' => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);

        ActivityLog::log($berita, 'scheduled', 'Berita dijadwalkan untuk publikasi', ['scheduled_at' => $request->scheduled_at]);

        return back()->with('success', 'Berita dijadwalkan untuk publikasi pada ' . $request->scheduled_at);
    }

    public function getMediaList()
    {
        try {
            $media = Media::latest()->get();
            return response()->json($media);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function uploadMedia(Request $request)
    {
        try {
            $request->validate([
                'media.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']
            ]);

            $uploaded = [];
            foreach ($request->file('media') as $file) {
                // Gambar yang di-drop/paste/upload lewat media library editor
                // Quill juga dikonversi & dikompres ke .webp. Ukuran & mime
                // type yang disimpan ke DB mengikuti hasil webp-nya, bukan
                // file asli sebelum dikompres.
                $optimized = ImageOptimizer::storeAsWebpWithMeta($file, 'media');

                $media = Media::create([
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp',
                    'url' => Storage::url($optimized['path']),
                    'thumbnail' => Storage::url($optimized['path']),
                    'size' => $optimized['size'],
                    'mime_type' => $optimized['mime_type'],
                    'user_id' => session('anggota.id')
                ]);
                $uploaded[] = $media;
            }

            return response()->json(['success' => true, 'media' => $uploaded]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function autoSave(Request $request)
    {
        try {
            $newsId = $request->news_id ?? 0;
            $data = [
                'judul' => $request->judul,
                'konten' => $this->sanitizeKonten($request->konten ?? ''),
                'ringkasan' => $request->ringkasan,
                'last_edited_at' => now(),
                'last_edited_by' => session('anggota.id')
            ];

            if ($newsId > 0) {
                $berita = News::find($newsId);
                if ($berita && in_array($berita->status, ['draft', 'revisi'])) {
                    $berita->update($data);
                    ActivityLog::log($berita, 'updated', 'Auto-save draft');
                }
            } else {
                $berita = News::create(array_merge($data, [
                    'slug' => Str::slug($request->judul ?? 'draft') . '-' . Str::random(5),
                    'created_by' => session('anggota.id'),
                    'status' => 'draft'
                ]));

                return response()->json([
                    'success' => true,
                    'news_id' => $berita->id,
                    'message' => 'Draft baru dibuat'
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sendNewsletter(News $berita): RedirectResponse
    {
        try {
            ActivityLog::log($berita, 'newsletter', 'Berita dikirim ke newsletter');
            return back()->with('success', 'Berita berhasil dikirim ke newsletter');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim newsletter: ' . $e->getMessage());
        }
    }

    public function preview(News $berita)
    {
        return response()->json([
            'id' => $berita->id,
            'judul' => $berita->judul,
            'kategori' => $berita->kategori,
            'konten' => $berita->konten,
            'gambar' => $berita->gambar ? asset('storage/' . $berita->gambar) : null,
            'penulis' => $berita->creator?->nama_lengkap ?? 'Redaksi',
            'tanggal' => match(true) {
                            !is_null($berita->published_at) => $berita->published_at->translatedFormat('d M Y H:i'),
                            !is_null($berita->scheduled_at) => $berita->scheduled_at->translatedFormat('d M Y H:i'),
                            default => $berita->updated_at->translatedFormat('d M Y H:i'),
                        },
            'waktu_baca' => $berita->getReadingTime(),
            'jumlah_kata' => str_word_count(strip_tags($berita->konten)),
            'is_featured' => $berita->is_featured,
            'is_breaking' => $berita->is_breaking,
            'status' => $berita->status,
        ]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'kategori'  => ['required', 'string', 'max:100'],
            'judul'     => ['required', 'string', 'max:255'],
            'ringkasan' => ['required', 'string', 'max:500'],
            'konten'    => ['required', 'string'], // HTML dari Quill
            'konten_delta' => ['nullable', 'json'], // Tambahkan ini
            'gambar'    => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'tags'      => ['nullable', 'string'],
            // FIX: sebelumnya field ini TIDAK ADA di rule validasi sama sekali,
            // sehingga $request->validate() tidak pernah mengembalikannya di
            // $validated — akibatnya scheduled_at TIDAK PERNAH tersimpan ke
            // database walau status sempat ke-set 'scheduled'. 'nullable' aman
            // dipakai karena Laravel otomatis mengubah string kosong dari
            // input datetime-local menjadi null (ConvertEmptyStringsToNull).
            'scheduled_at' => ['nullable', 'date'],
        ]);

        return $validated;
    }

    /**
     * Sanitize konten Quill menggunakan QuillSanitizer helper
     */
    private function sanitizeKonten(string $html): string
    {
        return QuillSanitizer::sanitize($html);
    }

    private function syncTags(string $tagString): array
    {
        $tagNames = array_map('trim', explode(',', $tagString));
        $tagIds = [];

        foreach ($tagNames as $name) {
            if (empty($name)) continue;
            $tag = Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            );
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    private function authorizeEdit(News $berita): void
    {
        $userId = session('anggota.id');
        $isCreator = $berita->created_by === $userId;
        $canReview = PermissionHelper::can('review_news');
        $canApprove = PermissionHelper::can('approve_news');

        $hasAccess = $isCreator || $canReview || $canApprove;
        abort_unless($hasAccess, 403, 'Anda tidak berhak mengelola berita ini.');
    }
}