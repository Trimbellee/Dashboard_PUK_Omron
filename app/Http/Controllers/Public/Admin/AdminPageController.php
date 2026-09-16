<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AdminPageController extends Controller
{
    public function index(): View
    {
        $pages = AdminPage::orderBy('order')->get();

        return view('landing-page.admin.halaman.index', [
            'pages' => $pages,
        ]);
    }

    public function create(): View
    {
        return view('landing-page.admin.halaman.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['type'] = 'menu';
        $validated['order'] = (int) (AdminPage::max('order') ?? 0) + 1;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_locked'] = false;

        AdminPage::create($validated);

        return $this->redirectWithFeedback($validated['route_name'] ?? null, 'Halaman baru berhasil ditambahkan ke menu.');
    }

    public function edit(AdminPage $adminPage): View
    {
        return view('landing-page.admin.halaman.edit', [
            'page' => $adminPage,
        ]);
    }

    public function update(Request $request, AdminPage $adminPage)
    {
        $validated = $this->validatedData($request, $adminPage->id);
        $validated['is_active'] = $request->boolean('is_active');

        $adminPage->update($validated);

        return $this->redirectWithFeedback($validated['route_name'] ?? null, 'Halaman berhasil diperbarui.');
    }

    public function destroy(AdminPage $adminPage)
    {
        if ($adminPage->is_locked) {
            return redirect()
                ->route('admin.halaman.index')
                ->with('error', 'Halaman ini terkunci dan tidak bisa dihapus karena merupakan fitur inti sistem.');
        }

        $adminPage->delete();

        return redirect()
            ->route('admin.halaman.index')
            ->with('success', 'Halaman berhasil dihapus dari menu.');
    }

    public function moveUp(AdminPage $adminPage)
    {
        $prev = AdminPage::where('order', '<', $adminPage->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prev) {
            [$adminPage->order, $prev->order] = [$prev->order, $adminPage->order];
            $adminPage->save();
            $prev->save();
        }

        return redirect()->route('admin.halaman.index');
    }

    public function moveDown(AdminPage $adminPage)
    {
        $next = AdminPage::where('order', '>', $adminPage->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($next) {
            [$adminPage->order, $next->order] = [$next->order, $adminPage->order];
            $adminPage->save();
            $next->save();
        }

        return redirect()->route('admin.halaman.index');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
{
    return $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'slug' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
                'unique:admin_pages,slug' . ($ignoreId ? ',' . $ignoreId : ''),
            ],
            // Opsional: hanya diisi kalau developer sudah bikin controller & route sungguhan.
            // Kalau kosong, halaman otomatis pakai placeholder generic (route admin.halaman.show).
            'route_name' => ['nullable', 'string', 'max:150'],
            'icon_svg' => ['nullable', 'string'],
        ]);
    }

    private function redirectWithFeedback(?string $routeName, string $successMessage)
    {
        // Kalau route_name diisi tapi belum terdaftar di kode -> beri tahu, tapi jangan blokir.
        // Kalau route_name kosong -> halaman langsung aktif lewat placeholder generic, tidak perlu warning.
        if ($routeName && !Route::has($routeName)) {
            return redirect()
                ->route('admin.halaman.index')
                ->with('warning', "Halaman disimpan. Route kustom \"{$routeName}\" belum terdaftar, jadi sementara menu masih mengarah ke halaman placeholder sampai route-nya dibuat.");
        }

        return redirect()
            ->route('admin.halaman.index')
            ->with('success', $successMessage);
    }

    public function show(AdminPage $page): View
    {
        return view('landing-page.admin.halaman.show', [
            'page' => $page,
        ]);
    }
}