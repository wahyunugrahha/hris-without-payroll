@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">
                        Jam Kerja Departemen
                    </h2>
                </div>

                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        {{-- Permission: jam-kerja-dept-edit-admin (Untuk Bulk Setting) --}}
                        @can('jam-kerja-dept-edit-admin')
                            <button type="button" class="btn btn-secondary d-none d-sm-inline-block" data-bs-toggle="modal"
                                data-bs-target="#modalSetAllCabang">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                </svg>
                                Bulk Setting
                            </button>
                        @endcan

                        {{-- Permission: jam-kerja-dept-create-admin --}}
                        @can('jam-kerja-dept-create-admin')
                            <a href="/konfigurasi/jamkerjadept/create" class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Data
                            </a>
                        @endcan

                        {{-- Mobile view buttons --}}
                        @can('jam-kerja-dept-edit-admin')
                            <a href="#" class="btn btn-secondary d-sm-none btn-icon" data-bs-toggle="modal"
                                data-bs-target="#modalSetAllCabang">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                </svg>
                            </a>
                        @endcan
                        @can('jam-kerja-dept-create-admin')
                            <a href="/konfigurasi/jamkerjadept/create" class="btn btn-primary d-sm-none btn-icon">
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

            <div class="row">
                <div class="col-12">
                    @if (Session::get('success'))
                        <div class="alert alert-success alert-important alert-dismissible" role="alert">
                            {{ Session::get('success') }}
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if (Session::get('warning'))
                        <div class="alert alert-warning alert-important alert-dismissible" role="alert">
                            {{ Session::get('warning') }}
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-important alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <circle cx="12" cy="12" r="9" />
                                        <line x1="12" y1="8" x2="12" y2="12" />
                                        <line x1="12" y1="16" x2="12.01" y2="16" />
                                    </svg>
                                </div>
                                <div>
                                    <strong>Terjadi Kesalahan:</strong>
                                    <ul class="mb-0 ps-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif
                </div>
            </div>

            @php
                $groupedByCabang = $jamkerjadept->groupBy('kode_cabang');
                $totalCabang = $groupedByCabang->count();
                $totalDept = $jamkerjadept->count();
            @endphp
            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-blue text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 21l18 0" />
                                            <path d="M5 21v-14l8 -4l8 4v14" />
                                            <path d="M13 13a2 2 0 0 0 2 2h4v-4h-4a2 2 0 0 0 -2 2z" />
                                            <path d="M10 13a2 2 0 0 1 -2 2h-4v-4h4a2 2 0 0 1 2 2z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        {{ $totalCabang }} Cabang
                                    </div>
                                    <div class="text-secondary">
                                        Terkonfigurasi
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-green text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="7" r="4" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        {{ $totalDept }} Departemen
                                    </div>
                                    <div class="text-secondary">
                                        Memiliki Jam Kerja
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($groupedByCabang->count() > 0)
                <div class="row row-cards">
                    @foreach ($groupedByCabang as $kodeCabang => $deptList)
                        @php
                            $cabangName = $deptList->first()->nama_cabang;
                            $collapseId = 'collapse_' . str_replace(' ', '_', $kodeCabang);
                        @endphp
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header cursor-pointer" data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}">
                                    <div>
                                        <h3 class="card-title text-uppercase fw-bold">{{ $cabangName }}</h3>
                                        <span class="text-muted small">Kode Cabang: {{ $kodeCabang }}</span>
                                    </div>
                                    <div class="card-actions">
                                        <span class="badge bg-blue-lt">{{ $deptList->count() }} Departemen</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-2" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M6 9l6 6l6 -6" />
                                        </svg>
                                    </div>
                                </div>
                                <div id="{{ $collapseId }}" class="collapse show">
                                    <div class="table-responsive">
                                        <table class="table table-vcenter card-table table-hover">
                                            <thead>
                                                <tr>
                                                    <th class="w-1">No</th>
                                                    <th>Departemen</th>
                                                    <th>Kode JK</th>
                                                    <th>Status</th>
                                                    <th class="w-1 text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($deptList as $idx => $d)
                                                    @php
                                                        $words = explode(' ', $d->nama_dept);
                                                        $acronym = '';
                                                        foreach ($words as $w) {
                                                            $acronym .= mb_substr($w, 0, 1);
                                                        }
                                                        $acronym = strtoupper(substr($acronym, 0, 2));
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $idx + 1 }}</td>
                                                        <td>
                                                            <div class="d-flex py-1 align-items-center">
                                                                <span class="avatar me-2"
                                                                    style="background-image: none; background-color: var(--color-surface-2); color: var(--color-muted); font-size: 0.75rem; font-weight: bold;">{{ $acronym }}</span>
                                                                <div class="flex-fill">
                                                                    <div class="font-weight-medium">{{ $d->nama_dept }}
                                                                    </div>
                                                                    <div class="text-secondary small">
                                                                        {{ $d->kode_jk_dept }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="text-blue fw-bold">{{ $d->kode_jk_dept }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="text-success d-flex align-items-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="icon icon-tabler icon-tabler-check"
                                                                    width="24" height="24" viewBox="0 0 24 24"
                                                                    stroke-width="2" stroke="currentColor" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z"
                                                                        fill="none" />
                                                                    <path d="M5 12l5 5l10 -10" />
                                                                </svg>
                                                                <span class="ms-1">Aktif</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="btn-list flex-nowrap justify-content-center">
                                                                {{-- Permission: jam-kerja-dept-view-admin --}}
                                                                @can('jam-kerja-dept-view-admin')
                                                                    <a href="{{ route('konfigurasi.showjamkerjadept', ['kode_jk_dept' => $d->kode_jk_dept]) }}"
                                                                        class="btn btn-ghost-secondary btn-icon"
                                                                        title="Lihat Detail" data-bs-toggle="tooltip">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="icon icon-tabler icon-tabler-eye"
                                                                            width="24" height="24" viewBox="0 0 24 24"
                                                                            stroke-width="2" stroke="currentColor"
                                                                            fill="none" stroke-linecap="round"
                                                                            stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                fill="none" />
                                                                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                                            <path
                                                                                d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                                        </svg>
                                                                    </a>
                                                                @endcan

                                                                {{-- Permission: jam-kerja-dept-edit-admin --}}
                                                                @can('jam-kerja-dept-edit-admin')
                                                                    <a href="{{ route('konfigurasi.editjamkerjadept', ['kode_jk_dept' => $d->kode_jk_dept]) }}"
                                                                        class="btn btn-ghost-primary btn-icon"
                                                                        title="Edit Data" data-bs-toggle="tooltip">
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

                                                                {{-- Permission: jam-kerja-dept-delete-admin --}}
                                                                @can('jam-kerja-dept-delete-admin')
                                                                    <a href="{{ route('konfigurasi.deletejamkerjadept', ['kode_jk_dept' => $d->kode_jk_dept]) }}"
                                                                        class="btn btn-ghost-danger btn-icon delete-confirm"
                                                                        data-kode_jk_dept="{{ $d->kode_jk_dept }}"
                                                                        data-dept="{{ $d->nama_dept }}"
                                                                        data-cabang="{{ $d->nama_cabang }}"
                                                                        title="Hapus" data-bs-toggle="tooltip">
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
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty">
                    <div class="empty-img"><img
                            src="https://preview.tabler.io/static/illustrations/undraw_printing_invoices_5r4r.svg"
                            height="128" alt="">
                    </div>
                    <p class="empty-title">Belum ada konfigurasi jam kerja</p>
                    <p class="empty-subtitle text-secondary">
                        Silakan tambahkan data jam kerja untuk departemen agar perhitungan presensi dapat berjalan.
                    </p>
                    @can('jam-kerja-dept-create-admin')
                        <div class="empty-action">
                            <a href="/konfigurasi/jamkerjadept/create" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Data Pertama
                            </a>
                        </div>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Set All Jam Kerja By Cabang --}}
    <div class="modal modal-blur fade" id="modalSetAllCabang" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Setup: Jam Kerja Harian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/konfigurasi/jamkerjadept/setallbycabang" method="POST" id="formSetAllCabang">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Target Cabang</label>
                            <select name="kode_cabang_set" id="kode_cabang_set" class="form-select" required
                                {{ !empty($forcedKodeCabang) ? 'disabled' : '' }}>
                                <option value="">-- Pilih Cabang --</option>
                                @foreach ($cabang as $c)
                                    <option value="{{ $c->kode_cabang }}"
                                        {{ !empty($forcedKodeCabang) && $forcedKodeCabang == $c->kode_cabang ? 'selected' : '' }}>
                                        {{ $c->nama_cabang }}
                                    </option>
                                @endforeach
                            </select>
                            @if (!empty($forcedKodeCabang))
                                <input type="hidden" name="kode_cabang_set" value="{{ $forcedKodeCabang }}">
                            @endif
                            <small class="form-hint">Pengaturan ini akan menimpa seluruh jam kerja departemen di cabang
                                yang
                                dipilih.</small>
                        </div>

                        <div class="hr-text">Konfigurasi Hari</div>

                        <div class="row">
                            {{-- Split into 2 columns for better view --}}
                            <div class="col-lg-6">
                                @php $hariList1 = ['Senin', 'Selasa', 'Rabu', 'Kamis']; @endphp
                                @foreach ($hariList1 as $hari)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $hari }}</label>
                                        <select name="jam_kerja[{{ $hari }}]"
                                            class="form-select jam-kerja-select" required>
                                            <option value="">Loading...</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-lg-6">
                                @php $hariList2 = ['Jumat', 'Sabtu', 'Minggu']; @endphp
                                @foreach ($hariList2 as $hari)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $hari }}</label>
                                        <select name="jam_kerja[{{ $hari }}]"
                                            class="form-select jam-kerja-select" required>
                                            <option value="">Loading...</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                            Terapkan Konfigurasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('mystyle')
    <style>
        .card-header.cursor-pointer {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .card-header.cursor-pointer:hover {
            background-color: var(--color-surface-2);
        }

        .avatar {
            font-family: var(--tblr-font-sans-serif-condensed);
        }
    </style>
@endpush

@push('myscript')
    <script>
        $(document).ready(function() {
            // 1. Register Delete Confirmation Event Handler (Diletakkan di paling atas demi keamanan)
            $(document).on("click", ".delete-confirm", function(e) {
                e.preventDefault();
                let btn = $(this).closest(".delete-confirm");
                let url = btn.attr("href");
                let kode_jk_dept = (btn.data("kode_jk_dept") || "").toString().trim();
                let dept = btn.data("dept");
                let cabang = btn.data("cabang");

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
                $.get('/konfigurasi/jamkerjadept/' + encodeURIComponent(kode_jk_dept) + '/relations', function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        let rels = response.relations;
                        let hasRelations = (rels.karyawan > 0);
                        
                        let messageHtml = `<p class="mb-3">Apakah Anda yakin ingin menghapus konfigurasi jam kerja departemen <b>${dept}</b> di cabang <b>${cabang}</b>?</p>`;
                        
                        if (hasRelations) {
                            messageHtml += `
                                <div class="alert alert-warning text-start mb-0">
                                    <h6 class="alert-heading mb-1 font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Dampak Penghapusan Terdeteksi:</h6>
                                    <ul class="pl-3 mb-0" style="list-style-type: disc;">
                            `;
                            
                            // Tampilkan data karyawan yang terpengaruh
                            if (rels.karyawan > 0) {
                                messageHtml += `<li class="mb-1 text-dark">Data berikut akan <b>kehilangan jadwal jam kerja</b> departemen ini dan perlu diatur kembali:`;
                                messageHtml += `<ul class="pl-3" style="list-style-type: circle;">`;
                                messageHtml += `<li><b>${rels.karyawan}</b> Karyawan di Cabang & Departemen ini</li>`;
                                messageHtml += `</ul></li>`;
                            }
                            
                            messageHtml += `
                                    </ul>
                                </div>
                            `;
                        } else {
                            messageHtml += `<p class="text-muted" style="font-size: 13px;">Tidak ada karyawan yang terpengaruh oleh penghapusan konfigurasi ini.</p>`;
                        }

                        Swal.fire({
                            title: 'Hapus Jam Kerja Dept?',
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
                                // Hapus via DELETE + CSRF (bukan GET link) agar tidak bisa dipicu dari halaman lain.
                                const form = $('<form>', { method: 'POST', action: url })
                                    .append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }))
                                    .append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                                $('body').append(form);
                                form.trigger('submit');
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

            // 2. Load jam kerja options logic
            function loadJamKerja() {
                $.ajax({
                    url: '/konfigurasi/getjamkerja',
                    type: 'GET',
                    success: function(response) {
                        var options = '<option value="">-- Pilih Jam Kerja --</option>';
                        options +=
                            '<option value="LIBUR" class="text-warning fw-bold">-- HARI LIBUR --</option>';
                        response.forEach(function(item) {
                            options +=
                                `<option value="${item.kode_jam_kerja}">${item.nama_jam_kerja} (${item.jam_masuk} - ${item.jam_pulang})</option>`;
                        });
                        $('.jam-kerja-select').html(options);
                    }
                });
            }

            $('#modalSetAllCabang').on('shown.bs.modal', function() {
                if ($('.jam-kerja-select').first().children('option').length <= 1) {
                    loadJamKerja();
                }
            });

            // 3. Initialize Tooltips
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                })
            }

            // 4. Form Submit
            $('#formSetAllCabang').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                Swal.fire({
                    title: 'Konfirmasi Bulk Update',
                    text: 'Anda akan mengatur jam kerja untuk SEMUA departemen di cabang ini. Lanjutkan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-accent)',
                    cancelButtonColor: 'var(--color-muted)',
                    confirmButtonText: 'Ya, Terapkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(response) {
                                Swal.fire('Berhasil!', response.message, 'success')
                                    .then(
                                        () => {
                                            location.reload();
                                        });
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON?.message ||
                                    'Terjadi kesalahan sistem', 'error');
                            }
                        });
                    }
                });
            });

            // 5. Auto Dismiss Alert Static (3 Detik)
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert-success, .alert-warning');
                alerts.forEach(function(alert) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        var bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } else {
                        $(alert).fadeOut();
                    }
                });
            }, 3000);
        });
    </script>
@endpush
