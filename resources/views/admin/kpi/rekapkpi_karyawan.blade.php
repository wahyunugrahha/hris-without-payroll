@extends('layouts.admin.tabler')

@php
    $periode = \Carbon\Carbon::parse($tglAwal)->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d M Y');
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">KPI Karyawan</div>
                    <h2 class="page-title">Rekap KPI Karyawan</h2>
                    <p class="page-subtitle">Periode {{ $periode }} · centang karyawan untuk menyetujui atau mencetak.</p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        <button type="button" class="btn" onclick="cetakLaporan('pdf')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                                <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                            </svg><span class="d-none d-md-inline">Cetak PDF</span>
                        </button>
                        <button type="button" class="btn" onclick="cetakLaporan('excel')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                <path d="M7 11l5 5l5 -5" />
                                <path d="M12 4l0 12" />
                            </svg><span class="d-none d-md-inline">Export Excel</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Rekap KPI karyawan">
                <form id="frmFilter" action="{{ route('kpi.rekap.karyawan') }}" method="GET" class="list-toolbar">
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Periode</span>
                            <select name="bulan" id="bulan" class="form-select form-select-sm" data-auto-submit required>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" @selected($bulan_terpilih == $i)>{{ $periodeList[$i] }}</option>
                                @endfor
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Tahun</span>
                            <select name="tahun" class="form-select form-select-sm" data-auto-submit>
                                @for ($t = date('Y'); $t >= date('Y') - 5; $t--)
                                    <option value="{{ $t }}" @selected($tahun_terpilih == $t)>{{ $t }}</option>
                                @endfor
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Cabang</span>
                            <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($cabang as $c)
                                    <option value="{{ $c->kode_cabang }}" @selected($kode_cabang_terpilih == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Departemen</span>
                            <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($departemen as $d)
                                    <option value="{{ $d->kode_dept }}" @selected($kode_dept_terpilih == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field filter-field--wide">
                            <span class="filter-label">Karyawan</span>
                            <select name="nik_pencarian" id="nik_pencarian" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua karyawan</option>
                                @foreach ($karyawan as $k)
                                    <option value="{{ $k->nik }}" @selected($nik_pencarian == $k->nik)>{{ $k->nik }} — {{ $k->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </form>

                <form action="{{ route('kpi.rekap.karyawan.bulk_approve') }}" method="POST" id="formBulkApprove">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $bulan_terpilih }}">
                    <input type="hidden" name="tahun" value="{{ $tahun_terpilih }}">

                    <div class="selection-bar" id="selection-bar" hidden>
                        <span><strong id="jumlah-terpilih">0</strong> karyawan dipilih</span>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-approve-bulk">Setujui terpilih</button>
                    </div>

                    @if ($dataApproval->isEmpty())
                        <div class="list-empty">
                            <p class="mb-1 fw-medium">Tidak ada karyawan yang cocok.</p>
                            <p class="mb-0 text-secondary small">Ubah periode atau filter.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter list-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">
                                            <input class="form-check-input m-0" type="checkbox" id="check-all" aria-label="Pilih semua karyawan">
                                        </th>
                                        <th>Karyawan</th>
                                        <th>Unit</th>
                                        <th class="text-end">Laporan</th>
                                        <th>Persetujuan</th>
                                        <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataApproval as $d)
                                        @php
                                            $selesaiHr = $d->total_laporan > 0 && $d->total_approved_hr >= $d->total_laporan;
                                            [$labelHr, $nadaHr] = match (true) {
                                                $d->total_laporan == 0 => ['Belum ada laporan', 'neutral'],
                                                $selesaiHr => ['Final HR', 'success'],
                                                default => ['Menunggu HR', 'warning'],
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input m-0 check-karyawan" type="checkbox" name="niks[]" value="{{ $d->nik }}"
                                                    data-name="{{ $d->nama_lengkap }}" aria-label="Pilih {{ $d->nama_lengkap }}">
                                            </td>
                                            <td class="cell-person">
                                                <span class="person-name" title="{{ $d->nama_lengkap }}">{{ $d->nama_lengkap }}</span>
                                                <span class="person-sub">NIK {{ $d->nik }}</span>
                                            </td>
                                            <td data-label="Unit">
                                                <div class="cell-main">{{ $d->departemen }}</div>
                                                <div class="cell-sub">{{ $d->cabang }}</div>
                                            </td>
                                            <td data-label="Laporan" class="cell-num text-lg-end">{{ $d->total_laporan }}</td>
                                            <td data-label="Persetujuan">
                                                <span class="emp-status emp-status--{{ $nadaHr }}">{{ $labelHr }}</span>
                                                <div class="cell-sub cell-num">Atasan {{ $d->total_approved_atasan }}/{{ $d->total_laporan }} · HR {{ $d->total_approved_hr }}/{{ $d->total_laporan }}</div>
                                            </td>
                                            <td class="cell-actions">
                                                <a href="{{ route('kpi.indikator.index', ['nik' => $d->nik, 'bulan' => $bulan_terpilih, 'tahun' => $tahun_terpilih]) }}"
                                                    class="btn btn-sm">Histori</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($paginator->hasPages())
                        <div class="list-footer">
                            <span class="text-secondary small">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
                            {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </form>
            </section>

            <form id="frmCetak" action="{{ route('kpi.rekap.karyawan.cetak') }}" method="GET" target="_blank" hidden>
                <input type="hidden" name="bulan" value="{{ $bulan_terpilih }}">
                <input type="hidden" name="tahun" value="{{ $tahun_terpilih }}">
                <input type="hidden" name="kode_dept" value="{{ $kode_dept_terpilih }}">
                <input type="hidden" name="kode_cabang" value="{{ $kode_cabang_terpilih }}">
                <input type="hidden" name="exportexcel" id="exportexcel" value="0">
                <div id="hidden-niks"></div>
            </form>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('#nik_pencarian').select2({ placeholder: 'Cari karyawan…', allowClear: true, width: '100%' });

            function sinkronPilihan() {
                const n = $('.check-karyawan:checked').length;
                $('#jumlah-terpilih').text(n);
                $('#selection-bar').prop('hidden', n === 0);
                $('#check-all').prop('checked', n > 0 && n === $('.check-karyawan').length)
                    .prop('indeterminate', n > 0 && n < $('.check-karyawan').length);
            }
            $('#check-all').on('change', function() {
                $('.check-karyawan').prop('checked', this.checked);
                sinkronPilihan();
            });
            $('.check-karyawan').on('change', sinkronPilihan);

            $('#btn-approve-bulk').on('click', function() {
                Swal.fire({
                    title: 'Setujui KPI terpilih?',
                    text: 'Seluruh laporan harian dan penilaian atasan karyawan terpilih disetujui secara final.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, setujui',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => r.isConfirmed && $('#formBulkApprove').trigger('submit'));
            });
        });

        // Cetak/export: karyawan yang dicentang (kosong = semua sesuai filter).
        function cetakLaporan(tipe) {
            const form = document.getElementById('frmCetak');
            const wadah = document.getElementById('hidden-niks');
            wadah.innerHTML = '';
            document.querySelectorAll('.check-karyawan:checked').forEach(function(box) {
                wadah.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'niks[]', value: box.value }));
            });
            document.getElementById('exportexcel').value = tipe === 'excel' ? '1' : '0';
            form.target = tipe === 'excel' ? '_self' : '_blank';
            form.submit();
        }
    </script>
@endpush
