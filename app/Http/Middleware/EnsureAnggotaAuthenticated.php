<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\PermissionHelper; // tambahkan

class EnsureAnggotaAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek session login
        if (! session()->has('anggota')) {
            return redirect()->route('anggota.login')
                ->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        // 2. Cek permission akses admin
        if (! PermissionHelper::can('access_admin')) {
            // Logout agar tidak tersesat
            session()->forget('anggota');
            return redirect()->route('anggota.login')
                ->with('error', 'Anda tidak memiliki izin untuk mengakses admin panel.');
        }

        return $next($request);
    }
}