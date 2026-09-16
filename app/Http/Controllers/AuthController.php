<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function processLogin(Request $request)
    {
        $request->validate([
            'login' => 'required', // field login bisa username atau NIK
            'password' => 'required'
        ]);

        // Cari user berdasarkan username atau NIK karyawan
        $user = User::where('username', $request->login)
                    ->orWhere('nik_karyawan', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['login' => 'Username/NIK atau password salah']);
        }

        // Simpan session
        session(['user' => $user->only(['id','username','email','role','ttd','nik_karyawan'])]);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login');
    }
}