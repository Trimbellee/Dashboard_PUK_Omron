@extends('layouts.app')
@section('title', 'User Management | PUK OMRON')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

{{-- HEADER --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="bg-blue-800 text-white p-2.5 rounded-xl shadow-lg shadow-blue-900/40">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </span>
            <span class="text-blue-800 dark:text-blue-300">
                User Management
            </span>
        </h1>
        <p class="text-sm text-gray-700 dark:text-gray-400 mt-1 font-medium">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
            Kelola akun pengguna sistem
        </p>
    </div>

    <button
        onclick="openAddModal()"
        class="inline-flex items-center gap-2.5 px-6 py-3 bg-blue-800 hover:bg-blue-900 text-white font-semibold rounded-xl shadow-lg shadow-blue-900/40 hover:shadow-blue-950/50 transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        <span>Tambah User</span>
    </button>
</div>

{{-- TABLE --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="userTable" class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">NIK</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TTD</th>
                    <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @foreach($users as $user)
                <tr
                    class="user-row border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all duration-200"
                    data-username="{{ $user->username }}"
                    data-nik="{{ $user->nik_karyawan }}"
                    data-email="{{ $user->email }}"
                    data-role="{{ $user->role }}"
                    data-ttd="{{ $user->ttd ? asset('storage/'.$user->ttd) : '' }}"
                >
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                {{ strtoupper(substr($user->username, 0, 2)) }}
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $user->username }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300 font-mono text-xs">{{ $user->nik_karyawan ?? '-' }}</td>
                    <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                            @if($user->role == 'Ketua') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                            @elseif($user->role == 'Sekretaris' || $user->role == 'Bendahara') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                            @elseif(str_contains($user->role, 'Bidang')) bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endif
                        ">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if($user->ttd)
                            <img
                                src="{{ asset('storage/'.$user->ttd) }}"
                                class="ttd-thumb mx-auto cursor-pointer hover:scale-110 transition-transform duration-200"
                                onclick="previewTtd('{{ asset('storage/'.$user->ttd) }}')"
                                alt="TTD {{ $user->username }}"
                            >
                        @else
                            <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                onclick="openEditModal('{{ $user->username }}')"
                                class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200 hover:scale-110"
                                title="Edit User"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                onclick="openDeleteModal('{{ $user->username }}')"
                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200 hover:scale-110"
                                title="Delete User"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

</div>

{{-- ================= MODAL ADD / EDIT ================= --}}
<div
    id="userModal"
    class="modal-overlay hidden"
>
    <div
        class="modal-content-box"
        onclick="event.stopPropagation()"
    >
        <div class="modal-header-custom">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 dark:text-white">Tambah User</h2>
            </div>
            <button
                type="button"
                onclick="closeModal()"
                class="modal-close-btn"
            >
                ✕
            </button>
        </div>

        <form id="userForm" method="POST" class="space-y-4">
            @csrf

            {{-- NIK KARYAWAN --}}
            <div class="form-group">
                <label class="form-label">NIK Karyawan</label>
                <div class="relative">
                    <input
                        type="text"
                        name="nik_karyawan"
                        id="nikKaryawan"
                        class="form-input"
                        placeholder="Masukkan NIK karyawan"
                        required
                    >
                    <button
                        type="button"
                        id="checkNikBtn"
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                    >
                        Cek
                    </button>
                </div>
                <div id="nikStatus" class="text-xs mt-1 hidden"></div>
            </div>

            {{-- USERNAME --}}
            <div class="form-group">
                <label class="form-label">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-input"
                    placeholder="Username akan auto-fill dari nama lengkap"
                    required
                >
            </div>

            {{-- EMAIL --}}
            <div class="form-group">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-input"
                    placeholder="Email akan auto-fill dari data anggota"
                    required
                >
                <p id="emailHint" class="text-xs text-gray-400 mt-1 hidden">* Email tidak ditemukan di data anggota, silahkan isi manual</p>
            </div>

            {{-- PASSWORD --}}
            <div class="form-group">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    id="passwordInput"
                    class="form-input"
                    placeholder="Password wajib diisi"
                >
                <p id="passwordHint" class="password-hint hidden">
                    * Isi hanya jika ingin mengganti password
                </p>
            </div>

            {{-- ROLE --}}
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-input" required>
                    <option value="">-- Pilih Role --</option>
                    <option>Ketua</option>
                    <option>Sekretaris</option>
                    <option>Bendahara</option>
                    <option>Wakil Ketua Bidang 1</option>
                    <option>Wakil Sekretaris Bidang 1</option>
                    <option>Wakil Ketua Bidang 2</option>
                    <option>Wakil Sekretaris Bidang 2</option>
                    <option>Wakil Ketua Bidang 3</option>
                    <option>Wakil Sekretaris Bidang 3</option>
                    <option>Wakil Ketua Bidang 4</option>
                    <option>Wakil Sekretaris Bidang 4</option>
                    <option>Wakil Ketua Bidang 5</option>
                    <option>Wakil Sekretaris Bidang 5</option>
                    <option>Wakil Ketua Bidang 6</option>
                    <option>Wakil Sekretaris Bidang 6</option>
                    <option>PANGKORLAP</option>
                    <option>PANGKORMI</option>
                    <option>Sekretaris PANGKORLAP</option>
                    <option>Sekretaris PANGKORMI</option>
                </select>
            </div>

            {{-- TANDA TANGAN --}}
            <div class="form-group">
                <label class="form-label">Tanda Tangan (TTD)</label>
                <div class="signature-box">
                    <canvas id="signatureCanvas"></canvas>
                    <div id="signatureEmptyHint" class="signature-empty-hint">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            <span class="text-sm">Gambar tanda tangan di sini</span>
                        </div>
                    </div>
                </div>
                <div class="signature-actions">
                    <button
                        type="button"
                        class="btn-ghost-sm"
                        onclick="clearSignature()"
                    >
                        🧹 Hapus Coretan
                    </button>
                    <span id="signatureStatus" class="signature-status"></span>
                </div>
                <p class="password-hint mt-2">
                    * Kosongkan / jangan gambar apa pun jika tidak ingin mengubah tanda tangan
                </p>
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    onclick="closeModal()"
                    class="btn btn-ghost"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <span class="spinner hidden"></span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================= MODAL DELETE ================= --}}
<div
    id="deleteModal"
    class="modal-overlay hidden"
>
    <div
        class="modal-content-box delete-box"
        onclick="event.stopPropagation()"
    >
        <div class="delete-icon">⚠️</div>
        <h2 class="delete-title">Hapus User?</h2>
        <p class="delete-description">
            User <strong id="deleteUsername"></strong> akan dihapus permanen.
        </p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-actions center">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="btn btn-ghost"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-danger"
                >
                    <span id="deleteText">Hapus</span>
                    <span id="deleteSpinner" class="spinner hidden"></span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================= LIBRARY EKSTERNAL ================= --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

{{-- ================= CSS & JS HALAMAN INI ================= --}}
@vite(['resources/css/dashboard/users.css', 'resources/js/dashboard/users.js'])

@endsection