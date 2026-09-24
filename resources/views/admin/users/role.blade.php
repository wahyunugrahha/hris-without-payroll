@extends('layouts.admin.tabler')

@php
    $panelAkses = ['user' => 'Admin Panel', 'karyawan' => 'Karyawan (Mobile)'];
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Role & Hak Akses</h2>
                    <p class="page-subtitle">Atur izin tiap role per modul sistem.</p>
                </div>
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate" aria-label="Buat role">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                            stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg><span class="d-none d-sm-inline">Buat Role</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar role">
                <div class="list-toolbar">
                    <div class="list-toolbar-row">
                        <label class="search-field">
                            <span class="visually-hidden">Cari role</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                            <input type="search" id="role-search" placeholder="Cari role…" autocomplete="off">
                        </label>
                    </div>
                </div>

                @if ($roles->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Belum ada role.</p>
                        <p class="mb-0 text-secondary small">Buat role lalu pilih izinnya.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Panel akses</th>
                                    <th class="text-end">Izin aktif</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr data-role-row data-nama="{{ strtolower($role->name) }}">
                                        <td class="cell-person"><span class="person-name text-capitalize">{{ $role->name }}</span></td>
                                        <td data-label="Panel akses">
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::PANEL_AKSES[$role->guard_name] ?? 'slate' }}">{{ $panelAkses[$role->guard_name] ?? $role->guard_name }}</span>
                                        </td>
                                        <td data-label="Izin" class="cell-num text-lg-end">{{ $role->permissions->count() }}</td>
                                        <td class="cell-actions">
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#edit{{ $role->id }}">Kelola akses</button>
                                                <x-admin.row-menu :label="$role->name">
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit{{ $role->id }}">Kelola akses</button>
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('users.roles.destroy', $role->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"
                                                            data-confirm="Role “{{ $role->name }}” akan dihapus permanen."
                                                            data-confirm-title="Hapus role?">Hapus</button>
                                                    </form>
                                                </x-admin.row-menu>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="list-empty" id="role-kosong" hidden>
                        <p class="mb-0 text-secondary small">Tidak ada role yang cocok.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @foreach ($roles as $role)
        <div class="modal modal-blur fade" id="edit{{ $role->id }}" tabindex="-1" aria-labelledby="judulRole{{ $role->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulRole{{ $role->id }}">Kelola akses</h5>
                            <p class="modal-subtitle">{{ $panelAkses[$role->guard_name] ?? $role->guard_name }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form method="POST" action="{{ route('users.roles.update', $role->id) }}" class="modal-form-body">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="nama-role-{{ $role->id }}">Nama role</label>
                                <input class="form-control" name="name" id="nama-role-{{ $role->id }}" value="{{ $role->name }}" required>
                            </div>
                            @include('admin.users._permission-matrix', [
                                'izin' => $permissions->where('guard_name', $role->guard_name),
                                'dimiliki' => $role->permissions->pluck('name')->flip()->all(),
                            ])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal modal-blur fade" id="modalCreate" tabindex="-1" aria-labelledby="judulBuatRole" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulBuatRole">Buat role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('users.roles.store') }}" class="modal-form-body">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid mb-3">
                            <div>
                                <label class="form-label required" for="nama-role-baru">Nama role</label>
                                <input class="form-control" name="name" id="nama-role-baru" placeholder="Contoh: HRD Manager" required>
                            </div>
                            <div>
                                <label class="form-label required" for="guard_select_create">Panel akses</label>
                                <select name="guard_name" class="form-select" id="guard_select_create" required>
                                    @foreach ($panelAkses as $guard => $label)
                                        <option value="{{ $guard }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @foreach (array_keys($panelAkses) as $g)
                            <div class="guard-perms" id="perms-{{ $g }}" @if ($g !== 'user') hidden @endif>
                                @include('admin.users._permission-matrix', ['izin' => $permissions->where('guard_name', $g), 'dimiliki' => []])
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        document.addEventListener('change', function(e) {
            const matriks = e.target.closest('[data-perm-matrix]');
            if (!matriks) return;
            const baris = e.target.closest('.perm-row');

            if (e.target.matches('.row-master-check')) {
                baris.querySelectorAll('.perm-item-check').forEach((c) => c.checked = e.target.checked);
            }
            if (e.target.matches('.perm-item-check')) {
                baris.querySelector('.row-master-check').checked =
                    baris.querySelectorAll('.perm-item-check:checked').length === baris.querySelectorAll('.perm-item-check').length;
            }
            if (e.target.matches('.global-master-check')) {
                matriks.querySelectorAll('.row-master-check, .perm-item-check').forEach((c) => c.checked = e.target.checked);
            }
        });

        // Form buat role: hanya izin panel terpilih yang tampil & terkirim.
        document.getElementById('guard_select_create').addEventListener('change', function() {
            document.querySelectorAll('.guard-perms').forEach((el) => {
                const aktif = el.id === 'perms-' + this.value;
                el.hidden = !aktif;
                el.querySelectorAll('input[type="checkbox"]').forEach((c) => {
                    c.checked = false;
                    c.disabled = !aktif;
                });
            });
        });
        document.getElementById('guard_select_create').dispatchEvent(new Event('change'));

        document.getElementById('role-search')?.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let tampil = 0;
            document.querySelectorAll('[data-role-row]').forEach((tr) => {
                const cocok = tr.dataset.nama.includes(q);
                tr.hidden = !cocok;
                tampil += cocok;
            });
            document.getElementById('role-kosong').hidden = tampil > 0;
        });
    </script>
@endpush
