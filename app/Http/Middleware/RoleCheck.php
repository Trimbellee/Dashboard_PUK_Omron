<?php

namespace App\Http\Middleware;

use Closure;

class RoleCheck
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = session('user');

        if (!$user || !in_array($user['Role'], $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
