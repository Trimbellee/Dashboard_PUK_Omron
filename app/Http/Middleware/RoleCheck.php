<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\RoleHelper;

class RoleCheck
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = session('user');

        if (!$user) {
            abort(403, 'Session user tidak ditemukan.');
        }

        // Gunakan key 'role', sesuai struktur session
        $userRole = $user['role'] ?? null;

        if (!$userRole) {
            abort(403, 'Role pengguna tidak ditemukan.');
        }

        if (!RoleHelper::isInRoles($userRole, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}