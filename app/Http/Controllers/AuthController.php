<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Services\AppsScriptService;

class AuthController extends Controller
{
    public function __construct(private AppsScriptService $api) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function processLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $res = $this->api->request('post', [
            'action'   => 'login',
            'username' => $request->username,
            'password' => $request->password,
        ], true);

        if (!$res['success']) {
            return back()->withErrors(['login' => $res['message']]);
        }

        if (!($res['data']['success'] ?? false)) {
            return back()->withErrors(['login' => 'Username atau password salah']);
        }

        session(['user' => $res['data']['user']]);
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login');
    }
}