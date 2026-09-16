<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Information;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InformationAdminController extends Controller
{
    public function index(): View
    {
        $informasiList = Information::orderBy('urutan')->orderBy('judul')->get();

        return view('landing-page.admin.informasi.index', [
            'informasiList' => $informasiList,
        ]);
    }

    public function create(): View
    {
        return view('landing-page.admin.informasi.form', [
            'informasi' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);
        $validated['slug'] = $this->uniqueSlug($validated['judul']);

        Information::create($validated);

        return redirect()->route('admin.informasi.index')->with('success', 'Informasi berhasil ditambahkan.');
    }

    public function edit(Information $informasi): View
    {
        return view('landing-page.admin.informasi.form', [
            'informasi' => $informasi,
        ]);
    }

    public function update(Request $request, Information $informasi): RedirectResponse
    {
        $validated = $this->validateData($request);

        if ($validated['judul'] !== $informasi->judul) {
            $validated['slug'] = $this->uniqueSlug($validated['judul'], $informasi->id);
        }

        $informasi->update($validated);

        return redirect()->route('admin.informasi.index')->with('success', 'Informasi berhasil diperbarui.');
    }

    public function destroy(Information $informasi): RedirectResponse
    {
        $informasi->delete();

        return back()->with('success', 'Informasi berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'judul'    => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'in:' . implode(',', array_keys(Information::KATEGORI_LIST))],
            'konten'   => ['required', 'string'],
            'status'   => ['required', 'in:draft,published'],
            'urutan'   => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $judul, ?int $ignoreId = null): string
    {
        $base = Str::slug($judul);
        $slug = $base;
        $i = 1;

        while (
            Information::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}