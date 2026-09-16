<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialMedia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSocialMediaController extends Controller
{
    public function index()
    {
        $medias = SocialMedia::orderBy('id')->get();
        return view('landing-page.admin.medsos.index', compact('medias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'twitter', 'instagram', 'youtube'])],
            'url'      => 'required|url',
            'icon'     => 'nullable|string|max:100',
        ]);

        SocialMedia::create($validated);

        return redirect()->route('admin.medsos.index')
                         ->with('success', 'Media sosial berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $media = SocialMedia::findOrFail($id);

        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'twitter', 'instagram', 'youtube'])],
            'url'      => 'required|url',
            'icon'     => 'nullable|string|max:100',
        ]);

        $media->update($validated);

        return redirect()->route('admin.medsos.index')
                         ->with('success', 'Media sosial berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $media = SocialMedia::findOrFail($id);
        $media->delete();

        return redirect()->route('admin.medsos.index')
                         ->with('success', 'Media sosial berhasil dihapus.');
    }

    public function edit($id)
    {
        $media = SocialMedia::findOrFail($id);
        return response()->json($media);
    }
}