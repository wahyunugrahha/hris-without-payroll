@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan</div>
                    <h2 class="page-title">Rekap Performance Appraisal Karyawan</h2>
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
                            <form id="frmLaporan" action="{{ route('kpi.report.cetak') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Bulan</label>
                                            <select name="bulan" class="form-select" required>
                                                @foreach ($periodeList as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ $defaultBulan == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Tahun</label>
                                            <select name="tahun" class="form-select" required>
                                                @for ($t = date('Y'); $t >= date('Y') - 5; $t--)
                                                    <option value="{{ $t }}"
                                                        {{ $defaultTahun == $t ? 'selected' : '' }}>
                                                        {{ $t }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Cabang</label>
                                            <select name="kode_cabang" id="kode_cabang" class="form-select">
                                                <option value="">Semua Cabang</option>
                                                @foreach ($cabang as $c)
                                                    <option value="{{ $c->kode_cabang }}">
                                                        {{ $c->nama_cabang }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Departemen</label>
                                            <select name="kode_dept" id="kode_dept" class="form-select" required>
                                                <option value="">Pilih Departemen</option>
                                                @foreach ($departemen as $d)
                                                    <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Pilih Karyawan (Opsional)</label>
                                            <select name="nik" id="nik" class="form-select select2-nik">
                                                <option value="">Semua Karyawan</option>
                                                @foreach ($karyawan as $k)
                                                    <option value="{{ $k->nik }}" data-dept="{{ $k->kode_dept }}"
                                                        data-cabang="{{ $k->kode_cabang }}"
                                                        data-nama="{{ $k->nama_lengkap }}">
                                                        {{ $k->nik }} - {{ $k->nama_lengkap }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-warning w-100"
                                                onclick="document.getElementById('frmLaporan').target = '_blank'">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-printer" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
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
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <button type="submit" name="exportexcel" value="1"
                                                class="btn btn-primary w-100"
                                                onclick="document.getElementById('frmLaporan').target = '_self'">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-download" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                                    <path d="M7 11l5 5l5 -5" />
                                                    <path d="M12 4l0 12" />
                                                </svg>
                                                Export Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
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
            $('#nik').select2({
                placeholder: "Cari Nama Karyawan...",
                allowClear: true,
                width: '100%'
            });

            var allKaryawanOptions = [];
            $('#nik option').each(function() {
                if ($(this).val() !== '') {
                    allKaryawanOptions.push({
                        id: $(this).val(),
                        text: $(this).text().trim(),
                        cabang: $(this).data('cabang'),
                        dept: $(this).data('dept'),
                        nama: $(this).data('nama')
                    });
                }
            });

            function filterKaryawan() {
                var selectedCabang = $('#kode_cabang').val();
                var selectedDept = $('#kode_dept').val();

                $('#nik').empty().trigger('change');
                var newOption = new Option("Semua Karyawan", "", true, true);
                $('#nik').append(newOption);

                var filteredData = allKaryawanOptions.filter(function(item) {
                    var cabangMatch = !selectedCabang || item.cabang == selectedCabang;
                    var deptMatch = !selectedDept || item.dept == selectedDept;
                    return cabangMatch && deptMatch;
                });

                filteredData.sort(function(a, b) {
                    return (a.nama || "").localeCompare(b.nama || "");
                });

                $.each(filteredData, function(index, item) {
                    var option = new Option(item.text, item.id, false, false);
                    $(option).attr('data-cabang', item.cabang);
                    $(option).attr('data-dept', item.dept);
                    $(option).attr('data-nama', item.nama);
                    $('#nik').append(option);
                });

                $('#nik').trigger('change');
            }

            $('#kode_cabang, #kode_dept').on('change', function() {
                filterKaryawan();
            });

            filterKaryawan();
        });
    </script>
@endpush
