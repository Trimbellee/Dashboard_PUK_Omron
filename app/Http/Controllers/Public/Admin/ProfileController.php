<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $profile = OrganizationProfile::first() ?? new OrganizationProfile();
        
        return view('landing-page.admin.profile.index', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Cek apakah user adalah Sekretaris (case-insensitive)
        $jabatanPuk = session('anggota.jabatan_puk', '');
        if (strtolower($jabatanPuk) !== 'sekretaris') {
            abort(403, 'Hanya Sekretaris yang bisa mengubah visi dan misi.');
        }

        $validated = $request->validate([
            'visi' => ['required', 'string', 'max:500'],
            'misi' => ['required', 'array', 'min:1'],
            'misi.*' => ['required', 'string', 'max:255'],
            'sejarah_singkat' => ['nullable', 'string', 'max:1000'],
            'periode_kepengurusan' => ['nullable', 'string', 'max:50'],
            'alamat_sekretariat' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = OrganizationProfile::first();
        
        if ($profile) {
            $profile->update($validated);
        } else {
            OrganizationProfile::create($validated);
        }

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Visi dan Misi berhasil diperbarui.');
    }
}