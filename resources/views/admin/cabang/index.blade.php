@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Cabang</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @can('cabang-create-admin')
                            <a href="#" class="btn btn-primary" id="btnTambahCabang" data-bs-toggle="modal"
                                data-bs-target="#modal-inputcabang">
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
                                    {{-- Notifications will be handled by SweetAlert via tabler layout --}}
                            </div>

                            <div class="row mt-2">
                                <div class="col-12">
                                    <form action="{{ route('cabang.index') }}" method="GET">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    {{-- Input Field --}}
                                                    <input type="text" name="nama_cabang" id="nama_cabang_search"
                                                        class="form-control" placeholder="Cari Nama Cabang..."
                                                        value="{{ request()->nama_cabang }}">

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
                                                    <th>Kode Cabang</th>
                                                    <th>Nama Cabang</th>
                                                    <th>Lokasi Kantor</th>
                                                    <th>Radius</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($cabang as $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration + $cabang->firstItem() - 1 }}</td>
                                                        <td>{{ $data->kode_cabang }}</td>
                                                        <td>{{ $data->nama_cabang }}</td>
                                                        <td>{{ $data->lokasi_kantor }}</td>
                                                        <td>{{ $data->radius }} m</td>
                                                        <td>
                                                            <div class="d-flex gap-2 align-items-center">
                                                                {{-- Kelola Lokasi (View) --}}
                                                                <a href="{{ route('cabanglokasi.index', ['kode_cabang' => $data->kode_cabang]) }}"
                                                                    class="btn btn-ghost-primary btn-icon"
                                                                    title="Kelola Lokasi Detail">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="icon icon-tabler icon-tabler-map-pin"
                                                                        width="20" height="20" viewBox="0 0 24 24"
                                                                        stroke-width="2" stroke="currentColor"
                                                                        fill="none" stroke-linecap="round"
                                                                        stroke-linejoin="round">
                                                                        <path stroke="none" d="M0 0h24v24H0z"
                                                                            fill="none"></path>
                                                                        <path d="M12 11m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0">
                                                                        </path>
                                                                        <path
                                                                            d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z">
                                                                        </path>
                                                                    </svg>
                                                                </a>

                                                                {{-- PERMISSION: cabang-edit-admin --}}
                                                                @can('cabang-edit-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-primary btn-icon edit-cabang"
                                                                        data-kode_cabang="{{ $data->kode_cabang }}"
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

                                                                {{-- PERMISSION: cabang-delete-admin --}}
                                                                @can('cabang-delete-admin')
                                                                    <a href="#"
                                                                        class="btn btn-ghost-danger btn-icon delete-confirm-cabang"
                                                                        data-kode_cabang="{{ $data->kode_cabang }}"
                                                                        data-nama_cabang="{{ $data->nama_cabang }}"
                                                                        title="Hapus Data">
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
                                                                        action="{{ route('cabang.destroy', ['kode_cabang' => $data->kode_cabang]) }}"
                                                                        method="POST"
                                                                        id="deleteFormCabang{{ $data->kode_cabang }}">
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
                                        {{ $cabang->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Cabang --}}
    <div class="modal modal-blur fade" id="modal-inputcabang" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('cabang.store') }}" method="POST" id="formCabang">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-map-pin" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                            <path
                                                d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z">
                                            </path>
                                        </svg>
                                    </span>
                                    {{-- PERUBAHAN: Placeholder diperbarui --}}
                                    <input type="text" id="kode_cabang" value="{{ old('kode_cabang') }}"
                                        class="form-control" name="kode_cabang" placeholder="Kode Cabang (3-8 Karakter)"
                                        data-field="Kode Cabang">
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
                                    <input type="text" id="nama_cabang" value="{{ old('nama_cabang') }}"
                                        class="form-control" name="nama_cabang" placeholder="Nama Cabang"
                                        data-field="Nama Cabang">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-current-location" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                            <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0"></path>
                                            <path d="M12 12l0 0.01"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="lokasi_kantor" value="{{ old('lokasi_kantor') }}"
                                        class="form-control" name="lokasi_kantor"
                                        placeholder="Lokasi Kantor (Lat/Long/Alamat)" data-field="Lokasi Kantor">
                                </div>
                                <div class="input-icon mb-3">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-target-arrow" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                            <path d="M8.2 8.2l3.8 -3.8"></path>
                                            <path d="M12 21a9 9 0 1 0 0 -18a9 9 0 0 0 0 18z"></path>
                                            <path d="M12 3v18"></path>
                                            <path d="M3 12h18"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="radius" value="{{ old('radius') }}"
                                        class="form-control" name="radius" placeholder="Radius Absensi (meter)"
                                        data-field="Radius" onkeypress="onlyNumberInput(event)">
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

    {{-- Modal Edit Cabang --}}
    <div class="modal modal-blur fade" id="modal-editcabang" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="loadededitformcabang">
                </div>
            </div>
        </div>
    </div>


@endsection

