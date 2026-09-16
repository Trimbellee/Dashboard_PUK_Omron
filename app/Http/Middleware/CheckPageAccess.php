<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use App\Models\AdminPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return $next($request);
        }

        // 'admin.berita.index' -> segmen ke-2 ('berita') dicocokkan ke AdminPage::slug
        $segments = explode('.', $routeName);
        $slug = $segments[1] ?? null;

        // Role & Permission sengaja tidak lewat sistem ini (dijaga middleware role terpisah)
        if (! $slug || $slug === 'role-permission') {
            return $next($request);
        }

        $page = AdminPage::where('slug', $slug)->active()->first();

        // Slug tidak terdaftar di admin_pages (mis. rute pendukung seperti media.list) -> biarkan lewat
        if (! $page) {
            return $next($request);
        }

        if (! PermissionHelper::can($slug)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}