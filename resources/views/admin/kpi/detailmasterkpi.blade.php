@extends('layouts.admin.tabler')

@php
    $cabangTerkait = \App\Models\KPIMaster::with('cabang')->where('kode_master', $kpiMaster->kode_master)->get();
    $atasanAktif = $atasanDetails->where('is_active', 1);
    $totalBobotAtasan = $atasanAktif->sum('bobot_atasan');
    $bobotKpi = floatval($kpiMaster->bobot_kpi);
    // Absensi mendapat sisa bobot agar total 100%.
    $bobotAbsensi = 100 - ($bobotKpi + $totalBobotAtasan);
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">KPI Karyawan / Master KPI</div>
                    <h2 class="page-title">{{ $kpiMaster->nama_kpi }}</h2>
                    <p class="page-subtitle">
                        {{ $kpiMaster->kode_master }} · {{ $kpiMaster->jabatan->nama_jabatan ?? '-' }} · {{ $kpiMaster->departemen->nama_dept ?? '-' }}
                        · <span title="{{ $cabangTerkait->pluck('cabang.nama_cabang')->filter()->implode(', ') }}">{{ $cabangTerkait->count() }} cabang</span>
                    </p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        <a href="{{ route('kpi.master.index') }}" class="btn">Kembali</a>
                        @can('kpi-create-admin')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg><span class="d-none d-sm-inline">Tambah indikator</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('kpi.master.detail.update') }}" method="POST" class="d-grid gap-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="kode_master" value="{{ $kpiMaster->kode_master }}">

                {{-- Komposisi bobot performance appraisal --}}
                <section class="card" aria-labelledby="judul-komposisi">
                    <div class="card-header">
                        <h3 class="card-title" id="judul-komposisi">Komposisi performance appraisal</h3>
                        <span class="card-subtitle ms-auto">Total bobot <strong id="total_bobot" class="cell-num">100</strong>%</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Komponen</th>
                                    <th>Sumber nilai</th>
                                    <th class="text-end" style="width: 140px">Bobot</th>
                                    <th class="text-end" style="width: 160px">Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-medium">Absensi</td>
                                    <td class="text-secondary">% datang tepat waktu · otomatis</td>
                                    <td class="text-end cell-num"><span id="bobot_absensi">{{ $angka($bobotAbsensi) }}</span>%</td>
                                    <td class="text-end cell-num">90%</td>
                                </tr>
                                <tr class="row-editable">
                                    <td class="fw-medium">KPI</td>
                                    <td class="text-secondary">Workbook harian karyawan</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.5" name="bobot_kpi" id="input_bobot_kpi" class="form-control text-end"
                                                value="{{ $bobotKpi }}" aria-label="Bobot KPI">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.5" name="target_kpi" class="form-control text-end"
                                                value="{{ floatval($kpiMaster->target_kpi) }}" aria-label="Target KPI">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($atasanAktif as $atasan)
                                    <tr>
                                        <td>{{ $atasan->indikator }}</td>
                                        <td class="text-secondary">Penilaian atasan</td>
                                        <td class="text-end cell-num">{{ $angka($atasan->bobot_atasan) }}%</td>
                                        <td class="text-end cell-num">{{ $angka($atasan->target_atasan) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Indikator workbook karyawan & penilaian atasan --}}
                @foreach ([
                    ['karyawan', 'Workbook harian karyawan', 'Diisi karyawan setiap hari.', $karyawanDetails],
                    ['atasan', 'Penilaian atasan', 'Dinilai atasan per periode.', $atasanDetails],
                ] as [$jenis, $judul, $keterangan, $daftar])
                    <section class="card" aria-labelledby="judul-{{ $jenis }}">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title" id="judul-{{ $jenis }}">{{ $judul }}</h3>
                                <p class="card-subtitle mb-0">{{ $keterangan }}</p>
                            </div>
                            <span class="card-subtitle ms-auto">{{ $daftar->count() }} indikator</span>
                        </div>
                        @if ($daftar->isEmpty())
                            <div class="list-empty">
                                <p class="mb-1 fw-medium">Belum ada indikator.</p>
                                <p class="mb-0 text-secondary small">Tambahkan lewat tombol “Tambah indikator”.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table edit-table">
                                    <thead>
                                        <tr>
                                            <th class="w-1">#</th>
                                            <th>Deskripsi kinerja</th>
                                            @if ($jenis === 'karyawan')
                                                <th style="width: 150px">Indikator</th>
                                            @else
                                                <th style="width: 130px">Bobot</th>
                                                <th style="width: 130px">Target</th>
                                            @endif
                                            <th style="width: 130px">Status</th>
                                            <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($daftar as $i => $detail)
                                            @php $prefix = "details_{$jenis}[{$detail->id}]"; @endphp
                                            <tr @class(['row-inactive' => !$detail->is_active])>
                                                <td class="text-secondary cell-num">{{ $i + 1 }}</td>
                                                <td>
                                                    <textarea name="{{ $prefix }}[indikator]" class="form-control" rows="1"
                                                        aria-label="Deskripsi indikator {{ $i + 1 }}">{{ $detail->indikator }}</textarea>
                                                </td>
                                                @if ($jenis === 'karyawan')
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.5" name="{{ $prefix }}[score_indikator]" class="form-control text-end"
                                                                value="{{ floatval($detail->score_indikator) }}" aria-label="Indikator">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.5" name="{{ $prefix }}[bobot]" class="form-control text-end"
                                                                value="{{ floatval($detail->bobot_atasan) }}" aria-label="Bobot">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.5" name="{{ $prefix }}[target]" class="form-control text-end"
                                                                value="{{ floatval($detail->target_atasan) }}" aria-label="Target">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td>
                                                    <select name="{{ $prefix }}[is_active]" class="form-select form-select-sm select-is-active" aria-label="Status">
                                                        <option value="1" @selected($detail->is_active)>Aktif</option>
                                                        <option value="0" @selected(!$detail->is_active)>Nonaktif</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-icon btn-ghost-danger btn-sm btn-delete-detail"
                                                        data-id="{{ $detail->id }}" data-jenis="{{ $jenis }}"
                                                        title="Hapus indikator" aria-label="Hapus indikator {{ $i + 1 }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18"
                                                            viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M4 7l16 0" />
                                                            <path d="M10 11l0 6" />
                                                            <path d="M14 11l0 6" />
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endforeach

                <div class="save-bar">
                    <span class="text-secondary small">Perubahan bobot, target, dan indikator disimpan sekaligus.</span>
                    <button type="submit" class="btn btn-primary">Simpan semua perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <form id="delete-form" method="POST" hidden>
        @csrf
        @method('DELETE')
    </form>

    @can('kpi-create-admin')
        <div class="modal modal-blur fade" id="modal-tambah-detail" tabindex="-1" aria-labelledby="judulTambahIndikator" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahIndikator">Tambah indikator</h5>
                            <p class="modal-subtitle">{{ $kpiMaster->nama_kpi }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('kpi.master.detail.store', $kpiMaster->id) }}" method="POST" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <div class="form-grid">
                                <div>
                                    <label class="form-label required" for="selectKategori">Kategori</label>
                                    <select name="kategori" id="selectKategori" class="form-select" required>
                                        <option value="karyawan">Workbook karyawan</option>
                                        <option value="atasan">Penilaian atasan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label required" for="indikator-status">Status awal</label>
                                    <select name="is_active" id="indikator-status" class="form-select" required>
                                        <option value="1" selected>Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                                <div class="form-grid-full">
                                    <label class="form-label required" for="indikator-deskripsi">Deskripsi indikator</label>
                                    <textarea class="form-control" name="indikator" id="indikator-deskripsi" rows="3"
                                        placeholder="Contoh: Memeriksa dan menganalisa kerusakan komponen" required></textarea>
                                </div>
                                <div>
                                    <label class="form-label required" for="indikator-bobot">Bobot</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="bobot" id="indikator-bobot" placeholder="0" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div id="targetContainer" hidden>
                                    <label class="form-label required" for="inputTarget">Target</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="target" id="inputTarget" placeholder="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('myscript')
    <script>
        $(function() {
            // Textarea tumbuh mengikuti isi.
            $('.edit-table textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            }).trigger('input');

            $('.btn-delete-detail').on('click', function() {
                const url = "{{ url('kpi/detailmasterkpi') }}/" + $(this).data('id') + '/' + $(this).data('jenis') + '/delete';
                Swal.fire({
                    title: 'Hapus indikator?',
                    text: 'Indikator dihapus permanen. Perubahan lain yang belum disimpan akan hilang.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => r.isConfirmed && $('#delete-form').attr('action', url).trigger('submit'));
            });

            $(document).on('change', '.select-is-active', function() {
                $(this).closest('tr').toggleClass('row-inactive', $(this).val() == '0');
            });

            $('#selectKategori').on('change', function() {
                const atasan = $(this).val() === 'atasan';
                $('#targetContainer').prop('hidden', !atasan);
                $('#inputTarget').prop('required', atasan);
                if (!atasan) $('#inputTarget').val('');
            });

            // Bobot absensi = sisa dari 100% (tidak kurang dari 0).
            const totalAtasan = {{ (float) $totalBobotAtasan }};
            $('#input_bobot_kpi').on('input', function() {
                const kpi = parseFloat($(this).val()) || 0;
                $('#bobot_absensi').text(Math.max(0, +(100 - kpi - totalAtasan).toFixed(2)));
                $('#total_bobot').text(+(Math.max(0, 100 - kpi - totalAtasan) + kpi + totalAtasan).toFixed(2));
            }).trigger('input');
        });
    </script>
@endpush
