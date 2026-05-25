@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Monitoring Karyawan
                    </div>
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users-x" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                            <path d="M22 22l-5 -5" />
                            <path d="M17 22l5 -5" />
                        </svg>
                        Data Karyawan Keluar / Berhenti
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
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

                    <div class="card">
                        <div class="card-body border-bottom py-3">
                            <form action="{{ route('karyawan.monitoring.turnover') }}" method="GET">
                                <div class="row g-2 align-items-center">
                                    <div class="col-6 col-xl-2">
                                        <select name="kode_dept" class="form-select">
                                            <option value="">Semua Departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}"
                                                    {{ request('kode_dept') == $d->kode_dept ? 'selected' : '' }}>
                                                    {{ $d->nama_dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-xl-2">
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
                                    <div class="col-6 col-xl-2">
                                        <select name="status_filter" class="form-select">
                                            <option value="">Semua Status</option>
                                            @foreach ($statusFilterOptions as $statusOption)
                                                @if (!in_array($statusOption, ['Aktif', 'Menunggu Approval']))
                                                    <option value="{{ $statusOption }}"
                                                        {{ $statusFilter == $statusOption ? 'selected' : '' }}>
                                                        {{ $statusOption }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-xl-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="nama_karyawan"
                                                value="{{ request('nama_karyawan') }}" placeholder="Cari Nama atau NIK...">
                                            <button type="submit" class="btn btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icon-tabler-search">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                    <path d="M21 21l-6 -6" />
                                                </svg>
                                                Cari Data
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
                                        <th>Karyawan</th>
                                        <th>Departemen / PT</th>
                                        <th>Tanggal Keluar</th>
                                        <th>Habis Kontrak</th>
                                        <th>Histori / Keterangan</th>
                                        <th class="w-1">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($karyawan as $data)
                                        @php
                                            $fotoUrl = !empty($data->foto)
                                                ? asset('storage/uploads/karyawan/' . $data->foto)
                                                : asset('assets/img/nophoto.png');
                                        @endphp
                                        <tr>
                                            <td data-label="Karyawan">
                                                <div class="d-flex py-1 align-items-center">
                                                    <span class="avatar me-2"
                                                        style="background-image: url({{ $fotoUrl }})"></span>
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">{{ $data->nama_lengkap }}</div>
                                                        <div class="text-muted">NIK: {{ $data->nik }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Departemen">
                                                <div>{{ $data->departemen->nama_dept ?? '-' }}</div>
                                                <div class="text-muted text-truncate">
                                                    {{ $data->cabang->nama_cabang ?? '-' }}</div>
                                            </td>
                                            <td data-label="Tanggal Keluar">
                                                {{ $data->tanggal_keluar ? \Carbon\Carbon::parse($data->tanggal_keluar)->format('d-m-Y') : '-' }}
                                            </td>
                                            <td data-label="Habis Kontrak">
                                                <div class="mb-1">
                                                    {{ $data->tanggal_habis_kontrak ? \Carbon\Carbon::parse($data->tanggal_habis_kontrak)->format('d-m-Y') : '-' }}
                                                </div>
                                                @if ($data->status_aktif == 'Aktif')
                                                    <span
                                                        class="badge {{ str_contains($data->sisa_kontrak, 'Expired') ? 'bg-red-lt' : 'bg-warning-lt' }}">Habis
                                                        Kontrak: {{ $data->sisa_kontrak }}</span>
                                                @elseif ($data->status_aktif == 'Diberhentikan')
                                                    <span class="badge bg-red-lt">Diberhentikan</span>
                                                @else
                                                    <span class="badge bg-secondary-lt">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td data-label="Histori">
                                                @php
                                                    $displayHistory = $data->history_karyawan;
                                                    if (
                                                        empty($displayHistory) &&
                                                        empty($data->tanggal_keluar) &&
                                                        in_array($data->status_aktif, ['Nonaktif', 'Diberhentikan']) &&
                                                        $data->status_karyawan == 'PKWT'
                                                    ) {
                                                        if (
                                                            $data->updated_at &&
                                                            $data->tanggal_habis_kontrak &&
                                                            \Carbon\Carbon::parse($data->updated_at)->lt(
                                                                \Carbon\Carbon::parse($data->tanggal_habis_kontrak),
                                                            )
                                                        ) {
                                                            $displayHistory = 'Perpanjang kontrak sebelum resign';
                                                        }
                                                    }
                                                @endphp
                                                {{ $displayHistory ?: '-' }}
                                                @if ($data->statemen)
                                                    <br><small class="text-muted">Statement: {{ $data->statemen }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    @php
                                                        $linkStatus =
                                                            $data->status_aktif === 'Aktif'
                                                                ? 'Habis Kontrak'
                                                                : $data->status_aktif;
                                                    @endphp
                                                    <a href="{{ route('karyawan.show', $data->nik) }}"
                                                        class="btn btn-ghost-secondary btn-icon" title="Lihat Detail">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-file-description">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                            <path
                                                                d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" />
                                                            <path d="M9 17h6" />
                                                            <path d="M9 13h6" />
                                                        </svg>
                                                    </a>
                                                    @if ($data->status_aktif !== \App\Models\Karyawan::STATUS_DIBERHENTIKAN)
                                                        @can('karyawan-edit-admin')
                                                            <button type="button"
                                                                class="btn btn-ghost-primary btn-icon edit-history"
                                                                data-nik="{{ $data->nik }}"
                                                                data-history="{{ $data->history_karyawan }}"
                                                                data-statemen="{{ $data->statemen }}"
                                                                data-tgl="{{ optional($data->tanggal_habis_kontrak)->format('Y-m-d') }}"
                                                                data-keluar="{{ $data->tanggal_keluar ? \Carbon\Carbon::parse($data->tanggal_keluar)->format('Y-m-d') : '' }}"
                                                                data-tmt="{{ $data->tmt ? \Carbon\Carbon::parse($data->tmt)->format('Y-m-d') : '' }}"
                                                                data-masuk="{{ $data->tanggal_awal_kontrak ? \Carbon\Carbon::parse($data->tanggal_awal_kontrak)->format('Y-m-d') : '' }}"
                                                                data-status="{{ $data->status_aktif }}"
                                                                title="Update Histori">
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
                                                            </button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($karyawan->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data karyawan yang
                                                berhenti/keluar.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center">
                            {{ $karyawan->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UBAH HISTORI --}}
    <div class="modal modal-blur fade" id="modal-edithistory" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Histori Karyawan Berhenti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="formEditHistory">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">TMT (Join Date)</label>
                                <input type="date" class="form-control" name="tmt" id="edit_tmt">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Awal Kontrak</label>
                                <input type="date" class="form-control" name="tanggal_awal_kontrak" id="edit_masuk">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status Aktif</label>
                                <select name="status_aktif" id="edit_status" class="form-select">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                    <option value="Diberhentikan">Diberhentikan</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Akhir Kontrak</label>
                                <input type="date" class="form-control" name="tanggal_habis_kontrak"
                                    id="edit_tanggal">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Berhenti / Keluar</label>
                            <input type="date" class="form-control" name="tanggal_keluar" id="edit_keluar">
                            <small class="text-muted">Wajib diisi jika status karyawan Nonaktif atau Diberhentikan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Histori Karyawan / Alasan Keluar</label>
                            <textarea class="form-control" name="history_karyawan" id="edit_history" rows="3"
                                placeholder="Contoh: Perpanjang Kontrak, Resign, Habis Kontrak, dll..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Tambahan / Statemen</label>
                            <textarea class="form-control" name="statemen" id="edit_statemen" rows="2"
                                placeholder="Contoh: Mengembalikan APD, dll..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Histori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('.edit-history').click(function() {
                var nik = $(this).data('nik');
                var history = $(this).data('history');
                var statemen = $(this).data('statemen');
                var tgl = $(this).data('tgl');
                var tmt = $(this).data('tmt');
                var masuk = $(this).data('masuk');
                var keluar = $(this).data('keluar');
                var status = $(this).data('status');

                $('#edit_tanggal').val(tgl);
                $('#edit_keluar').val(keluar);
                $('#edit_tmt').val(tmt);
                $('#edit_masuk').val(masuk);
                $('#edit_status').val(status);
                $('#edit_history').val(history);
                $('#edit_statemen').val(statemen);

                @php
                    $routePrefix = route('karyawan.updatehistory', ':nik');
                @endphp
                var actionUrl = "{{ $routePrefix }}".replace(':nik', nik);
                $('#formEditHistory').attr('action', actionUrl);

                $('#modal-edithistory').modal('show');
            });

            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert-success, .alert-warning');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>
@endpush
