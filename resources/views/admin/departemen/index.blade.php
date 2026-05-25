@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Departemen</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        {{-- PERMISSION: departemen-create-admin --}}
                        @can('departemen-create-admin')
                            <a href="#" class="btn btn-primary" id="btnTambahDepartemen" data-bs-toggle="modal"
                                data-bs-target="#modal-inputdepartemen">
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
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    {{-- Pesan Sukses/Warning dari Controller --}}
                                    {{-- Notifications will be handled by SweetAlert via @push('myscript') --}}
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-12">
                                    <form action="{{ route('departemen.index') }}" method="GET">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    {{-- Input Field --}}
                                                    <input type="text" name="nama_dept" id="nama_dept_search"
                                                        class="form-control" placeholder="Cari Nama Departemen..."
                                                        value="{{ request()->nama_dept }}">

                                                    {{-- Tombol Cari --}}
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
                                                    <th>Kode Dept.</th>
                                                    <th>Nama Departemen</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($departemen as $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration + $departemen->firstItem() - 1 }}</td>
                                                        <td>{{ $data->kode_dept }}</td>
                                                        <td>{{ $data->nama_dept }}</td>
                                                        <td>
                                                            <div class="btn-list flex-nowrap text-nowrap">
                                                                {{-- PERMISSION: departemen-edit-admin --}}
                                                                @can('departemen-edit-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-primary btn-icon edit-dept"
                                                                        data-kode_dept="{{ $data->kode_dept }}"
                                                                        title="Edit Data"
                                                                        aria-label="Edit {{ $data->nama_dept }}">
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

                                                                {{-- PERMISSION: departemen-delete-admin --}}
                                                                @can('departemen-delete-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-danger btn-icon delete-confirm-dept"
                                                                        data-kode_dept="{{ $data->kode_dept }}"
                                                                        data-nama_dept="{{ $data->nama_dept }}"
                                                                        title="Hapus Data"
                                                                        aria-label="Hapus {{ $data->nama_dept }}">
                                                                        {{-- ICON: Trash --}}
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
                                                                        action="{{ route('departemen.destroy', ['kode_dept' => $data->kode_dept]) }}"
                                                                        method="POST"
                                                                        id="deleteFormDept{{ $data->kode_dept }}"
                                                                        class="d-none">
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
                                        {{ $departemen->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-inputdepartemen" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('departemen.store') }}" method="POST" id="formDepartemen">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-binary-tree" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M18 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M6 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M18 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M6 8l0 8"></path>
                                            <path d="M18 8l0 8"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="kode_dept" value="{{ old('kode_dept') }}"
                                        class="form-control" name="kode_dept" placeholder="Kode Departemen (3 Char)"
                                        data-field="Kode Departemen">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-building-factory" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path
                                                d="M12 21h-5a2 2 0 0 1 -2 -2v-10a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v10a2 2 0 0 1 -2 2h-5">
                                            </path>
                                            <path d="M7 9l0 12"></path>
                                            <path d="M17 9l0 12"></path>
                                            <path d="M10 10l-2 6"></path>
                                            <path d="M16 10l2 6"></path>
                                            <path d="M12 7l0 -3"></path>
                                            <path d="M15 6l0 2"></path>
                                            <path d="M9 6l0 2"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="nama_dept" value="{{ old('nama_dept') }}"
                                        class="form-control" name="nama_dept" placeholder="Nama Departemen"
                                        data-field="Nama Departemen">
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

    {{-- Modal Edit Departemen --}}
    <div class="modal modal-blur fade" id="modal-editdepartemen" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Form akan dimuat di sini --}}
                <div class="modal-body" id="loadededitformdept">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
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

        function validateDepartemenForm(e, isEdit = false) {
            const kode_dept = isEdit ? $('#kode_dept_edit_dept').val().trim() : $('#kode_dept').val().trim();
            const nama_dept = isEdit ? $('#nama_dept_edit').val().trim() : $('#nama_dept').val().trim();

            const regexKode = /^[A-Z0-9]{3}$/;

            function showWarningDept(text, id) {
                e.preventDefault();
                Swal.fire({
                    title: 'Warning',
                    text: text,
                    icon: 'warning',
                    confirmButtonText: 'Ok'
                }).then(() => {
                    $(id).focus();
                });
                return false;
            }

            if (!isEdit) {
                if (kode_dept === "") return showWarningDept('Kode Departemen harus diisi!', '#kode_dept');
                if (kode_dept.length !== 3) return showWarningDept('Kode Departemen harus 3 karakter!', '#kode_dept');
                if (!kode_dept.match(regexKode)) return showWarningDept(
                    'Kode Departemen harus 3 karakter angka/huruf kapital!', '#kode_dept');
            }

            if (nama_dept === "") return showWarningDept('Nama Departemen harus diisi!', isEdit ? '#nama_dept_edit' :
                '#nama_dept');

            return true;
        }

        $(function() {
            $('#formDepartemen').on('submit', function(e) {
                return validateDepartemenForm(e, false);
            });

            $('.edit-dept').on('click', function(e) {
                e.preventDefault();
                let kode_dept = $(this).data('kode_dept');

                $.get('/departemen/' + kode_dept + '/edit', function(data) {
                    $('#loadededitformdept').html(data);
                    $('#modal-editdepartemen').modal('show');
                }).fail(function(xhr, status, error) {
                    console.error("AJAX Load Error:", status, error);
                    Swal.fire('Error',
                        'Gagal memuat form edit departemen. Cek rute dan controller.', 'error');
                });
            });

            $(document).on('submit', '#formDepartemenEdit', function(e) {
                return validateDepartemenForm(e, true);
            });

            $('.delete-confirm-dept').on('click', function(e) {
                e.preventDefault();
                let kode_dept = $(this).data('kode_dept');
                let nama_dept = $(this).data('nama_dept');

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
                $.get('/departemen/' + kode_dept + '/relations', function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        let rels = response.relations;
                        let hasRelations = (rels.karyawan > 0 || rels.user > 0 || rels.kpi > 0 || rels.configuration > 0 || rels.holiday > 0);
                        
                        let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus departemen <b>${nama_dept}</b> (${kode_dept})?</p>`;
                        
                        if (hasRelations) {
                            messageHtml += `
                                <div class="alert alert-warning text-left" style="font-size: 13px; border-left: 4px solid #ffc107; background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 4px;">
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
                            
                            // Tampilkan data cascade (kpi, configuration, holiday)
                            if (rels.kpi > 0 || rels.configuration > 0 || rels.holiday > 0) {
                                messageHtml += `<li class="text-danger">Data berikut akan <b>ikut terhapus secara permanen</b>:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                if (rels.configuration > 0) messageHtml += `<li><b>${rels.configuration}</b> Konfigurasi Jam Kerja</li>`;
                                if (rels.holiday > 0) messageHtml += `<li><b>${rels.holiday}</b> Hari Libur</li>`;
                                if (rels.kpi > 0) messageHtml += `<li><b>${rels.kpi}</b> Data KPI</li>`;
                                messageHtml += `</ul></li>`;
                            }
                            
                            messageHtml += `
                                    </ul>
                                </div>
                            `;
                        } else {
                            messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada data lain yang bergantung pada departemen ini.</p>`;
                        }

                        Swal.fire({
                            title: 'Hapus Data Departemen?',
                            html: messageHtml,
                            icon: hasRelations ? 'warning' : 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Ya, Hapus!',
                            cancelButtonText: 'Batal',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $(`#deleteFormDept${kode_dept}`).submit();
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
