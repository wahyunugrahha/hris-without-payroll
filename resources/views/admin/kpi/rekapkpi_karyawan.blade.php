@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan & Persetujuan</div>
                    <h2 class="page-title">Rekap KPI Karyawan</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body rekap-kpi-page">
        <div class="container-xl">

            {{-- ALERTS --}}

            <div class="row">
                {{-- BAGIAN FILTER --}}
                <div class="col-12">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="card-title">Filter Data</h3>
                        </div>
                        <div class="card-body">
                            <form id="frmFilter" action="{{ route('kpi.rekap.karyawan') }}" method="GET">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label class="form-label">Pilih Bulan</label>
                                        <select name="bulan" id="bulan" class="form-select"
                                            onchange="this.form.submit()" required>
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }}"
                                                    {{ $bulan_terpilih == $i ? 'selected' : '' }}>
                                                    {{ $periodeList[$i] }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label class="form-label">Tahun</label>
                                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                                            @for ($t = date('Y'); $t >= date('Y') - 5; $t--)
                                                <option value="{{ $t }}"
                                                    {{ $tahun_terpilih == $t ? 'selected' : '' }}>
                                                    {{ $t }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label class="form-label">Cabang</label>
                                        <select name="kode_cabang" class="form-select" onchange="this.form.submit()">
                                            <option value="">Semua Cabang</option>
                                            @foreach ($cabang as $c)
                                                <option value="{{ $c->kode_cabang }}"
                                                    {{ $kode_cabang_terpilih == $c->kode_cabang ? 'selected' : '' }}>
                                                    {{ $c->nama_cabang }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label class="form-label">Departemen</label>
                                        <select name="kode_dept" class="form-select" onchange="this.form.submit()">
                                            <option value="">Semua Departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}"
                                                    {{ $kode_dept_terpilih == $d->kode_dept ? 'selected' : '' }}>
                                                    {{ $d->nama_dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mt-2">
                                        <div class="form-group mb-2">
                                            <label class="form-label">Cari Karyawan (Opsional)</label>
                                            <select name="nik_pencarian" id="nik_pencarian" class="form-select select2-nik" onchange="this.form.submit()">
                                                <option value="">Semua Karyawan</option>
                                                @foreach ($karyawan as $k)
                                                    <option value="{{ $k->nik }}" data-dept="{{ $k->kode_dept }}" data-cabang="{{ $k->kode_cabang }}" data-nama="{{ $k->nama_lengkap }}" {{ $nik_pencarian == $k->nik ? 'selected' : '' }}>
                                                        {{ $k->nik }} - {{ $k->nama_lengkap }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- BAGIAN LIST KARYAWAN & BULK APPROVE --}}
                <div class="col-12">
                    <div class="card shadow-sm">
                        <form action="{{ route('kpi.rekap.karyawan.bulk_approve') }}" method="POST" id="formBulkApprove">
                            @csrf
                            <input type="hidden" name="bulan" value="{{ $bulan_terpilih }}">
                            <input type="hidden" name="tahun" value="{{ $tahun_terpilih }}">

                            <div
                                class="card-header d-flex justify-content-between align-items-center bg-light flex-wrap gap-3">
                                <div>
                                    <h3 class="card-title m-0">Daftar Karyawan</h3>
                                    <small class="text-muted d-block mt-1">
                                        <b>Periode:</b>
                                        {{ \Carbon\Carbon::parse($tglAwal)->translatedFormat('d M Y') }} s/d
                                        {{ \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d M Y') }}
                                    </small>
                                </div>

                                {{-- Tombol Aksi --}}
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="btn-approve-bulk">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checks"
                                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M7 12l5 5l10 -10" />
                                            <path d="M2 12l5 5m5 -5l5 -5" />
                                        </svg>
                                        Approve Checklist
                                    </button>

                                    <button type="button" class="btn" onclick="cetakLaporan('pdf')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer"
                                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                            <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                                            <path
                                                d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                                        </svg>
                                        Cetak PDF
                                    </button>

                                    <button type="button" class="btn" onclick="cetakLaporan('excel')">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-file-spreadsheet" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                            <path
                                                d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                            <path d="M8 11h8v7h-8z" />
                                            <path d="M8 15h8" />
                                            <path d="M11 11v7" />
                                        </svg>
                                        Export Excel
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-vcenter table-hover card-table">
                                    <thead>
                                        <tr>
                                            <th class="w-1 text-center">
                                                <input class="form-check-input m-0 align-middle" type="checkbox"
                                                    id="check-all">
                                            </th>
                                            <th>NIK</th>
                                            <th>Nama Karyawan</th>
                                            <th>Departemen / Cabang</th>
                                            <th class="text-center">Total Workbook</th>
                                            <th class="text-center">Approve Atasan</th>
                                            <th class="text-center">Approve HR</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dataApproval as $d)
                                            @php
                                                $isAtasanFullyApproved =
                                                    $d->total_laporan > 0 &&
                                                    $d->total_laporan == $d->total_approved_atasan &&
                                                    in_array($d->status_penilaian_atasan, [
                                                        'submitted',
                                                        'approved_by_hr',
                                                    ]);

                                                $dataReady = $isAtasanFullyApproved ? 'yes' : 'no';
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <input class="form-check-input m-0 align-middle check-karyawan"
                                                        type="checkbox" name="niks[]" value="{{ $d->nik }}"
                                                        data-ready="{{ $dataReady }}"
                                                        data-name="{{ $d->nama_lengkap }}">
                                                </td>
                                                <td class="text-muted">{{ $d->nik }}</td>
                                                <td class="fw-bold">{{ $d->nama_lengkap }}</td>
                                                <td class="text-muted">{{ $d->departemen }} / {{ $d->cabang }}</td>
                                                <td class="text-center">{{ $d->total_laporan }}</td>

                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $d->total_approved_atasan > 0 ? 'bg-success-lt' : 'bg-secondary-lt' }}">{{ $d->total_approved_atasan }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $d->total_approved_hr > 0 ? 'bg-primary-lt' : 'bg-secondary-lt' }}">{{ $d->total_approved_hr }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('kpi.indikator.index', ['nik' => $d->nik, 'bulan' => $bulan_terpilih, 'tahun' => $tahun_terpilih]) }}"
                                                        class="btn btn-sm btn-outline-primary shadow-sm"
                                                        title="Lihat Laporan Harian">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-list-details"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M13 5h8" />
                                                            <path d="M13 9h5" />
                                                            <path d="M13 15h8" />
                                                            <path d="M13 19h5" />
                                                            <path
                                                                d="M3 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                                            <path
                                                                d="M3 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                                        </svg>
                                                        Cek Histori
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">Tidak ada data
                                                    karyawan ditemukan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- PAGINATION --}}
                            <div class="card-footer d-flex align-items-center">
                                {{ $paginator->links('pagination::bootstrap-5') }}
                            </div>
                        </form>

                        {{-- FORM HIDDEN UNTUK CETAK --}}
                        <form id="frmCetak" action="{{ route('kpi.rekap.karyawan.cetak') }}" method="POST"
                            target="_blank" style="display: none;">
                            @csrf
                            <input type="hidden" name="bulan" value="{{ $bulan_terpilih }}">
                            <input type="hidden" name="tahun" value="{{ $tahun_terpilih }}">
                            <input type="hidden" name="kode_dept" value="{{ $kode_dept_terpilih }}">
                            <input type="hidden" name="kode_cabang" value="{{ $kode_cabang_terpilih }}">
                            <input type="hidden" name="exportexcel" id="exportexcel" value="0">

                            <div id="hidden-niks"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#nik_pencarian').select2({
                placeholder: "Cari Nama Karyawan...",
                allowClear: true,
                width: '100%'
            });
            // Logic Check All
            $('#check-all').change(function() {
                $('.check-karyawan').prop('checked', $(this).prop('checked'));
            });

            $('.check-karyawan').change(function() {
                if ($('.check-karyawan:checked').length === $('.check-karyawan').length) {
                    $('#check-all').prop('checked', true);
                } else {
                    $('#check-all').prop('checked', false);
                }
            });

            // LOGIC TOMBOL APPROVE BULK
            $('#btn-approve-bulk').click(function(e) {
                e.preventDefault();

                let checkedBoxes = $('.check-karyawan:checked');

                // 1. Cek apakah ada minimal 1 checkbox yang dicentang
                if (checkedBoxes.length === 0) {
                    Swal.fire('Peringatan', 'Silakan centang minimal satu karyawan terlebih dahulu!',
                        'warning');
                    return;
                }

                // 2. Validasi apakah semua yang dicentang sudah diapprove oleh Atasan
                let notReadyNames = [];
                checkedBoxes.each(function() {
                    if ($(this).attr('data-ready') === 'no') {
                        notReadyNames.push($(this).attr('data-name'));
                    }
                });

                // if (notReadyNames.length > 0) {
                //     let textMsg = "Karyawan berikut belum disetujui sepenuhnya oleh Atasan:\n\n";
                //     textMsg += notReadyNames.map(name => "- " + name).join("\n");
                //     textMsg += "\n\nHarap hubungi Atasan terkait sebelum melakukan Approve HR.";

                //     Swal.fire({
                //         title: 'Approval Ditolak!',
                //         text: textMsg,
                //         icon: 'error',
                //         confirmButtonColor: 'var(--color-danger)',
                //         confirmButtonText: 'Mengerti'
                //     });
                //     return; // Menghentikan proses
                // }

                // 3. Jika Lolos Validasi Atasan, Lanjut Konfirmasi Approve HR
                Swal.fire({
                    title: 'Setujui Semua KPI Terpilih?',
                    text: "Tindakan ini akan meng-approve (Final HR) seluruh laporan harian dan penilaian atasan karyawan yang Anda centang.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-accent)',
                    cancelButtonColor: 'var(--color-muted)',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formBulkApprove').submit();
                    }
                });
            });
        });

        // Logic Fungsi Cetak
        function cetakLaporan(tipe) {
            let formCetak = document.getElementById('frmCetak');
            let hiddenNiksContainer = document.getElementById('hidden-niks');

            // Bersihkan dulu kontainer NIK lama
            hiddenNiksContainer.innerHTML = '';

            // Ambil semua checkbox yang tercentang dari form tabel
            let checkedBoxes = document.querySelectorAll('.check-karyawan:checked');

            // Gandakan input checkbox ke dalam form cetak
            checkedBoxes.forEach(function(box) {
                let hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'niks[]';
                hiddenInput.value = box.value;
                hiddenNiksContainer.appendChild(hiddenInput);
            });

            // Tentukan target Cetak (PDF / Excel)
            if (tipe === 'excel') {
                document.getElementById('exportexcel').value = "1";
                formCetak.target = '_self';
            } else {
                document.getElementById('exportexcel').value = "0";
                formCetak.target = '_blank';
            }

            formCetak.submit();
        }
    </script>
@endpush
