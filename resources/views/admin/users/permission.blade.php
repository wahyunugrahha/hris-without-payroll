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
                    <h2 class="page-title">Manajemen Permission</h2>
                    <p class="page-subtitle">Izin dengan pola <code>modul-aksi</code>, dikelompokkan otomatis di halaman Role.</p>
                </div>
                @can('permissions-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate" aria-label="Tambah permission">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Permission</span>
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar permission">
                <div class="summary-strip" role="group" aria-label="Filter panel">
                    <button type="button" class="summary-item summary-tab is-current" data-guard="">
                        <span class="summary-label">Semua</span>
                        <span class="summary-value">{{ $permissions->count() }}</span>
                    </button>
                    @foreach ($panelAkses as $guard => $label)
                        <button type="button" class="summary-item summary-tab" data-guard="{{ $guard }}">
                            <span class="summary-label">{{ $label }}</span>
                            <span class="summary-value">{{ $permissions->where('guard_name', $guard)->count() }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="list-toolbar">
                    <div class="list-toolbar-row">
                        <label class="search-field">
                            <span class="visually-hidden">Cari permission</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                            <input type="search" id="permission-search" placeholder="Cari nama permission…" autocomplete="off">
                        </label>
                    </div>
                </div>

                @if ($permissions->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Belum ada permission.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Modul</th>
                                    <th>Panel akses</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $permission)
                                    <tr data-perm-row data-nama="{{ strtolower($permission->name) }}" data-guard="{{ $permission->guard_name }}">
                                        <td class="cell-person"><span class="person-name cell-num">{{ $permission->name }}</span></td>
                                        <td data-label="Modul" class="text-capitalize">{{ str_replace('_', ' ', explode('-', $permission->name, 2)[0]) }}</td>
                                        <td data-label="Panel">
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::PANEL_AKSES[$permission->guard_name] ?? 'slate' }}">{{ $panelAkses[$permission->guard_name] ?? $permission->guard_name }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            @canany(['permissions-edit-admin', 'permissions-delete-admin'])
                                                <x-admin.row-menu :label="$permission->name">
                                                    @can('permissions-edit-admin')
                                                        <button type="button" class="dropdown-item btn-edit-permission" data-id="{{ $permission->id }}"
                                                            data-nama="{{ $permission->name }}" data-guard="{{ $permission->guard_name }}">Edit</button>
                                                    @endcan
                                                    @can('permissions-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('users.permissions.destroy', $permission->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                data-confirm="Permission “{{ $permission->name }}” dicabut dari semua role."
                                                                data-confirm-title="Hapus permission?">Hapus</button>
                                                        </form>
                                                    @endcan
                                                </x-admin.row-menu>
                                            @endcanany
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="list-empty" id="permission-kosong" hidden>
                        <p class="mb-0 text-secondary small">Tidak ada permission yang cocok.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @foreach ([
        ['modalCreate', 'Tambah permission', route('users.permissions.store'), false],
        ['modalEdit', 'Edit permission', '', true],
    ] as [$idModal, $judul, $aksi, $edit])
        <div class="modal modal-blur fade" id="{{ $idModal }}" tabindex="-1" aria-labelledby="judul-{{ $idModal }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="judul-{{ $idModal }}">{{ $judul }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form method="POST" action="{{ $aksi }}" class="modal-form-body" id="form-{{ $idModal }}">
                        @csrf
                        @if ($edit)
                            @method('PUT')
                        @endif
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="nama-{{ $idModal }}">Nama permission</label>
                                <input type="text" class="form-control cell-num" name="name" id="nama-{{ $idModal }}" placeholder="modul-aksi-admin" required>
                                <div class="form-hint">Format: <code>modul-aksi</code>, mis. <code>cuti-create-admin</code>.</div>
                            </div>
                            <div>
                                <label class="form-label required" for="guard-{{ $idModal }}">Panel akses</label>
                                <select name="guard_name" id="guard-{{ $idModal }}" class="form-select" required>
                                    @foreach ($panelAkses as $guard => $label)
                                        <option value="{{ $guard }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('myscript')
    <script>
        $(function() {
            let guardKini = '';

            function saring() {
                const q = ($('#permission-search').val() || '').toLowerCase().trim();
                let tampil = 0;
                $('[data-perm-row]').each(function() {
                    const cocok = this.dataset.nama.includes(q) && (!guardKini || this.dataset.guard === guardKini);
                    this.hidden = !cocok;
                    tampil += cocok;
                });
                $('#permission-kosong').prop('hidden', tampil > 0);
            }

            $('#permission-search').on('input', saring);
            $('.summary-tab').on('click', function() {
                guardKini = $(this).data('guard');
                $('.summary-tab').removeClass('is-current').removeAttr('aria-pressed');
                $(this).addClass('is-current').attr('aria-pressed', 'true');
                saring();
            });

            const urlUpdate = "{{ route('users.permissions.update', '__ID__') }}";
            $('.btn-edit-permission').on('click', function() {
                const d = $(this).data();
                $('#form-modalEdit').attr('action', urlUpdate.replace('__ID__', d.id));
                $('#nama-modalEdit').val(d.nama);
                $('#guard-modalEdit').val(d.guard);
                $('#modalEdit').modal('show');
            });
        });
    </script>
@endpush