@push('myscript')
    <script>
        // Membatasi hanya angka
        function onlyNumberInput(event) {
            let key = event.key;
            if (!/^\d$/.test(key) && !event.metaKey && !event.ctrlKey && key.length === 1) {
                event.preventDefault();
            }
        }

        // Membatasi hanya huruf dan spasi
        function onlyTextInput(event) {
            let key = event.key;
            const isAllowedKey = /^[a-zA-Z\s]$/.test(key);
            const isControlKey = event.metaKey || event.ctrlKey || key.length !== 1;

            if (!isAllowedKey && !isControlKey) {
                event.preventDefault();
            }
        }

        // Validasi untuk form Cabang
        function validateCabangForm(e, isEdit = false) {
            // Mengambil nilai field. Perhatikan perbedaan ID/nama field antara form Tambah dan Edit
            const kode_cabang = isEdit ? $('#kode_cabang_edit').val().trim() : $('#kode_cabang').val().trim();
            const nama_cabang = isEdit ? $('#nama_cabang_edit').val().trim() : $('#nama_cabang').val().trim();
            const lokasi_kantor = isEdit ? $('#lokasi_kantor_edit').val().trim() : $('#lokasi_kantor').val().trim();
            const radius = isEdit ? $('#radius_edit').val().trim() : $('#radius').val().trim();

            // PERUBAHAN: Regex Kode Cabang (3 sampai 8 karakter, angka/huruf kapital)
            const regexKode = /^[A-Z0-9]{3,8}$/;
            const minLength = 3;
            const maxLength = 8;

            function showWarningCabang(text, id) {
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
                if (kode_cabang === "")
                    return showWarningCabang('Kode Cabang wajib diisi!', '#kode_cabang');

                // PERUBAHAN: Validasi harus 3-8 karakter
                if (kode_cabang.length < minLength || kode_cabang.length > maxLength)
                    return showWarningCabang(`Kode Cabang harus antara ${minLength} sampai ${maxLength} karakter!`,
                        '#kode_cabang');

                // PERUBAHAN: Validasi harus angka/huruf kapital (3-8 karakter)
                if (!regexKode.test(kode_cabang))
                    return showWarningCabang('Kode Cabang harus berupa angka/huruf kapital, 3 sampai 8 karakter.',
                        '#kode_cabang');


            }


            if (nama_cabang === "") return showWarningCabang('Nama Cabang harus diisi!', isEdit ? '#nama_cabang_edit' :
                '#nama_cabang');
            if (lokasi_kantor === "") return showWarningCabang('Lokasi Kantor harus diisi!', isEdit ?
                '#lokasi_kantor_edit' : '#lokasi_kantor');
            if (radius === "") return showWarningCabang('Radius wajib diisi!', isEdit ? '#radius_edit' : '#radius');
            if (isNaN(radius) || parseInt(radius) <= 0) return showWarningCabang('Radius harus berupa angka positif!',
                isEdit ? '#radius_edit' : '#radius');


            return true;
        }

        $(function() {
            // 1. Integrasi Validasi form Tambah Data Cabang
            $('#formCabang').on('submit', function(e) {
                return validateCabangForm(e, false);
            });

            // 2. Logic untuk memuat form edit Cabang (AJAX)
            $('.edit-cabang').on('click', function(e) {
                e.preventDefault();
                let kode_cabang = $(this).data('kode_cabang');

                // Rute diubah ke /cabang/{kode_cabang}/edit
                $.get('/cabang/' + kode_cabang + '/edit', function(data) {
                    $('#loadededitformcabang').html(data);
                    $('#modal-editcabang').modal('show');
                }).fail(function(xhr, status, error) {
                    console.error("AJAX Load Error:", status, error);
                    Swal.fire('Error',
                        'Gagal memuat form edit cabang. Cek rute dan controller.', 'error');
                });
            });

            // 3. Integrasi Validasi form Edit Data Cabang (Delegasi)
            $(document).on('submit', '#formCabangEdit', function(e) {
                return validateCabangForm(e, true);
            });

            // 4. Logic untuk konfirmasi Hapus Cabang (Delete)
            $('.delete-confirm-cabang').on('click', function(e) {
                e.preventDefault();
                let kode_cabang = $(this).data('kode_cabang');
                let nama_cabang = $(this).data('nama_cabang');

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
                $.get('/cabang/' + kode_cabang + '/relations', function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        let rels = response.relations;
                        let hasRelations = (rels.karyawan > 0 || rels.user > 0 || rels.kpi > 0 || rels.configuration > 0 || rels.holiday > 0 || rels.location > 0);
                        
                        let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus cabang <b>${nama_cabang}</b> (${kode_cabang})?</p>`;
                        
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
                            
                            // Tampilkan data cascade (kpi, configuration, holiday, location)
                            if (rels.kpi > 0 || rels.configuration > 0 || rels.holiday > 0 || rels.location > 0) {
                                messageHtml += `<li class="text-danger">Data berikut akan <b>ikut terhapus secara permanen</b>:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                if (rels.location > 0) messageHtml += `<li><b>${rels.location}</b> Koordinat Multi-Lokasi</li>`;
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
                            messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada data lain yang bergantung pada cabang ini.</p>`;
                        }

                        Swal.fire({
                            title: 'Hapus Data Cabang?',
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
                                $(`#deleteFormCabang${kode_cabang}`).submit();
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
