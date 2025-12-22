<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\AppsScriptService;

class UserController extends Controller
{
    public function __construct(private AppsScriptService $api) {}

    public function index()
    {
        $res = $this->api->request('get', ['action'=>'getUsers']);
        $users = $res['success'] ? $res['data']['data'] ?? [] : [];
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username'=>'required',
            'email'=>'required|email',
            'password'=>'required',
            'role'=>'required'
        ]);

        $this->api->request('post', array_merge(
            ['action'=>'addUser'],
            $request->only('username','email','password','role')
        ), true);

        return redirect()->route('users.index');
    }

    public function update(Request $request, $username)
    {
        $payload = [
            'action'   => 'editUser',
            'username' => $username,
            'email'    => $request->email,
            'role'     => $request->role
        ];

        if ($request->password) {
            $payload['password'] = $request->password;
        }

        $this->api->request('post', $payload, true);
        return back();
    }

    public function destroy($username)
    {
        $this->api->request('post', [
            'action'=>'deleteUser',
            'username'=>$username
        ], true);

        return redirect()->route('users.index');
    }
}