<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use App\Models\RolePermission;
use App\Models\StrukturOrganisasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    /**
     * Action-permission granular (bukan halaman, tapi aksi di dalam halaman).
     * Ini yang masih perlu di-maintain manual karena bergantung ke logic fitur.
     */
    private const ACTION_PERMISSIONS = [
        'access_admin'     => 'Akses Admin Panel',
        'create_news'      => 'Membuat Berita',
        'create_gallery'   => 'Membuat Galeri',
        'review_news'      => 'Review Berita',
        'review_gallery'   => 'Review Galeri',
        'approve_news'     => 'Approval Berita',
        'approve_gallery'  => 'Approval Galeri',
    ];

    public function index(): View
    {
        $allRoles = StrukturOrganisasi::distinct()
            ->whereNotNull('jabatan')
            ->pluck('jabatan')
            ->toArray();

        // Halaman (page-level access) diambil otomatis dari admin_pages
        $pagePermissions = AdminPage::active()
            ->pluck('label', 'slug')
            ->toArray();

        // Gabungkan: akses halaman + aksi granular di dalamnya
        $permissions = $pagePermissions + self::ACTION_PERMISSIONS;

        $existing = RolePermission::all()->groupBy('role')->map(function ($items) {
            return $items->pluck('permission')->toArray();
        });

        return view('landing-page.admin.role-permission.index', [
            'allRoles'    => $allRoles,
            'permissions' => $permissions,
            'existing'    => $existing,
        ]);
    }

    public function update(Request $request)
    {
        // Validasi 'in:...' sekarang dinamis, ikut daftar permission yang valid saat ini
        $validKeys = array_merge(
            AdminPage::active()->pluck('slug')->toArray(),
            array_keys(self::ACTION_PERMISSIONS)
        );

        $validated = $request->validate([
            'permissions'      => ['nullable', 'array'],
            'permissions.*'    => ['nullable', 'array'],
            'permissions.*.*'  => ['nullable', 'string', 'in:' . implode(',', $validKeys)],
        ]);

        RolePermission::truncate();

        if (!empty($validated['permissions'])) {
            foreach ($validated['permissions'] as $role => $perms) {
                foreach ($perms as $perm) {
                    RolePermission::create([
                        'role'       => $role,
                        'permission' => $perm,
                    ]);
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perubahan berhasil disimpan',
            ]);
        }

        return redirect()
            ->route('admin.role-permission.index')
            ->with('success', 'Pengaturan peran berhasil diperbarui.');
    }
}