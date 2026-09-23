@extends('layouts.admin.tabler')
@section('page-header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Data HR, HR Cabang & SPV</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    @can('users-create-admin')
                        <a href="#" class="btn btn-primary" id="btnTambahUser" data-bs-toggle="modal"
                            data-bs-target="#modal-inputuser">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>Tambah Data</a>
                    @endcan
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
                                <div class="col-12">
                                    @if (Session::get('success'))
                                        <div class="alert alert-success">
                                            {{ Session::get('success') }}
                                        </div>
                                    @endif

                                    @if (Session::get('warning'))
                                        <div class="alert alert-warning">
                                            {{ Session::get('warning') }}
                                        </div>
                                    @endif

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-12">
                                    <form action="{{ route('users.index') }}" method="GET">
                                        <div class="row g-2">
                                            {{-- 1. Filter Cabang --}}
                                            <div class="col-12 col-md-4">
                                                <select name="kode_cabang" class="form-select">
                                                    <option value="">Semua Cabang</option>
                                                    @foreach ($cabang as $c)
                                                        <option value="{{ $c->kode_cabang }}"
                                                            {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                                            {{ $c->nama_cabang }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- 2. Pencarian Nama & Tombol --}}
                                            <div class="col-12 col-md-8">
                                                <div class="input-group">
                                                    <input type="text" name="name" id="name_search"
                                                        class="form-control" placeholder="Cari Nama HR, HR Cabang, SPV"
                                                        value="{{ request()->name }}">

                                                    <button type="submit" class="btn btn-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                            height="20" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                            <path d="M21 21l-6 -6" />
                                                        </svg>
                                                        <span class="ms-1">Cari Data</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-vcenter">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama</th>
                                                    <th>Email</th>
                                                    <th>Departemen</th>
                                                    <th>Cabang</th>
                                                    <th>Jabatan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($users as $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $data->name }}</td>
                                                        <td>{{ $data->email }}</td>
                                                        <td>{{ $data->nama_dept }}</td>
                                                        <td>{{ $data->nama_cabang ?? '-' }}</td>
                                                        <td>
                                                            <span class="text-blue">
                                                                {{ strtoupper($data->jabatan->nama_jabatan ?? '-') }}
                                                            </span>
                                                        </td>

                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                @can('users-edit-admin')
                                                                    <a href="#"
                                                                        class="edit-user btn btn-ghost-primary btn-icon"
                                                                        data-id="{{ $data->id }}">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="icon icon-tabler icon-tabler-pencil"
                                                                            width="24" height="24" viewBox="0 0 24 24"
                                                                            stroke-width="2" stroke="currentColor"
                                                                            fill="none" stroke-linecap="round"
                                                                            stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                fill="none" />
                                                                            <path
                                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                            <path d="M13.5 6.5l4 4" />
                                                                        </svg>
                                                                    </a>
                                                                @endcan

                                                                @can('users-delete-admin')
                                                                    <a href="#"
                                                                        class="delete-confirm-user btn btn-ghost-danger btn-icon"
                                                                        data-id="{{ $data->id }}"
                                                                        data-name="{{ $data->name }}">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="icon icon-tabler icon-tabler-trash"
                                                                            width="24" height="24" viewBox="0 0 24 24"
                                                                            stroke-width="2" stroke="currentColor"
                                                                            fill="none" stroke-linecap="round"
                                                                            stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                fill="none" />
                                                                            <path d="M4 7l16 0" />
                                                                            <path d="M10 11l0 6" />
                                                                            <path d="M14 11l0 6" />
                                                                            <path
                                                                                d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                            <path
                                                                                d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                        </svg>
                                                                    </a>

                                                                    <form
                                                                        action="{{ route('users.destroy', ['id' => $data->id]) }}"
                                                                        method="POST"
                                                                        id="deleteFormUser{{ $data->id }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center justify-content-md-end">
                                        {{ $users->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-inputuser" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('users.store') }}" method="POST" id="formUser">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="name" value="{{ old('name') }}"
                                        class="form-control" name="name" placeholder="Nama User"
                                        data-field="Nama User">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-mail">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                            <path d="M3 7l9 6l9 -6" />
                                        </svg>
                                    </span>
                                    <input type="text" id="email" value="{{ old('email') }}"
                                        class="form-control" name="email" placeholder="Email" data-field="Email">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-lock">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" />
                                            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
                                            <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                                        </svg>
                                    </span>
                                    <input type="password" id="password" value="{{ old('password') }}"
                                        class="form-control" name="password" placeholder="Password"
                                        data-field="Password">
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="form-group">
                                            <select name="kode_dept" id="kode_dept" class="form-select">
                                                <option value="">Departemen</option>
                                                @foreach ($departemen as $d)
                                                    <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="form-group">
                                            <select name="jabatan_id" id="jabatan_id" class="form-select">
                                                <option value="">Pilih Jabatan</option>
                                                @foreach ($jabatan as $j)
                                                    <option value="{{ $j->id }}">
                                                        {{ strtoupper($j->nama_jabatan) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="form-group">
                                            <select name="kode_cabang" id="kode_cabang" class="form-select">
                                                <option value="">Cabang (untuk Admin Cabang)</option>
                                                @foreach ($cabang as $c)
                                                    <option value="{{ $c->kode_cabang }}">{{ $c->nama_cabang }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="icon icon-tabler icons-tabler-outline icon-tabler-send">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M10 14l11 -11" />
                                                <path
                                                    d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" />
                                            </svg>
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-edituser" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="loadededitformuser">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function onlyNumberInput(event) {
            let key = event.key;
            if (!/^\d$/.test(key) && !event.metaKey && !event.ctrlKey && key.length === 1) {
                event.preventDefault();
            }
        }

        function onlyTextInput(event) {
            let key = event.key;
            const isAllowedKey = /^[a-zA-Z\s]$/.test(key);
            const isControlKey = event.metaKey || event.ctrlKey || key.length !== 1;

            if (!isAllowedKey && !isControlKey) {
                event.preventDefault();
            }
        }

        function validateUserForm(e, isEdit = false) {

            const name = isEdit ? $('#name_edit').val().trim() : $('#name').val().trim();
            const email = isEdit ? $('#email_edit').val().trim() : $('#email').val().trim();
            const password = isEdit ? $('#password_edit').val() : $('#password').val();
            const kode_dept = isEdit ? $('#kode_dept_edit').val().trim() : $('#kode_dept').val().trim();
            const jabatan_id = isEdit ? $('#jabatan_id_edit').val().trim() : $('#jabatan_id').val().trim();

            function showWarning(text, el) {
                e.preventDefault();
                Swal.fire({
                    title: 'Warning',
                    text: text,
                    icon: 'warning',
                    confirmButtonText: 'Ok'
                }).then(() => {
                    el.focus();
                });
                return false;
            }

            if (name === "")
                return showWarning('Nama user harus diisi!', isEdit ? $('#name_edit') : $('#name'));

            if (email === "")
                return showWarning('Email harus diisi!', isEdit ? $('#email_edit') : $('#email'));

            if (!isEdit && password === "")
                return showWarning('Password harus diisi!', $('#password'));

            if (kode_dept === "")
                return showWarning('Departemen harus dipilih!', isEdit ? $('#kode_dept_edit') : $('#kode_dept'));

            if (jabatan_id === "")
                return showWarning('Jabatan harus dipilih!', isEdit ? $('#jabatan_id_edit') : $('#jabatan_id'));

            return true;
        }

        $(function() {

            // SUBMIT TAMBAH USER
            $('#formUser').on('submit', function(e) {
                return validateUserForm(e, false);
            });

            // LOAD FORM EDIT USER (AJAX)
            $('.edit-user').on('click', function(e) {
                e.preventDefault();

                let id = $(this).data('id');

                $.get('/users/' + id + '/edit', function(data) {
                    $('#loadededitformuser').html(data);
                    $('#modal-edituser').modal('show');
                }).fail(function() {
                    Swal.fire(
                        'Error',
                        'Gagal memuat form edit user.',
                        'error'
                    );
                });
            });

            // SUBMIT EDIT USER
            $(document).on('submit', '#formUserEdit', function(e) {
                return validateUserForm(e, true);
            });

            // DELETE USER
            $('.delete-confirm-user').on('click', function(e) {
                e.preventDefault();

                let id = $(this).data('id');
                let name = $(this).data('name');

                Swal.fire({
                    title: 'Hapus Data',
                    text: `Apakah Anda yakin ingin menghapus user ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteFormUser' + id).submit();
                    }
                });
            });

        });
    </script>
@endpush
