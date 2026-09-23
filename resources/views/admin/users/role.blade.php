@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title text-dark">Konfigurasi Role & Hak Akses</h2>
                    <div class="text-muted mt-1 small">Atur otoritas pengguna berdasarkan modul sistem</div>
                </div>
                <div class="col-auto ms-auto d-flex align-items-center gap-2">
                    <div class="d-none d-sm-block" style="min-width:260px;">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                    <path d="M21 21l-6 -6" />
                                </svg>
                            </span>
                            <input type="text" id="role-search" class="form-control" placeholder="Cari role...">
                        </div>
                    </div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg>
                        Buat Role Baru
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                @forelse ($roles as $role)
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-stacked">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="subheader text-uppercase fw-bold text-primary">{{ $role->name }}</div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle text-muted" href="#"
                                                data-bs-toggle="dropdown">Aksi</a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#edit{{ $role->id }}">Edit Otoritas</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete{{ $role->id }}">Hapus Role</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-2">
                                    <div class="text-muted small mb-2">
                                        Hak Akses:
                                        <span class="text-{{ $role->guard_name == 'user' ? 'blue' : 'green' }} fw-bold">
                                            {{ $role->guard_name == 'user' ? 'ADMIN' : 'KARYAWAN' }}
                                        </span>
                                    </div>
                                    <div class="text-muted small mb-2">Izin Aktif: <span
                                            class="badge bg-blue-lt">{{ $role->permissions->count() }}</span></div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent p-0">
                                <button class="btn btn-ghost-primary w-100 border-0 rounded-0 py-2" data-bs-toggle="modal"
                                    data-bs-target="#edit{{ $role->id }}">
                                    Kelola Detail Akses
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL EDIT --}}
                    <div class="modal modal-blur fade" id="edit{{ $role->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form method="POST" action="{{ route('users.roles.update', $role->id) }}"
                                class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Konfigurasi Akses: {{ $role->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nama Role</label>
                                        <input class="form-control" name="name" value="{{ $role->name }}" required>
                                    </div>

                                    {{-- Tampilan Grid Permission Seperti Gambar --}}
                                    <div class="table-responsive border rounded">
                                        <table class="table table-vcenter card-table table-striped"
                                            id="tableEdit{{ $role->id }}">
                                            <thead>
                                                <tr>
                                                    <th style="width: 5%">
                                                        <input type="checkbox" class="form-check-input global-master-check">
                                                    </th>
                                                    <th>Menu / Fitur</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $filteredPerms = $permissions->where(
                                                        'guard_name',
                                                        $role->guard_name,
                                                    );
                                                    $rolePermissionNames = $role->permissions
                                                        ->pluck('name')
                                                        ->flip();
                                                    $grouped = [];
                                                    foreach ($filteredPerms as $perm) {
                                                        $parts = explode('-', $perm->name, 2);
                                                        $action = $parts[1];
                                                        $resource = $parts[0] ?? $perm->name;
                                                        $grouped[$resource][] = [
                                                            'name' => $perm->name,
                                                            'action' => $action,
                                                        ];
                                                    }
                                                @endphp
                                                @foreach ($grouped as $resource => $actions)
                                                    @php
                                                        $isAllChecked = collect($actions)->every(
                                                            fn($a) => isset($rolePermissionNames[$a['name']]),
                                                        );
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center" style="width: 50px;">
                                                            <input type="checkbox"
                                                                class="form-check-input row-master-check"
                                                                {{ $isAllChecked ? 'checked' : '' }}>
                                                        </td>
                                                        <td class="fw-bold text-dark text-capitalize">
                                                            {{ str_replace('-', ' ', $resource) }}</td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-3 justify-content-start">
                                                                @foreach ($actions as $act)
                                                                    <div class="d-flex align-items-center">
                                                                        <label class="form-check form-switch m-0">
                                                                            <input class="form-check-input perm-item-check"
                                                                                type="checkbox" name="permissions[]"
                                                                                value="{{ $act['name'] }}"
                                                                                {{ isset($rolePermissionNames[$act['name']]) ? 'checked' : '' }}>
                                                                            <span
                                                                                class="form-check-label small">{{ $act['action'] }}</span>
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link link-secondary me-auto"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- MODAL DELETE --}}
                    <div class="modal modal-blur fade" id="delete{{ $role->id }}" tabindex="-1">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                            <div class="modal-content">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                                <div class="modal-status bg-danger"></div>
                                <div class="modal-body text-center py-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg"
                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 9v2m0 4v.01" />
                                        <path
                                            d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                                    </svg>
                                    <h3>Apakah Anda yakin?</h3>
                                    <div class="text-muted">Anda akan menghapus role <strong>{{ $role->name }}</strong>.
                                        Data yang sudah dihapus tidak dapat dikembalikan.</div>
                                </div>
                                <div class="modal-footer">
                                    <div class="w-100">
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn w-100" data-bs-dismiss="modal">Batal</a>
                                            </div>
                                            <div class="col">
                                                <form action="{{ route('users.roles.destroy', $role->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger w-100">Hapus</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Belum ada role yang terdaftar.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div class="modal modal-blur fade" id="modalCreate" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('users.roles.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Role Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nama Role</label>
                            <input class="form-control" name="name" placeholder="Contoh: HRD Manager" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Akses Platform</label>
                            <select name="guard_name" class="form-select" id="guard_select_create" required>
                                <option value="user">Admin Panel</option>
                                <option value="karyawan">Karyawan</option>
                            </select>
                        </div>
                    </div>

                    <div id="permission_container_create">
                        @foreach (['user', 'karyawan'] as $g)
                            <div class="guard-perms" id="perms-{{ $g }}"
                                style="display: {{ $g == 'user' ? 'block' : 'none' }}">
                                <div class="table-responsive border rounded">
                                    <table class="table table-vcenter card-table table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">
                                                    <input type="checkbox" class="form-check-input global-master-check">
                                                </th>
                                                <th>Fitur ({{ strtoupper($g) }})</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $gPerms = $permissions->where('guard_name', $g);
                                                $gGrouped = [];
                                                foreach ($gPerms as $p) {
                                                    $parts = explode('-', $p->name, 2);
                                                    $gGrouped[$parts[0] ?? $p->name][] = [
                                                        'name' => $p->name,
                                                        'action' => $parts[1],
                                                    ];
                                                }
                                            @endphp
                                            @foreach ($gGrouped as $res => $acts)
                                                @php
                                                    $allChecked = false;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input row-master-check">
                                                    </td>
                                                    <td class="fw-bold text-capitalize">{{ str_replace('-', ' ', $res) }}
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-3">
                                                            @foreach ($acts as $a)
                                                                <label class="form-check form-switch m-0">
                                                                    <input class="form-check-input perm-item-check"
                                                                        type="checkbox" name="permissions[]"
                                                                        value="{{ $a['name'] }}">
                                                                    <span
                                                                        class="form-check-label small">{{ $a['action'] }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Role</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('row-master-check')) {
                    const row = e.target.closest('tr');
                    const switches = row.querySelectorAll('.perm-item-check');
                    switches.forEach(sw => sw.checked = e.target.checked);
                }

                if (e.target.classList.contains('perm-item-check')) {
                    const row = e.target.closest('tr');
                    const master = row.querySelector('.row-master-check');
                    const allItems = row.querySelectorAll('.perm-item-check');
                    const checkedItems = row.querySelectorAll('.perm-item-check:checked');

                    if (master) {
                        master.checked = (allItems.length === checkedItems.length);
                    }
                }

                if (e.target.classList.contains('global-master-check')) {
                    const table = e.target.closest('table');
                    const allCheckboxes = table.querySelectorAll('.row-master-check, .perm-item-check');
                    allCheckboxes.forEach(c => c.checked = e.target.checked);
                }
            });

            const guardSelect = document.getElementById('guard_select_create');
            if (guardSelect) {
                guardSelect.addEventListener('change', function() {
                    document.querySelectorAll('.guard-perms').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.guard-perms input[type="checkbox"]').forEach(el => el
                        .checked = false);
                    const targetId = 'perms-' + this.value;
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) targetEl.style.display = 'block';
                });
            }

            const roleInput = document.getElementById('role-search');
            if (roleInput) {
                roleInput.addEventListener('input', function() {
                    const q = this.value.toLowerCase();
                    document.querySelectorAll('.row-cards > div').forEach(div => {
                        const name = div.querySelector('.subheader')?.textContent.toLowerCase() ||
                            '';
                        div.style.display = name.includes(q) ? '' : 'none';
                    });
                });
            }
        });
    </script>
@endpush
