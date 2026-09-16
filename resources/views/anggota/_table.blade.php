
<div id="ajaxMeta"
     data-total="{{ $anggota->total() }}"
     data-sort="{{ $sort ?? 'nama_lengkap' }}"
     data-dir="{{ $dir ?? 'asc' }}"
     style="display:none;">
</div>

<div style="background:var(--bg-surface);border:1px solid var(--border);
            border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);">

    <div style="overflow:auto;max-height:75vh;">
        <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">

            <thead style="position:sticky;top:0;z-index:20;">
                <tr style="background:var(--bg-elevated);text-align:left;">

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);
                               border-bottom:1px solid var(--border);">
                        <button class="sort-btn"
                            data-sort="nik_karyawan"
                            style="display:inline-flex;align-items:center;gap:4px;
                                   background:none;border:none;cursor:pointer;
                                   font-weight:600;font-size:inherit;
                                   color:var(--text-secondary);padding:0;
                                   font-family:inherit;">
                            NIK
                            @if(($sort ?? '') === 'nik_karyawan')
                                <span>{{ ($dir ?? '') === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);
                               border-bottom:1px solid var(--border);">
                        <button class="sort-btn"
                            data-sort="nama_lengkap"
                            style="display:inline-flex;align-items:center;gap:4px;
                                   background:none;border:none;cursor:pointer;
                                   font-weight:600;font-size:inherit;
                                   color:var(--text-secondary);padding:0;
                                   font-family:inherit;">
                            Nama
                            @if(($sort ?? '') === 'nama_lengkap')
                                <span>{{ ($dir ?? '') === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);
                               border-bottom:1px solid var(--border);">
                        <button class="sort-btn"
                            data-sort="departemen"
                            style="display:inline-flex;align-items:center;gap:4px;
                                   background:none;border:none;cursor:pointer;
                                   font-weight:600;font-size:inherit;
                                   color:var(--text-secondary);padding:0;
                                   font-family:inherit;">
                            Departemen
                            @if(($sort ?? '') === 'departemen')
                                <span>{{ ($dir ?? '') === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);
                               border-bottom:1px solid var(--border);">
                        <button class="sort-btn"
                            data-sort="jabatan"
                            style="display:inline-flex;align-items:center;gap:4px;
                                   background:none;border:none;cursor:pointer;
                                   font-weight:600;font-size:inherit;
                                   color:var(--text-secondary);padding:0;
                                   font-family:inherit;">
                            Jabatan
                            @if(($sort ?? '') === 'jabatan')
                                <span>{{ ($dir ?? '') === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);
                               border-bottom:1px solid var(--border);">
                        <button class="sort-btn"
                            data-sort="status_karyawan"
                            style="display:inline-flex;align-items:center;gap:4px;
                                   background:none;border:none;cursor:pointer;
                                   font-weight:600;font-size:inherit;
                                   color:var(--text-secondary);padding:0;
                                   font-family:inherit;">
                            Status
                            @if(($sort ?? '') === 'status_karyawan')
                                <span>{{ ($dir ?? '') === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th style="padding:14px 24px;font-weight:600;
                               color:var(--text-secondary);text-align:center;
                               border-bottom:1px solid var(--border);">
                        Action
                    </th>

                </tr>
            </thead>

            <tbody>
                @forelse($anggota as $item)
                <tr style="border-top:1px solid var(--border);transition:background .12s;"
                    onmouseover="this.style.background='var(--bg-elevated)'"
                    onmouseout="this.style.background=''">

                    <td style="padding:14px 24px;">
                        <div style="font-weight:500;color:var(--text-primary);">
                            {{ $item->nik_karyawan }}
                        </div>
                    </td>

                    <td style="padding:14px 24px;">
                        <div style="font-weight:600;color:var(--text-primary);">
                            {{ $item->nama_lengkap }}
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                            {{ $item->jenis_kelamin }}
                        </div>
                    </td>

                    <td style="padding:14px 24px;color:var(--text-secondary);">
                        {{ $item->departemen }}
                    </td>

                    <td style="padding:14px 24px;color:var(--text-secondary);">
                        {{ $item->jabatan }}
                    </td>

                    <td style="padding:14px 24px;color:var(--text-secondary);">
                        {{ $item->status_karyawan }}
                    </td>

                    <td style="padding:14px 24px;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">

                            <a href="{{ route('anggota.edit', $item) }}"
                                style="padding:6px 12px;border-radius:8px;font-size:11px;
                                       font-weight:500;background:rgba(234,179,8,.12);
                                       color:#92400E;text-decoration:none;
                                       transition:background .12s;"
                                onmouseover="this.style.background='rgba(234,179,8,.22)'"
                                onmouseout="this.style.background='rgba(234,179,8,.12)'">
                                Edit
                            </a>
				
			    @if(in_array(session('user')['role'], ['Sekretaris','Bidang 1']))
                            <button type="button"
                                onclick="confirmHapus('{{ route('anggota.destroy', $item) }}', '{{ addslashes($item->nama_lengkap) }}')"
                                style="padding:6px 12px;border-radius:8px;font-size:11px;
                                    font-weight:500;background:rgba(239,68,68,.1);
                                    color:#B91C1C;border:none;cursor:pointer;
                                    transition:background .12s;font-family:inherit;"
                                onmouseover="this.style.background='rgba(239,68,68,.2)'"
                                onmouseout="this.style.background='rgba(239,68,68,.1)'">
                                Hapus
                            </button>
			    @endif

                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6"
                        style="padding:48px 24px;text-align:center;
                               color:var(--text-muted);">
                        Tidak ada data anggota
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    @if($anggota->hasPages())
        <div style="padding:14px 24px;border-top:1px solid var(--border);">
            {{ $anggota->links() }}
        </div>
    @endif

</div>

{{-- PER PAGE --}}
<div style="margin-top:12px;display:flex;align-items:center;gap:8px;">
    <label for="perPageSelect"
        style="font-size:0.875rem;color:var(--text-secondary);">
        Per halaman:
    </label>
    <select id="perPageSelect" class="form-input" style='width:auto;
                    padding:2px 14px 2px 10px;
                    font-size:12.5px;
                    cursor:pointer;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2714%27 height=%2714%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 6px center;'>
        <option value="10"  {{ $perPage == 10  ? 'selected' : '' }}>10</option>
        <option value="20"  {{ $perPage == 20  ? 'selected' : '' }}>20</option>
        <option value="50"  {{ $perPage == 50  ? 'selected' : '' }}>50</option>
        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
    </select>
</div>