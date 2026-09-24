@extends('layouts.admin.tabler')

@php
    $inisial = fn ($nama) => collect(explode(' ', trim($nama)))->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
    $filterAktif = collect([request('kode_cabang'), request('name')])->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Manajemen User Admin</h2>
                    <p class="page-subtitle">Akun HR, HR cabang, dan SPV yang bisa masuk ke panel admin.</p>
                </div>
                @can('users-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="btnTambahUser" data-bs-toggle="modal" data-bs-target="#modal-inputuser" aria-label="Tambah user">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah User</span>
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
            <section class="card list-card" aria-label="Daftar user admin">
                <form action="{{ route('users.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="name" placeholder="Cari nama user…" label="Cari user" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Cabang</span>
                            <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($cabang as $c)
                                    <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </label>
                        @if ($filterAktif > 0)
                            <a href="{{ route('users.index') }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($users->total() > 0)
                        Menampilkan <strong>{{ $users->firstItem() }}–{{ $users->lastItem() }}</strong>
                        dari <strong>{{ $users->total() }}</strong> user
                    @endif
                </div>

                @if ($users->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada user yang cocok.</p>
                        <p class="mb-0 text-secondary small">Ubah kata kunci atau filter.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Jabatan</th>
                                    <th>Departemen</th>
                                    <th>Cabang</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person">
                                                <span class="avatar avatar-sm">{{ $inisial($data->name) }}</span>
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $data->name }}">{{ $data->name }}</span>
                                                    <span class="person-sub">{{ $data->email }}</span>
                                                </span>
                                            </span>
                                        </td>
                                        <td data-label="Jabatan">{{ $data->jabatan->nama_jabatan ?? '-' }}</td>
                                        <td data-label="Departemen">{{ $data->nama_dept ?? '-' }}</td>
                                        <td data-label="Cabang">{{ $data->nama_cabang ?? 'Semua cabang' }}</td>
                                        <td class="cell-actions">
                                            @canany(['users-edit-admin', 'users-delete-admin'])
                                                <x-admin.row-menu :label="$data->name">
                                                    @can('users-edit-admin')
                                                        <button type="button" class="dropdown-item edit-user" data-id="{{ $data->id }}">Edit</button>
                                                    @endcan
                                                    @can('users-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('users.destroy', ['id' => $data->id]) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                data-confirm="User {{ $data->name }} tidak bisa lagi masuk ke panel admin."
                                                                data-confirm-title="Hapus user?">Hapus</button>
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
                @endif

                @if ($users->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</span>
                        {{ $users->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('users-create-admin')
        <div class="modal modal-blur fade" id="modal-inputuser" tabindex="-1" aria-labelledby="judulTambahUser" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahUser">Tambah user admin</h5>
                            <p class="modal-subtitle">Hak akses mengikuti role dari jabatan yang dipilih.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('users.store') }}" method="POST" id="formUser" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            @include('admin.users._fields', ['user' => null, 'sufiks' => ''])
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

    <div class="modal modal-blur fade" id="modal-edituser" tabindex="-1" aria-labelledby="judulEditUser" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditUser">Edit user admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadededitformuser" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        function validateUserForm(e, isEdit) {
            const sfx = isEdit ? '_edit' : '';
            const val = (id) => ($('#' + id + sfx).val() || '').trim();

            function peringatan(text, id) {
                e.preventDefault();
                Swal.fire({ title: 'Periksa isian', text: text, icon: 'warning', confirmButtonText: 'Ok' })
                    .then(() => $('#' + id + sfx).focus());
                return false;
            }

            if (val('name') === '') return peringatan('Nama user harus diisi.', 'name');
            if (val('email') === '') return peringatan('Email harus diisi.', 'email');
            if (!isEdit && $('#password').val() === '') return peringatan('Password harus diisi.', 'password');
            if (val('kode_dept') === '') return peringatan('Departemen harus dipilih.', 'kode_dept');
            if (val('jabatan_id') === '') return peringatan('Jabatan harus dipilih.', 'jabatan_id');
            return true;
        }

        $(function() {
            $('#formUser').on('submit', (e) => validateUserForm(e, false));
            $(document).on('submit', '#formUserEdit', (e) => validateUserForm(e, true));

            $('.edit-user').on('click', function() {
                $.get('/users/' + $(this).data('id') + '/edit', function(data) {
                    $('#loadededitformuser').html(data);
                    $('#modal-edituser').modal('show');
                }).fail(() => Swal.fire('Gagal', 'Form edit user tidak dapat dimuat.', 'error'));
            });
        });
    </script>
@endpush
