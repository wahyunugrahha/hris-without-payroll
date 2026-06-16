@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Performance</div>
                    <h2 class="page-title">
                        Dashboard KPI
                    </h2>
                    <div class="text-muted">
                        Monitoring KPI Tanggal: <span
                            class="fw-bold">{{ \Carbon\Carbon::parse($selectedTanggal)->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- FILTER --}}
            <form method="GET" class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tanggal Monitoring</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ $selectedTanggal }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cabang</label>
                            <select name="kode_cabang" class="form-select">
                                <option value="">Semua Cabang</option>
                                @foreach ($cabangs as $cabang)
                                    <option value="{{ $cabang->kode_cabang }}"
                                        {{ $selectedCabang == $cabang->kode_cabang ? 'selected' : '' }}>
                                        {{ $cabang->nama_cabang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Departemen</label>
                            <select name="kode_dept" class="form-select">
                                <option value="">Semua Dept</option>
                                @foreach ($departemens as $dept)
                                    <option value="{{ $dept->kode_dept }}"
                                        {{ $selectedDept == $dept->kode_dept ? 'selected' : '' }}>
                                        {{ $dept->nama_dept }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-filter me-1"></i> Filter Data
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- TOP STATS & PIE CHART --}}
            <div class="row row-cards mb-3">
                <div class="col-lg-8">
                    <div class="row row-cards">
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body d-flex align-items-center">
                                    <span class="bg-blue text-white avatar me-3"><i class="ti ti-target"></i></span>
                                    <div>
                                        <div class="subheader">KPI Aktif</div>
                                        <div class="h2 mb-0">{{ $totalMaster }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body d-flex align-items-center">
                                    <span class="bg-cyan text-white avatar me-3"><i class="ti ti-list-check"></i></span>
                                    <div>
                                        <div class="subheader">Total Indikator</div>
                                        <div class="h2 mb-0">{{ $totalIndikator }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Status Cards --}}
                        @php
                            $statuses = [
                                'draft' => ['color' => 'secondary', 'label' => 'Draft'],
                                'submitted' => ['color' => 'primary', 'label' => 'Submitted'],
                                'approved' => ['color' => 'success', 'label' => 'Approved'],
                                'rejected' => ['color' => 'danger', 'label' => 'Rejected'],
                            ];
                        @endphp

                        @foreach ($statuses as $key => $props)
                            <div class="col-sm-6 col-md-3">
                                <div class="card">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted mb-1">{{ $props['label'] }}</div>
                                        {{-- Gunakan ?? 0 untuk menghindari error jika key tidak ada di collection --}}
                                        <div class="h2 mb-0 text-{{ $props['color'] }}">{{ $statusCount[$key] ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Komposisi Status</h3>
                            <div id="chart-status-kpi" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th class="text-center">Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestDaily as $item)
                                <tr>
                                    <td><small class="text-muted">{{ $item->nik }}</small></td>
                                    <td class="fw-bold">{{ $item->karyawan->nama_lengkap ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="badge bg-{{ $item->status == 'approved' ? 'success' : ($item->status == 'submitted' ? 'primary' : ($item->status == 'rejected' ? 'danger' : 'secondary')) }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td style="width: 25%">
                                        <div class="d-flex align-items-center">
                                            <div class="progress progress-xs w-100 me-2">
                                                <div class="progress-bar bg-primary"
                                                    style="width: {{ $item->bobot_tercapai }}%"></div>
                                            </div>
                                            <small>{{ number_format($item->bobot_tercapai, 1) }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">Tidak ada data untuk filter ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Navigasi Pagination --}}
                @if ($latestDaily->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        <p class="m-0 text-muted">
                            Showing <span>{{ $latestDaily->firstItem() }}</span> to
                            <span>{{ $latestDaily->lastItem() }}</span> of <span>{{ $latestDaily->total() }}</span>
                            entries
                        </p>
                        <ul class="pagination m-0 ms-auto">
                            {{ $latestDaily->links('pagination::bootstrap-4') }}
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const options = {
                chart: {
                    type: 'donut',
                    height: 250,
                    animations: {
                        enabled: true
                    }
                },
                // Memberikan nilai default 0 jika data tidak ditemukan di controller
                series: [
                    {{ $statusCount['draft'] ?? 0 }},
                    {{ $statusCount['submitted'] ?? 0 }},
                    {{ $statusCount['approved'] ?? 0 }},
                    {{ $statusCount['rejected'] ?? 0 }}
                ],
                labels: ["Draft", "Submitted", "Approved", "Rejected"],
                colors: ['#6c757d', '#206bc4', '#094b87', '#d63939'],
                legend: {
                    position: 'bottom',
                    offsetY: 0
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total KPI',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    fillSeriesColor: false
                },
            };
            const chart = new ApexCharts(document.querySelector("#chart-status-kpi"), options);
            chart.render();
        });
    </script>
@endpush
