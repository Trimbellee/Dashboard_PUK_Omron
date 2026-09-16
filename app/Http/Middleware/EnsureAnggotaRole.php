<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnggotaRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if (! session()->has('anggota')) {
            return redirect()
                ->route('anggota.login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        $jabatan = strtolower(trim(session('anggota.jabatan_puk', '')));

        $allowedRoles = array_map(
            fn ($role) => strtolower(trim($role)),
            $allowedRoles
        );

        if (! in_array($jabatan, $allowedRoles, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}