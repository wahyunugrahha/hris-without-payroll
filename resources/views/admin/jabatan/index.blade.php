@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Jabatan</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @can('jabatan-create-admin')
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                                data-bs-target="#modal-inputjabatan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Jabatan
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                @if ($errors->any())
                                    {{-- Validation errors are handled globally, but we keep this here if you want a local list --}}
                                @endif
                            </div>

                            <div class="card-body border-bottom py-3 px-0">
                                <form action="{{ route('jabatan.index') }}" method="GET" id="filterFormJabatan">
                                    <div class="row g-2">
                                        {{-- 1. Filter Panel Akses --}}
                                        <div class="col-md-3">
                                            <select name="guard_name" class="form-select" id="guard_filter">
                                                <option value="">Semua Akses</option>
                                                <option value="user"
                                                    {{ request('guard_name') == 'user' ? 'selected' : '' }}>
                                                    Admin Panel</option>
                                                <option value="karyawan"
                                                    {{ request('guard_name') == 'karyawan' ? 'selected' : '' }}>Karyawan
                                                    (Mobile)</option>
                                            </select>
                                        </div>

                                        {{-- 2. Filter Role --}}
                                        <div class="col-md-3">
                                            <select name="role_id" class="form-select" id="role_filter">
                                                <option value="">Semua Role</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}"
                                                        {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- 3. Pencarian & Submit --}}
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" name="nama_jabatan" id="nama_jabatan_search"
                                                    value="{{ request('nama_jabatan') }}" class="form-control"
                                                    placeholder="Cari nama jabatan..." aria-label="Cari nama jabatan">

                                                <button type="submit" class="btn btn-primary" aria-label="Cari">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-search" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <circle cx="10" cy="10" r="7" />
                                                        <line x1="21" y1="21" x2="15" y2="15" />
                                                    </svg>
                                                    Cari
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-vcenter table-mobile-md card-table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Jabatan</th>
                                            <th>Role</th>
                                            <th>Panel Akses</th>
                                            <th class="w-1">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($jabatan as $index => $data)
                                            <tr>
                                                <td class="text-muted">{{ $loop->iteration + $jabatan->firstItem() - 1 }}
                                                </td>
                                                <td data-label="Jabatan">
                                                    <div class="fw-bold text-dark">{{ $data->nama_jabatan }}</div>
                                                </td>
                                                <td>
                                                    @php
                                                        $roleName = $data->role->name ?? 'None';
                                                        $guardName = $data->role->guard_name ?? '-';
                                                        $textColor = match (strtolower($roleName)) {
                                                            'administrator' => 'text-danger',
                                                            'hrd' => 'text-azure',
                                                            'spv' => 'text-indigo',
                                                            'staff' => 'text-success',
                                                            default => 'text-muted',
                                                        };
                                                    @endphp
                                                    <span class="{{ $textColor }} fw-bold text-uppercase"
                                                        style="font-size: 0.75rem;">
                                                        {{ $roleName }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small text-uppercase">
                                                        {{ $guardName == 'user' ? 'Admin Panel' : 'Karyawan' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-list flex-nowrap text-nowrap">
                                                        @can('jabatan-edit-admin')
                                                            <a href="#"
                                                                class="btn btn-ghost-primary btn-icon edit-jabatan"
                                                                data-id="{{ $data->id }}" data-bs-toggle="tooltip"
                                                                data-bs-placement="top" title="Edit Data">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="icon icon-tabler icon-tabler-pencil" width="24"
                                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                                    stroke="currentColor" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <path
                                                                        d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                    <path d="M13.5 6.5l4 4" />
                                                                </svg>
                                                            </a>
                                                        @endcan

                                                        @can('jabatan-delete-admin')
                                                            <button
                                                                class="btn btn-ghost-danger btn-icon delete-confirm-jabatan"
                                                                data-id="{{ $data->id }}"
                                                                data-nama="{{ $data->nama_jabatan }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                title="Hapus Data">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="icon icon-tabler icon-tabler-trash" width="24"
                                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                                    stroke="currentColor" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <path d="M4 7l16 0" />
                                                                    <path d="M10 11l0 6" />
                                                                    <path d="M14 11l0 6" />
                                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                </svg>
                                                            </button>
                                                            <form id="deleteFormJabatan{{ $data->id }}" method="POST"
                                                                action="{{ route('jabatan.delete', $data->id) }}">
                                                                @csrf @method('DELETE')
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-secondary py-5">
                                                    <div class="empty">
                                                        <p class="empty-title">Data tidak ditemukan</p>
                                                        <p class="empty-subtitle text-secondary">
                                                            Coba sesuaikan pencarian Anda untuk menemukan apa yang Anda
                                                            cari.
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer d-flex align-items-center bg-transparent">
                                <p class="m-0 text-secondary">
                                    Menampilkan <strong>{{ $jabatan->firstItem() ?? 0 }}</strong> -
                                    <strong>{{ $jabatan->lastItem() ?? 0 }}</strong> dari
                                    <strong>{{ $jabatan->total() }}</strong> data
                                </p>
                                <div class="ms-auto">
                                    {{ $jabatan->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Jabatan --}}
        <div class="modal modal-blur fade" id="modal-inputjabatan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jabatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('jabatan.store') }}" method="POST" id="formJabatan">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Jabatan</label>
                                <input type="text" class="form-control" name="nama_jabatan"
                                    value="{{ old('nama_jabatan') }}" placeholder="Masukkan nama jabatan" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Panel Akses</label>
                                <select name="guard_name" class="form-select" id="guard_select" required>
                                    <option value="">Pilih Akses User</option>
                                    <option value="user">Admin Panel</option>
                                    <option value="karyawan">Karyawan (Mobile)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_id" class="form-select" id="role_id" required>
                                    <option value="">Pilih Role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}"
                                            {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Edit Jabatan --}}
        <div class="modal modal-blur fade" id="modal-editjabatan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jabatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="loadedEditFormJabatan">
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('myscript')
        <script>
            $(function() {
                // Filter Role berdasarkan Guard yang dipilih pada Modal Tambah (Exisiting)
                $('#guard_select').on('change', function() {
                    let selectedGuard = $(this).val();
                    applyRoleGuardFilter('#role_id', selectedGuard);
                });

                // Filter Role berdasarkan Guard yang dipilih pada Form Pencarian (New)
                $('#guard_filter').on('change', function() {
                    let selectedGuard = $(this).val();
                    applyRoleGuardFilter('#role_filter', selectedGuard);
                });

                function applyRoleGuardFilter(targetSelect, guardName) {
                    $(targetSelect + ' option').each(function() {
                        let optionGuard = $(this).data('guard');
                        if (!guardName || optionGuard == guardName || $(this).val() == "") {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    $(targetSelect).val(""); // Reset pilihan role
                }

                function validateJabatanForm(e, isEdit = false) {
                    let nama = isEdit ? $('#nama_jabatan_edit').val().trim() : $('#nama_jabatan').val().trim();
                    let role = isEdit ? $('#role_id_edit').val() : $('#role_id').val();

                    if (!nama) {
                        e.preventDefault();
                        Swal.fire('Warning', 'Nama jabatan harus diisi!', 'warning');
                        return false;
                    }
                    if (!role) {
                        e.preventDefault();
                        Swal.fire('Warning', 'Role harus dipilih!', 'warning');
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

                $('.edit-jabatan').on('click', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.get('/jabatan/' + id + '/edit', function(data) {
                        $('#loadedEditFormJabatan').html(data);
                        $('#modal-editjabatan').modal('show');
                    }).fail(function() {
                        Swal.fire('Error', 'Gagal memuat form edit jabatan', 'error');
                    });
                });

                $('.delete-confirm-jabatan').on('click', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    let nama = $(this).data('nama');

                    // Tampilkan loading SweetAlert
                    Swal.fire({
                        title: 'Memeriksa Data...',
                        text: 'Silakan tunggu sementara sistem menganalisis keterkaitan data.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch data relasi
                    $.get('/jabatan/' + id + '/relations', function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            let rels = response.relations;
                            let hasRelations = (rels.karyawan > 0 || rels.user > 0 || rels.kpi > 0);
                            
                            let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus jabatan <b>${nama}</b>?</p>`;
                            
                            if (hasRelations) {
                                messageHtml += `
                                    <div class="alert alert-warning text-start mb-0">
                                        <h6 class="alert-heading mb-1 font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Data Terkait Terdeteksi:</h6>
                                        <ul class="pl-3 mb-0" style="list-style-type: disc;">
                                `;
                                
                                // Tampilkan data set null (karyawan, user)
                                if (rels.karyawan > 0 || rels.user > 0) {
                                    messageHtml += `<li class="mb-1 text-dark">Data berikut akan <b>dikosongkan</b> dan perlu diatur ulang nantinya:`;
                                    messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                    if (rels.karyawan > 0) messageHtml += `<li><b>${rels.karyawan}</b> Karyawan</li>`;
                                    if (rels.user > 0) messageHtml += `<li><b>${rels.user}</b> User Admin</li>`;
                                    messageHtml += `</ul></li>`;
                                }
                                
                                // Tampilkan data cascade (kpi)
                                if (rels.kpi > 0) {
                                    messageHtml += `<li class="text-danger">Data berikut akan <b>ikut terhapus secara permanen</b>:`;
                                    messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                    messageHtml += `<li><b>${rels.kpi}</b> Data KPI Master</li>`;
                                    messageHtml += `</ul></li>`;
                                }
                                
                                messageHtml += `
                                        </ul>
                                    </div>
                                `;
                            } else {
                                messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada data lain yang bergantung pada jabatan ini.</p>`;
                            }

                            Swal.fire({
                                title: 'Hapus Data Jabatan?',
                                html: messageHtml,
                                icon: hasRelations ? 'warning' : 'question',
                                showCancelButton: true,
                                confirmButtonColor: 'var(--color-danger)',
                                cancelButtonColor: 'var(--color-muted)',
                                confirmButtonText: 'Ya, Hapus!',
                                cancelButtonText: 'Batal',
                                reverseButtons: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $('#deleteFormJabatan' + id).submit();
                                }
                            });
                        } else {
                            Swal.fire('Error', 'Gagal memproses analisis keterkaitan data.', 'error');
                        }
                    }).fail(function() {
                        Swal.close();
                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                    });
                });
            });
        </script>
    @endpush
