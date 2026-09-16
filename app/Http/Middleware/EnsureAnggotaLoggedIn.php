<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memproteksi route publik yang hanya boleh diakses anggota yang sudah login
 * lewat AnggotaAuthController (session('anggota')) — bukan session('user')
 * yang dipakai untuk dashboard staff.
 *
 * Daftarkan di app/Http/Kernel.php:
 *   protected $middlewareAliases = [
 *       ...
 *       'anggota.auth' => \App\Http\Middleware\EnsureAnggotaLoggedIn::class,
 *   ];
 */
class EnsureAnggotaLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('anggota')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Silakan login sebagai anggota terlebih dahulu.'], 401);
            }

            return redirect()
                ->route('anggota.login')
                ->with('error', 'Silakan login sebagai anggota terlebih dahulu.');
        }

        return $next($request);
    }
}