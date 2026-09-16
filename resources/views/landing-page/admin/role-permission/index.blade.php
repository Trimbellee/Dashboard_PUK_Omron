@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-role-permission.css'])
@endpush

@section('title', 'Peran & Izin')

@section('content')
<div class="rp-wrapper">
    {{-- HEADER --}}
    <div class="rp-header">
        <div>
            <h1 class="rp-header__title">Peran &amp; Izin</h1>
            <p class="rp-header__sub">
                Kelola hak akses setiap peran (role) terhadap fitur admin.
                Hanya Sekretaris yang dapat mengubah pengaturan ini.
            </p>
        </div>
        <div class="rp-stats">
            <span class="rp-stats__item">
                <strong>{{ count($allRoles) }}</strong> Peran
            </span>
            <span class="rp-stats__item">
                <strong>{{ count($permissions) }}</strong> Izin
            </span>
        </div>
    </div>

    {{-- FORM --}}
    <form action="{{ route('admin.role-permission.update') }}" method="POST" id="role-permission-form">
        @csrf
        @method('PUT')

        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th scope="col" style="min-width:120px;">Jabatan</th>
                        @foreach($permissions as $key => $label)
                            <th scope="col" class="vertical-label">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($allRoles as $role)
                        <tr>
                            <th scope="row">{{ $role }}</th>
                            @foreach($permissions as $key => $label)
                                <td>
                                    <label class="rp-check" title="{{ $label }}">
                                        <input type="checkbox"
                                               name="permissions[{{ $role }}][]"
                                               value="{{ $key }}"
                                               @checked(in_array($key, $existing[$role] ?? []))
                                               aria-label="Berikan izin '{{ $label }}' untuk peran '{{ $role }}'">
                                        <span class="rp-check__box"></span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($permissions) + 1 }}" style="text-align:center; padding:40px 0; color:var(--text-secondary);">
                                Belum ada peran yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('role-permission-form');
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    let saveTimeout = null;

    function savePermissions() {
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message || '✅ Perubahan berhasil disimpan');
                } else {
                    showToast('error', data.message || '❌ Terjadi kesalahan');
                }
            })
            .catch(() => {
                showToast('error', '❌ Gagal menyimpan perubahan');
            });
        }, 300);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', savePermissions);
    });

    // Optional: Tambahkan indikator scroll horizontal
    const tableWrap = document.querySelector('.rp-table-wrap');
    if (tableWrap) {
        tableWrap.addEventListener('scroll', function() {
            // Tambahkan class saat discroll untuk efek shadow
            if (this.scrollLeft > 0) {
                this.classList.add('is-scrolled');
            } else {
                this.classList.remove('is-scrolled');
            }
        });
    }
});
</script>
@endpush