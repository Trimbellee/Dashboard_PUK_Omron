<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthCheck
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('user') &&
        !$request->is('pengajuan/export-excel')
        ) {
            return redirect('dashboard/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}
