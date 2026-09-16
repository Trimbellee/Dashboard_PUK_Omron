<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DynamicPageController extends Controller
{
    /**
     * Daftar semua halaman type=content
     */
    public function manage(): View
    {
        $pages = AdminPage::where('type', 'content')->orderBy('order')->get();
        return view('landing-page.admin.pages.manage', compact('pages'));
    }

    /**
     * Buat halaman baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
        ]);

        $slug = Str::slug($validated['label']);

        if (AdminPage::where('slug', $slug)->exists()) {
            return back()->withErrors(['label' => 'Halaman dengan nama serupa sudah ada.']);
        }

        $page = AdminPage::create([
            'slug'       => $slug,
            'label'      => $validated['label'],
            'type'       => 'content',
            'route_name' => null,
            'content'    => null,
            'order'      => (AdminPage::max('order') ?? 0) + 10,
            'is_active'  => true,
        ]);

        return redirect()
            ->route('admin.page.edit', $page->slug)
            ->with('success', 'Halaman berhasil dibuat. Silakan isi kontennya.');
    }

    /**
     * Tampilkan halaman (read-only)
     */
    public function show(string $slug): View
    {
        $page = AdminPage::where('slug', $slug)->where('type', 'content')->active()->firstOrFail();
        return view('landing-page.admin.pages.show', compact('page'));
    }

    /**
     * Form edit konten
     */
    public function edit(string $slug): View
    {
        $page = AdminPage::where('slug', $slug)->where('type', 'content')->firstOrFail();
        return view('landing-page.admin.pages.edit', compact('page'));
    }

    /**
     * Update konten - SIMPAN SEBAGAI DELTA (JSON)
     */
    public function update(Request $request, string $slug)
    {
        $page = AdminPage::where('slug', $slug)->where('type', 'content')->firstOrFail();

        $validated = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        // Simpan konten apa adanya (sudah berupa JSON Delta dari Quill)
        $page->update([
            'content' => $validated['content'] ?: null,
        ]);

        return redirect()
            ->route('admin.page.show', $page->slug)
            ->with('success', 'Konten berhasil disimpan.');
    }

    /**
     * Hapus halaman
     */
    public function destroy(AdminPage $adminPage)
    {
        if ($adminPage->is_locked) {
            abort(403, 'Halaman ini tidak boleh dihapus.');
        }

        $adminPage->delete();
        return back()->with('success', 'Halaman dihapus.');
    }
}