<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Anggota;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function getAnggotaByNik($nik)
    {
        try {
            $anggota = Anggota::where('nik_karyawan', $nik)->first();
            
            if (!$anggota) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIK tidak ditemukan'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'nama_lengkap' => $anggota->nama_lengkap,
                    'email' => $anggota->email,
                    'nik_karyawan' => $anggota->nik_karyawan,
                    'foto' => $anggota->foto_url ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getAnggotaByNik: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nik_karyawan' => 'required|unique:users,nik_karyawan',
                'username' => 'required|unique:users,username',
                'email' => 'required|email',
                'password' => 'required|min:6',
                'role' => 'required',
                'ttd' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $ttdPath = null;
            if ($request->hasFile('ttd')) {
                $ttdPath = $request->file('ttd')->store('ttd', 'public');
            }

            $user = User::create([
                'nik_karyawan' => $request->nik_karyawan,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'ttd' => $ttdPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan',
                'user' => [
                    'nik_karyawan' => $user->nik_karyawan,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'ttd_url' => $user->ttd ? asset('storage/' . $user->ttd) : null,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error store user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $username)
    {
        try {
            $user = User::where('username', $username)->firstOrFail();

            $validated = $request->validate([
                'email' => 'required|email',
                'role' => 'required',
                'ttd' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $user->email = $request->email;
            $user->role = $request->role;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('ttd')) {
                if ($user->ttd) {
                    Storage::disk('public')->delete($user->ttd);
                }
                $user->ttd = $request->file('ttd')->store('ttd', 'public');
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui',
                'data' => [
                    'id' => $user->id,
                    'nik_karyawan' => $user->nik_karyawan,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'ttd_url' => $user->ttd ? asset('storage/' . $user->ttd) : null,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error update user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($username)
    {
        try {
            $user = User::where('username', $username)->firstOrFail();

            if ($user->ttd) {
                Storage::disk('public')->delete($user->ttd);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus',
                'username' => $username
            ]);

        } catch (\Exception $e) {
            Log::error('Error destroy user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request, $username)
    {
        try {
            $user = User::where('username', $username)->firstOrFail();

            $request->validate([
                'email' => 'required|email',
                'ttd' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('ttd')) {
                if ($user->ttd) {
                    Storage::disk('public')->delete($user->ttd);
                }
                $user->ttd = $request->file('ttd')->store('ttd', 'public');
            }

            $user->save();

            session()->put('user.email', $request->email);
            session()->put('user.ttd', $user->ttd);

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'email' => $request->email,
                'ttd_url' => $user->ttd ? asset('storage/' . $user->ttd) : null,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updateProfile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }
}