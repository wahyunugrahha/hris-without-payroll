@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Jam Kerja</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @can('jam-kerja-create-admin')
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" id="btnTambahJK"
                                data-bs-toggle="modal" data-bs-target="#modal-inputjk">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Data
                            </a>
                            {{-- Mobile Button --}}
                            <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal"
                                data-bs-target="#modal-inputjk" aria-label="Tambah Data">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    {{-- Notifications are handled globally by SweetAlert --}}

                    <div class="card">
                        <div class="card-header border-bottom py-3">
                            <form action="{{ route('konfigurasi.jamkerja') }}" method="GET" autocomplete="off"
                                class="w-100">
                                <div class="row g-3">
                                    {{-- 1. Filter Tipe Jam Kerja --}}
                                    <div class="col-12 col-md-4">
                                        <select name="lintashari" class="form-select">
                                            <option value="">Semua Tipe Jam Kerja</option>
                                            <option value="1" {{ request('lintashari') === '1' ? 'selected' : '' }}>
                                                Lintas Hari
                                            </option>
                                            <option value="0" {{ request('lintashari') === '0' ? 'selected' : '' }}>
                                                Normal (Satu Hari)
                                            </option>
                                        </select>
                                    </div>

                                    {{-- 2. Pencarian & Submit --}}
                                    <div class="col-12 col-md-8">
                                        <div class="input-group">
                                            <input type="text" name="nama_jam_kerja" class="form-control"
                                                placeholder="Cari nama jam kerja (Contoh: Shift Pagi)..."
                                                value="{{ request('nama_jam_kerja') }}">
                                            <button type="submit" class="btn btn-primary">
                                                Cari Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Table Section --}}
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th class="w-1">No</th>
                                        <th>Kode</th>
                                        <th>Nama Jam Kerja</th>
                                        <th class="text-center">Awal Masuk</th>
                                        <th class="text-center">Jam Masuk</th>
                                        <th class="text-center">Akhir Masuk</th>
                                        <th class="text-center">Jam Pulang</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="w-1">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($jam_kerja as $d)
                                        <tr>
                                            <td><span
                                                    class="text-muted">{{ $loop->iteration + $jam_kerja->firstItem() - 1 }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt">{{ $d->kode_jam_kerja }}</span>
                                            </td>
                                            <td class="fw-bold">{{ $d->nama_jam_kerja }}</td>
                                            <td class="text-center text-secondary">{{ $d->awal_jam_masuk }}</td>
                                            <td class="text-center text-primary fw-bold">{{ $d->jam_masuk }}</td>
                                            <td class="text-center text-secondary">{{ $d->akhir_jam_masuk }}</td>
                                            <td class="text-center text-primary fw-bold">{{ $d->jam_pulang }}</td>
                                            <td class="text-center">
                                                @if ($d->lintashari == 1)
                                                    <span class="badge bg-yellow-lt" title="Lintas Hari">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-moon" width="12"
                                                            height="12" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path
                                                                d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z">
                                                            </path>
                                                        </svg>
                                                        Lintas Hari
                                                    </span>
                                                @else
                                                    <span class="badge bg-azure-lt" title="Normal">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-sun" width="12"
                                                            height="12" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <circle cx="12" cy="12" r="4"></circle>
                                                            <path
                                                                d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7">
                                                            </path>
                                                        </svg>
                                                        Normal
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    {{-- PERMISSION: jam-kerja-edit-admin --}}
                                                    @can('jam-kerja-edit-admin')
                                                        <a href="#" class="btn btn-ghost-primary btn-icon edit-jk"
                                                            data-kode_jk="{{ $d->kode_jam_kerja }}" title="Edit Data">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-tabler icon-tabler-pencil" width="24"
                                                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                                stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path
                                                                    d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                <path d="M13.5 6.5l4 4" />
                                                            </svg>
                                                        </a>
                                                    @endcan

                                                    {{-- PERMISSION: jam-kerja-delete-admin --}}
                                                    @can('jam-kerja-delete-admin')
                                                        <a href="#"
                                                            class="btn btn-ghost-danger btn-icon delete-confirm-jk"
                                                            data-kode_jk="{{ $d->kode_jam_kerja }}"
                                                            data-nama_jk="{{ $d->nama_jam_kerja }}" title="Hapus Data">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-tabler icon-tabler-trash" width="24"
                                                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                                stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M4 7l16 0" />
                                                                <path d="M10 11l0 6" />
                                                                <path d="M14 11l0 6" />
                                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                            </svg>
                                                        </a>
                                                        <form
                                                            action="{{ route('konfigurasi.destroyjamkerja', ['kode_jam_kerja' => $d->kode_jam_kerja]) }}"
                                                            method="POST" id="deleteFormJK{{ $d->kode_jam_kerja }}"
                                                            class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9">
                                                <div class="empty">
                                                    <div class="empty-img"><img
                                                            src="https://preview.tabler.io/static/illustrations/undraw_printing_invoices_5r4r.svg"
                                                            height="128" alt="">
                                                    </div>
                                                    <p class="empty-title">Tidak ada data ditemukan</p>
                                                    <p class="empty-subtitle text-muted">
                                                        Coba sesuaikan pencarian atau filter Anda untuk menemukan apa yang
                                                        Anda cari.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="card-footer d-flex align-items-center">
                            <p class="m-0 text-muted">Showing <span>{{ $jam_kerja->firstItem() }}</span> to
                                <span>{{ $jam_kerja->lastItem() }}</span> of <span>{{ $jam_kerja->total() }}</span>
                                entries
                            </p>
                            <ul class="pagination m-0 ms-auto">
                                {{ $jam_kerja->links('pagination::bootstrap-5') }}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Input JK --}}
    <div class="modal modal-blur fade" id="modal-inputjk" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Jam Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('konfigurasi.storejamkerja') }}" method="POST" id="formJK">
                        @csrf
                        {{-- Grouping Kode & Nama --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label required">Kode Jam Kerja</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 7v-1a2 2 0 0 1 2 -2h2" />
                                            <path d="M4 17v1a2 2 0 0 0 2 2h2" />
                                            <path d="M16 4h2a2 2 0 0 1 2 2v1" />
                                            <path d="M16 20h2a2 2 0 0 0 2 -2v-1" />
                                            <path d="M5 11h1v2h-1z" />
                                            <path d="M10 11l0 2" />
                                            <path d="M14 11h1v2h-1z" />
                                            <path d="M19 11l0 2" />
                                        </svg>
                                    </span>
                                    <input type="text" id="kode_jam_kerja" value="{{ old('kode_jam_kerja') }}"
                                        class="form-control" name="kode_jam_kerja" placeholder="Contoh: JK01">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label required">Nama Jam Kerja</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <rect x="3" y="7" width="18" height="13" rx="2">
                                            </rect>
                                            <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="nama_jam_kerja" value="{{ old('nama_jam_kerja') }}"
                                        class="form-control" name="nama_jam_kerja" placeholder="Contoh: Shift Pagi">
                                </div>
                            </div>
                        </div>

                        {{-- Grouping Waktu Grid 2x2 --}}
                        <div class="row">
                            <div class="col-md-6">
                                <fieldset class="form-fieldset">
                                    <legend>Pengaturan Masuk</legend>
                                    <div class="mb-3">
                                        <label class="form-label">Awal Absen Masuk</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="9" />
                                                    <polyline points="12 7 12 12 15 15" />
                                                </svg>
                                            </span>
                                            <input type="text" id="awal_jam_masuk"
                                                value="{{ old('awal_jam_masuk') }}" class="form-control"
                                                name="awal_jam_masuk" placeholder="00:00">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">Jam Masuk</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-clock-play" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 7v5l2.5 1.5" />
                                                    <path d="M12 21a9 9 0 0 1 -9 -9c0 -4.97 4.03 -9 9 -9" />
                                                    <path d="M17 19l4 -2.5v5z" />
                                                </svg>
                                            </span>
                                            <input type="text" id="jam_masuk" value="{{ old('jam_masuk') }}"
                                                class="form-control" name="jam_masuk" placeholder="00:00">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Batas Akhir Masuk</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="9" />
                                                    <line x1="12" y1="8" x2="12" y2="12" />
                                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                                </svg>
                                            </span>
                                            <input type="text" id="akhir_jam_masuk"
                                                value="{{ old('akhir_jam_masuk') }}" class="form-control"
                                                name="akhir_jam_masuk" placeholder="00:00">
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-6">
                                <fieldset class="form-fieldset">
                                    <legend>Pengaturan Pulang & Status</legend>
                                    <div class="mb-3">
                                        <label class="form-label required">Jam Pulang</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-clock-stop" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M21 12a9 9 0 1 0 -9 9" />
                                                    <path d="M12 7v5l1 1" />
                                                    <path d="M16 16h6v6h-6z" />
                                                </svg>
                                            </span>
                                            <input type="text" id="jam_pulang" value="{{ old('jam_pulang') }}"
                                                class="form-control" name="jam_pulang" placeholder="00:00">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">Status Lintas Hari</label>
                                        <select name="lintashari" id="lintashari" class="form-select">
                                            <option value="">-- Pilih Status --</option>
                                            <option value="1">Ya (Lintas Hari)</option>
                                            <option value="0">Tidak (Normal)</option>
                                        </select>
                                        <small class="form-hint">Pilih "Ya" jika jam kerja melewati pukul 00:00.</small>
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                                Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit (Containernya saja, isinya dari AJAX/Controller) --}}
    <div class="modal modal-blur fade" id="modal-editjk" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Jam Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="loadededitformjk">
                    {{-- Content loaded via AJAX --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    {{-- Script JavaScript Anda tetap sama persis, tidak perlu diubah --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        function validateJamKerjaForm(e, isEdit = false) {
            const kode_jam_kerja = isEdit ? $('#kode_jam_kerja_edit').val().trim() : $('#kode_jam_kerja').val().trim();
            const nama_jam_kerja = isEdit ? $('#nama_jam_kerja_edit').val().trim() : $('#nama_jam_kerja').val().trim();
            const awal_jam_masuk = isEdit ? $('#awal_jam_masuk_edit').val().trim() : $('#awal_jam_masuk').val().trim();
            const jam_masuk = isEdit ? $('#jam_masuk_edit').val().trim() : $('#jam_masuk').val().trim();
            const akhir_jam_masuk = isEdit ? $('#akhir_jam_masuk_edit').val().trim() : $('#akhir_jam_masuk').val().trim();
            const jam_pulang = isEdit ? $('#jam_pulang_edit').val().trim() : $('#jam_pulang').val().trim();
            const lintashari = isEdit ? $('#lintashari_edit').val() : $('#lintashari').val();

            const regexKodeJK = /^[A-Z0-9]{4}$/;
            const regexJam = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;

            function showWarningJK(text, id) {
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
                if (kode_jam_kerja === "") return showWarningJK('Kode Jam Kerja harus diisi!', '#kode_jam_kerja');

                if (kode_jam_kerja.length !== 4) return showWarningJK('Kode Jam Kerja harus 4 karakter!',
                    '#kode_jam_kerja');

                if (!kode_jam_kerja.match(regexKodeJK)) return showWarningJK(
                    'Kode Jam Kerja harus 4 karakter angka/huruf kapital!', '#kode_jam_kerja');
            }

            if (nama_jam_kerja === "") return showWarningJK('Nama Jam Kerja harus diisi!', isEdit ? '#nama_jam_kerja_edit' :
                '#nama_jam_kerja');

            if (awal_jam_masuk === "") return showWarningJK('Awal Jam Masuk harus diisi!', isEdit ? '#awal_jam_masuk_edit' :
                '#awal_jam_masuk');
            if (!awal_jam_masuk.match(regexJam)) return showWarningJK('Format Awal Jam Masuk tidak valid (HH:MM)!', isEdit ?
                '#awal_jam_masuk_edit' : '#awal_jam_masuk');

            if (jam_masuk === "") return showWarningJK('Jam Masuk harus diisi!', isEdit ? '#jam_masuk_edit' : '#jam_masuk');
            if (!jam_masuk.match(regexJam)) return showWarningJK('Format Jam Masuk tidak valid (HH:MM)!', isEdit ?
                '#jam_masuk_edit' : '#jam_masuk');

            if (akhir_jam_masuk === "") return showWarningJK('Akhir Jam Masuk harus diisi!', isEdit ?
                '#akhir_jam_masuk_edit' : '#akhir_jam_masuk');
            if (!akhir_jam_masuk.match(regexJam)) return showWarningJK('Format Akhir Jam Masuk tidak valid (HH:MM)!',
                isEdit ? '#akhir_jam_masuk_edit' : '#akhir_jam_masuk');

            if (jam_pulang === "") return showWarningJK('Jam Pulang harus diisi!', isEdit ? '#jam_pulang_edit' :
                '#jam_pulang');
            if (!jam_pulang.match(regexJam)) return showWarningJK('Format Jam Pulang tidak valid (HH:MM)!', isEdit ?
                '#jam_pulang_edit' : '#jam_pulang');

            if (lintashari === "") return showWarningJK('Status Lintas Hari harus dipilih!', isEdit ? '#lintashari_edit' :
                '#lintashari');

            return true;
        }

        $(function() {
            flatpickr("#awal_jam_masuk, #jam_masuk, #akhir_jam_masuk, #jam_pulang", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true
            });

            $('#formJK').on('submit', function(e) {
                return validateJamKerjaForm(e, false);
            });

            $('.edit-jk').on('click', function(e) {
                e.preventDefault();
                let kode_jam_kerja = $(this).data('kode_jk');

                $.get('/konfigurasi/jamkerja/' + kode_jam_kerja + '/edit', function(data) {
                    $('#loadededitformjk').html(data);
                    $('#modal-editjk').modal('show');

                    flatpickr(
                        "#awal_jam_masuk_edit, #jam_masuk_edit, #akhir_jam_masuk_edit, #jam_pulang_edit", {
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: "H:i",
                            time_24hr: true
                        });
                }).fail(function(xhr, status, error) {
                    console.error("AJAX Load Error:", status, error);
                    Swal.fire('Error',
                        'Gagal memuat form edit Jam Kerja. Cek rute dan controller.', 'error');
                });
            });

            $(document).on('submit', '#formJamKerjaEdit', function(e) {
                return validateJamKerjaForm(e, true);
            });

            $('.delete-confirm-jk').on('click', function(e) {
                e.preventDefault();
                let kode_jam_kerja = $(this).data('kode_jk');
                let nama_jam_kerja = $(this).data('nama_jk');

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
                $.get('/konfigurasi/jamkerja/' + kode_jam_kerja + '/relations', function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        let rels = response.relations;
                        let hasRelations = (rels.presensi > 0 || rels.configuration > 0 || rels.personal > 0);
                        
                        let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus jam kerja <b>${nama_jam_kerja}</b> (${kode_jam_kerja})?</p>`;
                        
                        if (hasRelations) {
                            messageHtml += `
                                <div class="alert alert-warning text-left" style="font-size: 13px; border-left: 4px solid #ffc107; background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 4px;">
                                    <h6 class="alert-heading mb-1 font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Data Terkait Terdeteksi:</h6>
                                    <ul class="pl-3 mb-0" style="list-style-type: disc;">
                            `;
                            
                            // Tampilkan data set null (presensi)
                            if (rels.presensi > 0) {
                                messageHtml += `<li class="mb-1 text-dark">Data berikut akan <b>dikosongkan</b> dan perlu diatur ulang nantinya:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                messageHtml += `<li><b>${rels.presensi}</b> Riwayat Presensi/Absensi</li>`;
                                messageHtml += `</ul></li>`;
                            }
                            
                            // Tampilkan data cascade (configuration, personal)
                            if (rels.configuration > 0 || rels.personal > 0) {
                                messageHtml += `<li class="text-danger">Data berikut akan <b>ikut terhapus secara permanen</b>:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                if (rels.configuration > 0) messageHtml += `<li><b>${rels.configuration}</b> Konfigurasi Jam Kerja Departemen</li>`;
                                if (rels.personal > 0) messageHtml += `<li><b>${rels.personal}</b> Set Jam Kerja Karyawan (Personal)</li>`;
                                messageHtml += `</ul></li>`;
                            }
                            
                            messageHtml += `
                                    </ul>
                                </div>
                            `;
                        } else {
                            messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada data lain yang bergantung pada jam kerja ini.</p>`;
                        }

                        Swal.fire({
                            title: 'Hapus Data Jam Kerja?',
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
                                $(`#deleteFormJK${kode_jam_kerja}`).submit();
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
