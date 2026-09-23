@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Master Cuti</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        {{-- PERMISSION: cuti-create-admin --}}
                        @can('cuti-create-admin')
                            <a href="#" class="btn btn-primary" id="btnTambahCuti" data-bs-toggle="modal"
                                data-bs-target="#modal-inputcuti">
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

                                    {{-- Pesan Error Validasi --}}
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
                                    <form action="{{ route('cuti.index') }}" method="GET">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    {{-- Input Field --}}
                                                    <input type="text" name="nama_cuti" id="nama_cuti_search"
                                                        class="form-control" placeholder="Cari Nama Cuti..."
                                                        value="{{ request()->nama_cuti }}">

                                                    {{-- Tombol Cari (Menyatu) --}}
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
                                                    <th>Kode Cuti</th>
                                                    <th>Nama Cuti</th>
                                                    <th>Jumlah Hari</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {{-- Menggunakan variabel $cuti dari controller --}}
                                                @foreach ($cuti as $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration + $cuti->firstItem() - 1 }}</td>
                                                        <td>{{ $data->kode_cuti }}</td>
                                                        <td>{{ $data->nama_cuti }}</td>
                                                        <td>{{ $data->jml_hari }} hari</td>
                                                        <td>
                                                            <div class="btn-list flex-nowrap text-nowrap">
                                                                {{-- PERMISSION: cuti-edit-admin --}}
                                                                @can('cuti-edit-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-primary btn-icon edit-cuti"
                                                                        data-kode_cuti="{{ $data->kode_cuti }}"
                                                                        title="Edit Data">
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

                                                                {{-- PERMISSION: cuti-delete-admin --}}
                                                                @can('cuti-delete-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-danger btn-icon delete-confirm-cuti"
                                                                        data-kode_cuti="{{ $data->kode_cuti }}"
                                                                        data-nama_cuti="{{ $data->nama_cuti }}"
                                                                        title="Hapus Data">
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

                                                                    {{-- Form Delete --}}
                                                                    <form
                                                                        action="{{ route('cuti.destroy', ['kode_cuti' => $data->kode_cuti]) }}"
                                                                        method="POST"
                                                                        id="deleteFormCuti{{ $data->kode_cuti }}"
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
                                        {{-- Paginasi --}}
                                        {{ $cuti->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Cuti --}}
    <div class="modal modal-blur fade" id="modal-inputcuti" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('cuti.store') }}" method="POST" id="formCuti">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        {{-- Icon untuk Kode Cuti --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-code"
                                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M7 8l-4 4l4 4"></path>
                                            <path d="M17 8l4 4l-4 4"></path>
                                            <path d="M14 4l-4 16"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="kode_cuti" value="{{ old('kode_cuti') }}"
                                        class="form-control" name="kode_cuti" placeholder="Kode Cuti (3 Char, A-Z/0-9)"
                                        data-field="Kode Cuti" maxlength="3">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        {{-- Icon untuk Nama Cuti --}}
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-calendar-time" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path
                                                d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4">
                                            </path>
                                            <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                            <path d="M3 10h18"></path>
                                            <path d="M16 3v4"></path>
                                            <path d="M8 3v4"></path>
                                            <path d="M21 17v2l1 1"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="nama_cuti" value="{{ old('nama_cuti') }}"
                                        class="form-control" name="nama_cuti" placeholder="Nama Cuti"
                                        data-field="Nama Cuti">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        {{-- Icon untuk Jumlah Hari --}}
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-number" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 17v-10l5 10v-10" />
                                            <path d="M14 17h6" />
                                            <path d="M18 5l2 0" />
                                        </svg>
                                    </span>
                                    <input type="text" id="jml_hari" value="{{ old('jml_hari') }}"
                                        class="form-control" name="jml_hari" placeholder="Jumlah Hari Cuti"
                                        data-field="Jumlah Hari" onkeypress="onlyNumberInput(event)">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary w-100">
                                            {{-- Icon Simpan --}}
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

    {{-- Modal Edit Cuti --}}
    <div class="modal modal-blur fade" id="modal-editcuti" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Form akan dimuat di sini --}}
                <div class="modal-body" id="loadededitformcuti">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // FUNGSI INI DIBUTUHKAN DI KEDUA FILE VIEW
        // Membatasi hanya angka
        function onlyNumberInput(event) {
            let key = event.key;
            if (!/^\d$/.test(key) && !event.metaKey && !event.ctrlKey && key.length === 1) {
                event.preventDefault();
            }
        }

        // FUNGSI INI DIBUTUHKAN DI KEDUA FILE VIEW
        // Membatasi hanya huruf dan spasi (TIDAK DIPAKAI untuk Kode Cuti, tapi untuk Nama Cuti bisa dipertimbangkan)
        function onlyTextInput(event) {
            let key = event.key;
            const isAllowedKey = /^[a-zA-Z\s]$/.test(key);
            const isControlKey = event.metaKey || event.ctrlKey || key.length !== 1;

            if (!isAllowedKey && !isControlKey) {
                event.preventDefault();
            }
        }

        // FUNGSI BARU: Validasi untuk form Cuti
        function validateCutiForm(e, isEdit = false) {
            // Untuk form Tambah, ambil dari modal Tambah. Untuk form Edit, ambil dari modal Edit.
            const kode_cuti = isEdit ? $('#kode_cuti_edit').val().trim() : $('#kode_cuti').val().trim();
            const nama_cuti = isEdit ? $('#nama_cuti_edit').val().trim() : $('#nama_cuti').val().trim();
            const jml_hari = isEdit ? $('#jml_hari_edit').val().trim() : $('#jml_hari').val().trim();


            const regexKode = /^[A-Z0-9]{3}$/;

            function showWarningCuti(text, id) {
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
                if (kode_cuti === "") return showWarningCuti('Kode Cuti harus diisi!', '#kode_cuti');
                if (kode_cuti.length !== 3) return showWarningCuti('Kode Cuti harus 3 karakter!', '#kode_cuti');
                if (!kode_cuti.match(regexKode)) return showWarningCuti(
                    'Kode Cuti harus 3 karakter angka/huruf kapital!', '#kode_cuti');
            }

            if (nama_cuti === "") return showWarningCuti('Nama Cuti harus diisi!', isEdit ? '#nama_cuti_edit' :
                '#nama_cuti');

            if (jml_hari === "" || isNaN(parseInt(jml_hari))) return showWarningCuti(
                'Jumlah Hari harus diisi dan berupa angka!', isEdit ? '#jml_hari_edit' :
                '#jml_hari');

            const jml_hari_int = parseInt(jml_hari);
            if (jml_hari_int < 1 || jml_hari_int > 365) return showWarningCuti('Jumlah Hari harus antara 1 sampai 365!',
                isEdit ? '#jml_hari_edit' :
                '#jml_hari');


            return true;
        }

        $(function() {
            // 1. Integrasi Validasi form Tambah Data Cuti
            $('#formCuti').on('submit', function(e) {
                return validateCutiForm(e, false);
            });

            // 2. Logic untuk memuat form edit cuti (AJAX)
            $('.edit-cuti').on('click', function(e) {
                e.preventDefault();
                let kode_cuti = $(this).data('kode_cuti');

                // Menggunakan rute baru (cuti.edit)
                $.get('/cuti/' + kode_cuti + '/edit', function(data) {
                    $('#loadededitformcuti').html(data);
                    $('#modal-editcuti').modal('show');
                }).fail(function(xhr, status, error) {
                    console.error("AJAX Load Error:", status, error);
                    Swal.fire('Error',
                        'Gagal memuat form edit cuti. Cek rute dan controller.', 'error');
                });
            });

            // 3. Integrasi Validasi form Edit Data Cuti (Delegasi)
            $(document).on('submit', '#formCutiEdit', function(e) {
                return validateCutiForm(e, true);
            });

            // 4. Logic untuk konfirmasi Hapus Cuti (Delete)
            $('.delete-confirm-cuti').on('click', function(e) {
                e.preventDefault();
                let kode_cuti = $(this).data('kode_cuti');
                let nama_cuti = $(this).data('nama_cuti');

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
                $.get('/cuti/' + kode_cuti + '/relations', function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        let rels = response.relations;
                        let hasRelations = (rels.izin > 0);
                        
                        let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus cuti <b>${nama_cuti}</b> (${kode_cuti})?</p>`;
                        
                        if (hasRelations) {
                            messageHtml += `
                                <div class="alert alert-warning text-start mb-0">
                                    <h6 class="alert-heading mb-1 font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Data Terkait Terdeteksi:</h6>
                                    <ul class="pl-3 mb-0" style="list-style-type: disc;">
                            `;
                            
                            // Tampilkan data set null (izin)
                            if (rels.izin > 0) {
                                messageHtml += `<li class="mb-1 text-dark">Data berikut akan <b>dikosongkan</b> dan perlu diatur ulang nantinya:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                messageHtml += `<li><b>${rels.izin}</b> Riwayat Pengajuan Izin/Cuti Karyawan</li>`;
                                messageHtml += `</ul></li>`;
                            }
                            
                            messageHtml += `
                                    </ul>
                                </div>
                            `;
                        } else {
                            messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada data lain yang bergantung pada cuti ini.</p>`;
                        }

                        Swal.fire({
                            title: 'Hapus Data Cuti?',
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
                                $(`#deleteFormCuti${kode_cuti}`).submit();
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

            // Membatasi input Kode Cuti agar hanya Huruf Kapital/Angka
            $('#kode_cuti').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            // Batasi input Jumlah Hari hanya angka
            $('#jml_hari').on('keypress', onlyNumberInput);

            // Gunakan delegasi untuk elemen yang dimuat via AJAX
            $(document).on('keypress', '#jml_hari_edit', onlyNumberInput);

        });
    </script>
@endpush
