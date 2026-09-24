@extends('layouts.admin.tabler')

@php
    $filterAktif = collect([request('guard_name'), request('role_id'), request('nama_jabatan')])->filter(fn ($v) => filled($v))->count();
    $panelAkses = ['user' => 'Admin Panel', 'karyawan' => 'Karyawan (Mobile)'];
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Jabatan</h2>
                    <p class="page-subtitle">Jabatan menentukan role & hak akses karyawan.</p>
                </div>
                @can('jabatan-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-inputjabatan"
                            aria-label="Tambah jabatan">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Jabatan</span>
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
            <section class="card list-card" aria-label="Daftar jabatan">
                <form action="{{ route('jabatan.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_jabatan" placeholder="Cari nama jabatan…" label="Cari jabatan" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Panel akses</span>
                            <select name="guard_name" id="guard_filter" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($panelAkses as $guard => $label)
                                    <option value="{{ $guard }}" @selected(request('guard_name') == $guard)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Role</span>
                            <select name="role_id" id="role_filter" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}" @selected(request('role_id') == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        @if ($filterAktif > 0)
                            <a href="{{ route('jabatan.index') }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($jabatan->total() > 0)
                        Menampilkan <strong>{{ $jabatan->firstItem() }}–{{ $jabatan->lastItem() }}</strong>
                        dari <strong>{{ $jabatan->total() }}</strong> jabatan
                    @endif
                </div>

                @if ($jabatan->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada jabatan yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            Ubah kata kunci atau filter
                            @if ($filterAktif > 0)
                                — atau <a href="{{ route('jabatan.index') }}">reset filter</a>
                            @endif.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Role</th>
                                    <th>Panel akses</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jabatan as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name">{{ $data->nama_jabatan }}</span>
                                        </td>
                                        <td data-label="Role">{{ $data->role->name ?? '-' }}</td>
                                        <td data-label="Panel akses">
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::PANEL_AKSES[$data->role->guard_name ?? ''] ?? 'slate' }}">
                                                {{ $panelAkses[$data->role->guard_name ?? ''] ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="cell-actions">
                                            @canany(['jabatan-edit-admin', 'jabatan-delete-admin'])
                                                <x-admin.row-menu :label="$data->nama_jabatan">
                                                    @can('jabatan-edit-admin')
                                                        <button type="button" class="dropdown-item edit-jabatan" data-id="{{ $data->id }}">Edit</button>
                                                    @endcan
                                                    @can('jabatan-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger delete-confirm-jabatan"
                                                            data-id="{{ $data->id }}" data-nama="{{ $data->nama_jabatan }}">Hapus</button>
                                                    @endcan
                                                </x-admin.row-menu>
                                                @can('jabatan-delete-admin')
                                                    <form id="deleteFormJabatan{{ $data->id }}" method="POST"
                                                        action="{{ route('jabatan.delete', $data->id) }}" hidden>
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endcan
                                            @endcanany
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($jabatan->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $jabatan->currentPage() }} dari {{ $jabatan->lastPage() }}</span>
                        {{ $jabatan->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('jabatan-create-admin')
        <div class="modal modal-blur fade" id="modal-inputjabatan" tabindex="-1" aria-labelledby="judulTambahJabatan" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahJabatan">Tambah Jabatan</h5>
                            <p class="modal-subtitle">Role menentukan hak akses pemegang jabatan ini.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('jabatan.store') }}" method="POST" id="formJabatan" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="nama_jabatan">Nama jabatan</label>
                                <input type="text" class="form-control" name="nama_jabatan" id="nama_jabatan"
                                    value="{{ old('nama_jabatan') }}" placeholder="Contoh: Supervisor Produksi" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="guard_select">Panel akses</label>
                                <select name="guard_name" class="form-select" id="guard_select" required>
                                    <option value="">Pilih panel akses</option>
                                    @foreach ($panelAkses as $guard => $label)
                                        <option value="{{ $guard }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label required" for="role_id">Role</label>
                                <select name="role_id" class="form-select" id="role_id" required>
                                    <option value="">Pilih role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}" @selected(old('role_id') == $role->id)>
                                            {{ $role->name }}
                                        </option>
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
    @endcan

    <div class="modal modal-blur fade" id="modal-editjabatan" tabindex="-1" aria-labelledby="judulEditJabatan" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditJabatan">Edit Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadedEditFormJabatan" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            // Role yang bisa dipilih mengikuti panel akses.
            function applyRoleGuardFilter(targetSelect, guardName, reset) {
                $(targetSelect + ' option').each(function() {
                    let optionGuard = $(this).data('guard');
                    $(this).toggle(!guardName || optionGuard == guardName || $(this).val() == '');
                });
                if (reset) $(targetSelect).val('');
            }

            $('#guard_select').on('change', function() {
                applyRoleGuardFilter('#role_id', $(this).val(), true);
            });
            // Filter daftar: sembunyikan role dari panel lain (tanpa mereset pilihan dari URL).
            applyRoleGuardFilter('#role_filter', $('#guard_filter').val(), false);

            function validateJabatanForm(e, isEdit) {
                let nama = $(isEdit ? '#nama_jabatan_edit' : '#nama_jabatan').val().trim();
                let role = $(isEdit ? '#role_id_edit' : '#role_id').val();

                if (!nama) {
                    e.preventDefault();
                    Swal.fire('Periksa isian', 'Nama jabatan harus diisi.', 'warning');
                    return false;
                }
                if (!role) {
                    e.preventDefault();
                    Swal.fire('Periksa isian', 'Role harus dipilih.', 'warning');
                    return false;
                }
                return true;
            }

            $('#formJabatan').on('submit', function(e) {
                return validateJabatanForm(e, false);
            });

            $(document).on('submit', '#formJabatanEdit', function(e) {
                return validateJabatanForm(e, true);
            });

            $('.edit-jabatan').on('click', function() {
                let id = $(this).data('id');
                $.get('/jabatan/' + id + '/edit', function(data) {
                    $('#loadedEditFormJabatan').html(data);
                    $('#modal-editjabatan').modal('show');
                }).fail(function() {
                    Swal.fire('Gagal', 'Form edit jabatan tidak dapat dimuat.', 'error');
                });
            });

            // Hapus: tampilkan dulu data yang bergantung pada jabatan ini.
            $('.delete-confirm-jabatan').on('click', function() {
                let id = $(this).data('id');
                hapusDenganRelasi({
                    url: '/jabatan/' + id + '/relations',
                    jenis: 'jabatan',
                    nama: $(this).data('nama'),
                    form: document.getElementById('deleteFormJabatan' + id),
                    kosongkan: { karyawan: 'karyawan', user: 'user admin' },
                    ikutTerhapus: { kpi: 'master KPI' }
                });
            });
        });
    </script>
@endpush
