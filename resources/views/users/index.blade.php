@extends('layouts.app')
@section('title', 'User Management | PUK OMRON')
@section('content')
<div class="p-4 sm:p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-semibold">User Management</h1>
        <button onclick="openAddModal()" class="px-4 py-2 bg-blue-500 text-white rounded">Tambah User</button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border text-gray-700 dark:text-gray-700">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-2 px-4 border">Username</th>
                    <th class="py-2 px-4 border">Email</th>
                    <th class="py-2 px-4 border">Password</th>
                    <th class="py-2 px-4 border">Role</th>
                    <th class="py-2 px-4 border">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="py-2 px-4 border">{{ $user['Username'] }}</td>
                    <td class="py-2 px-4 border">{{ $user['Email'] }}</td>
                    <td class="py-2 px-4 border">{{ $user['Password']}}</td>
                    <td class="py-2 px-4 border">{{ $user['Role'] }}</td>
                    <td class="py-2 px-4 border flex gap-2">
                        <button onclick="openEditModal('{{ $user['Username'] }}')" class="px-2 py-1 bg-yellow-400 rounded">Edit</button>
                        <form action="{{ route('users.destroy', $user['Username']) }}" method="POST" class="inline" onsubmit="showDeleteSpinner(this)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 bg-red-500 text-white rounded flex items-center justify-center gap-1">
                                <span class="spinner hidden w-3 h-3 border-2 border-t-white border-gray-200 rounded-full animate-spin"></span>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded w-96">
        <h2 id="modalTitle" class="text-lg font-semibold mb-4text-gray-700 dark:text-gray-700">Tambah User</h2>
        <form id="userForm" method="POST" onsubmit="showSaveSpinner(this)">
            @csrf
            <div class="mb-2 text-gray-700 dark:text-gray-700">
                <label>Username</label>
                <input type="text" name="username" class="w-full border rounded px-2 py-1 text-gray-700 dark:text-gray-700">
            </div>
            <div class="mb-2 text-gray-700 dark:text-gray-700">
                <label>Email</label>
                <input type="email" name="email" class="w-full border rounded px-2 py-1  text-gray-700 dark:text-gray-700">
            </div>
            <div class="mb-2 text-gray-700 dark:text-gray-700">
                <label>Password</label>
                <input type="password" name="password" class="w-full border rounded px-2 py-1 text-gray-700 dark:text-gray-700">
            </div>
            <div class="mb-4 text-gray-700 dark:text-gray-700">
                <label>Role</label>
                <select name="role" class="w-full border rounded px-2 py-1 text-gray-700 dark:text-gray-700">
                      <option value="">-- Pilih Bidang --</option>
                    <option>Ketua</option>
                    <option>Sekretaris</option>
                    <option>Bendahara</option>
                    <option>Pengurus</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-3 py-1 bg-gray-300 rounded">Batal</button>
                <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded flex items-center gap-1 justify-center">
                    <span class="spinner hidden w-3 h-3 border-2 border-t-white border-gray-200 rounded-full animate-spin"></span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = "Tambah User";
    const form = document.getElementById('userForm');
    form.action = "{{ route('users.store') }}";
    form.reset();
    document.getElementById('userModal').classList.remove('hidden');
}

function openEditModal(username) {
    document.getElementById('modalTitle').innerText = "Edit User";
    const user = @json($users);
    const data = user.find(u => u.Username === username);
    const form = document.getElementById('userForm');
    form.action = "/users/update/" + username;
    form.username.value = data.Username;
    form.email.value = data.Email;
    form.password.value = "";
    form.role.value = data.Role;
    document.getElementById('userModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function showDeleteSpinner(form) {
    const btn = form.querySelector('button');
    btn.querySelector('.spinner').classList.remove('hidden');
    btn.disabled = true; // disable tombol saat spinner aktif
}

function showSaveSpinner(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.querySelector('.spinner').classList.remove('hidden');
    btn.disabled = true; // disable tombol saat spinner aktif
}
</script>
@endsection
