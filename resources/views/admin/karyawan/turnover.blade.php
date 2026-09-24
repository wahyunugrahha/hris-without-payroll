@extends('layouts.admin.tabler')

@php
    $filterAktif = collect([request('kode_dept'), request('kode_cabang'), request('status_filter'), request('nama_karyawan')])->filter(fn ($v) => filled($v))->count();
    $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '-';
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Karyawan Keluar & Habis Kontrak</h2>
                    <p class="page-subtitle">Karyawan yang berhenti, diberhentikan, atau kontraknya berakhir.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar turnover karyawan">
                <form action="{{ route('karyawan.monitoring.turnover') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_karyawan" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Status</span>
                            <select name="status_filter" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($statusFilterOptions as $statusOption)
                                    @if (!in_array($statusOption, ['Aktif', 'Menunggu Approval']))
                                        <option value="{{ $statusOption }}" @selected($statusFilter == $statusOption)>{{ $statusOption }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Departemen</span>
                            <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($departemen as $d)
                                    <option value="{{ $d->kode_dept }}" @selected(request('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                @endforeach
                            </select>
                        </label>
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
                            <a href="{{ route('karyawan.monitoring.turnover') }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($karyawan->total() > 0)
                        Menampilkan <strong>{{ $karyawan->firstItem() }}–{{ $karyawan->lastItem() }}</strong>
                        dari <strong>{{ $karyawan->total() }}</strong> karyawan
                    @endif
                </div>

                @if ($karyawan->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada karyawan yang cocok.</p>
                        <p class="mb-0 text-secondary small">Ubah kata kunci atau filter.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Unit</th>
                                    <th>Kontrak</th>
                                    <th>Status</th>
                                    <th>Histori</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($karyawan as $data)
                                    @php
                                        $fotoUrl = !empty($data->foto) ? asset('storage/uploads/karyawan/' . $data->foto) : asset('assets/img/nophoto.png');
                                        [$labelStatus, $nadaStatus] = match ($data->status_aktif) {
                                            'Aktif' => ['Habis kontrak · ' . $data->sisa_kontrak, str_contains((string) $data->sisa_kontrak, 'Expired') ? 'danger' : 'warning'],
                                            \App\Models\Karyawan::STATUS_DIBERHENTIKAN => ['Diberhentikan', 'danger'],
                                            default => [$data->status_aktif ?: 'Nonaktif', 'neutral'],
                                        };

                                        // PKWT nonaktif tanpa tanggal keluar yang diperbarui sebelum kontrak habis = perpanjang lalu resign.
                                        $histori = $data->history_karyawan;
                                        if (empty($histori) && empty($data->tanggal_keluar) && in_array($data->status_aktif, ['Nonaktif', 'Diberhentikan'])
                                            && $data->status_karyawan == 'PKWT' && $data->updated_at && $data->tanggal_habis_kontrak
                                            && \Carbon\Carbon::parse($data->updated_at)->lt(\Carbon\Carbon::parse($data->tanggal_habis_kontrak))) {
                                            $histori = 'Perpanjang kontrak sebelum resign';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <a href="{{ route('karyawan.show', $data->nik) }}" class="person">
                                                <span class="avatar" style="background-image: url('{{ $fotoUrl }}')"></span>
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $data->nama_lengkap }}">{{ $data->nama_lengkap }}</span>
                                                    <span class="person-sub">NIK {{ $data->nik }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td data-label="Unit">
                                            <div class="cell-main">{{ $data->departemen->nama_dept ?? '-' }}</div>
                                            <div class="cell-sub">{{ $data->cabang->nama_cabang ?? '-' }}</div>
                                        </td>
                                        <td data-label="Kontrak" class="cell-num">
                                            <div class="cell-main">Habis {{ $tgl($data->tanggal_habis_kontrak) }}</div>
                                            <div class="cell-sub">Keluar {{ $tgl($data->tanggal_keluar) }}</div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                                        </td>
                                        <td data-label="Histori">
                                            <div class="cell-clamp" title="{{ $histori }}">{{ $histori ?: '-' }}</div>
                                            @if ($data->statemen)
                                                <div class="cell-sub cell-clamp" title="{{ $data->statemen }}">{{ $data->statemen }}</div>
                                            @endif
                                        </td>
                                        <td class="cell-actions">
                                            <x-admin.row-menu :label="$data->nama_lengkap">
                                                <a href="{{ route('karyawan.show', $data->nik) }}" class="dropdown-item">Lihat profil</a>
                                                @if ($data->status_aktif !== \App\Models\Karyawan::STATUS_DIBERHENTIKAN)
                                                    @can('karyawan-edit-admin')
                                                        <button type="button" class="dropdown-item edit-history" data-nik="{{ $data->nik }}"
                                                            data-nama="{{ $data->nama_lengkap }}"
                                                            data-history="{{ $data->history_karyawan }}" data-statemen="{{ $data->statemen }}"
                                                            data-tgl="{{ optional($data->tanggal_habis_kontrak)->format('Y-m-d') }}"
                                                            data-keluar="{{ $data->tanggal_keluar ? \Carbon\Carbon::parse($data->tanggal_keluar)->format('Y-m-d') : '' }}"
                                                            data-tmt="{{ $data->tmt ? \Carbon\Carbon::parse($data->tmt)->format('Y-m-d') : '' }}"
                                                            data-masuk="{{ $data->tanggal_awal_kontrak ? \Carbon\Carbon::parse($data->tanggal_awal_kontrak)->format('Y-m-d') : '' }}"
                                                            data-status="{{ $data->status_aktif }}">Perbarui histori</button>
                                                    @endcan
                                                @endif
                                            </x-admin.row-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($karyawan->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $karyawan->currentPage() }} dari {{ $karyawan->lastPage() }}</span>
                        {{ $karyawan->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-edithistory" tabindex="-1" aria-labelledby="judulEditHistori" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulEditHistori">Perbarui histori</h5>
                        <p class="modal-subtitle" id="edit_nama">-</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="" method="POST" id="formEditHistory" class="modal-form-body">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <fieldset class="form-section">
                            <legend class="form-section-title">Status & kontrak</legend>
                            <div class="form-grid">
                                <div>
                                    <label class="form-label" for="edit_status">Status aktif</label>
                                    <select name="status_aktif" id="edit_status" class="form-select">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                        <option value="Diberhentikan">Diberhentikan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="edit_keluar">Tanggal keluar</label>
                                    <input type="date" class="form-control" name="tanggal_keluar" id="edit_keluar">
                                    <div class="form-hint">Wajib bila Nonaktif atau Diberhentikan.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="edit_tmt">TMT (tanggal bergabung)</label>
                                    <input type="date" class="form-control" name="tmt" id="edit_tmt">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_masuk">Awal kontrak</label>
                                    <input type="date" class="form-control" name="tanggal_awal_kontrak" id="edit_masuk">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_tanggal">Akhir kontrak</label>
                                    <input type="date" class="form-control" name="tanggal_habis_kontrak" id="edit_tanggal">
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="form-section">
                            <legend class="form-section-title">Catatan</legend>
                            <div class="mb-3">
                                <label class="form-label" for="edit_history">Histori / alasan keluar</label>
                                <textarea class="form-control" name="history_karyawan" id="edit_history" rows="3"
                                    placeholder="Contoh: Resign, habis kontrak, perpanjang kontrak…"></textarea>
                            </div>
                            <div>
                                <label class="form-label" for="edit_statemen">Keterangan tambahan</label>
                                <textarea class="form-control" name="statemen" id="edit_statemen" rows="2"
                                    placeholder="Contoh: sudah mengembalikan APD"></textarea>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan histori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            const urlUpdate = "{{ route('karyawan.updatehistory', ':nik') }}";

            $('.edit-history').on('click', function() {
                const d = $(this).data();
                $('#edit_nama').text(d.nama + ' · NIK ' + d.nik);
                $('#edit_tanggal').val(d.tgl);
                $('#edit_keluar').val(d.keluar);
                $('#edit_tmt').val(d.tmt);
                $('#edit_masuk').val(d.masuk);
                $('#edit_status').val(d.status);
                $('#edit_history').val(d.history);
                $('#edit_statemen').val(d.statemen);
                $('#formEditHistory').attr('action', urlUpdate.replace(':nik', d.nik));
                $('#modal-edithistory').modal('show');
            });
        });
    </script>
@endpush
