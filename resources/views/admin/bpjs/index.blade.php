@extends('layouts.admin.tabler')

@php
    $statusBpjs = ['pending' => ['Menunggu', 'warning'], 'processed' => ['Diproses', 'success']];
    $statusKini = request('status');
    $urlStatus = fn ($s) => route('admin.bpjs.index', array_merge(request()->except(['page', 'status']), $s === null ? [] : ['status' => $s]));
    $filterAktif = collect([request('dari'), request('sampai'), request('nama_lengkap')])->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan</div>
                    <h2 class="page-title">Pengajuan BPJS</h2>
                    <p class="page-subtitle">Permintaan pendaftaran & pembaruan data BPJS dari karyawan.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar pengajuan BPJS">
                <nav class="list-tabs" aria-label="Status pengajuan">
                    <a href="{{ $urlStatus(null) }}" class="list-tab {{ blank($statusKini) ? 'is-current' : '' }}" @if (blank($statusKini)) aria-current="page" @endif>Semua</a>
                    @foreach ($statusBpjs as $nilai => [$label])
                        <a href="{{ $urlStatus($nilai) }}" class="list-tab {{ $statusKini === $nilai ? 'is-current' : '' }}" @if ($statusKini === $nilai) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>

                <form action="{{ route('admin.bpjs.index') }}" method="GET" class="list-toolbar" autocomplete="off">
                    @if (filled($statusKini))
                        <input type="hidden" name="status" value="{{ $statusKini }}">
                    @endif
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_lengkap" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Diajukan dari</span>
                            <input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm">
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Sampai</span>
                            <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm">
                        </label>
                        @if ($filterAktif > 0)
                            <a href="{{ $urlStatus($statusKini ?: null) }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($pengajuan->total() > 0)
                        Menampilkan <strong>{{ $pengajuan->firstItem() }}–{{ $pengajuan->lastItem() }}</strong>
                        dari <strong>{{ $pengajuan->total() }}</strong> pengajuan
                    @endif
                </div>

                @if ($pengajuan->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Belum ada pengajuan BPJS.</p>
                        <p class="mb-0 text-secondary small">Pengajuan dari aplikasi karyawan akan tampil di sini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Diajukan</th>
                                    <th>Status</th>
                                    <th>Diproses oleh</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pengajuan as $p)
                                    @php [$labelStatus, $nadaStatus] = $statusBpjs[$p->status] ?? [$p->status, 'neutral']; @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <a href="{{ route('admin.bpjs.show', $p->id) }}" class="person">
                                                <span class="min-w-0">
                                                    <span class="person-name">{{ $p->nama_lengkap }}</span>
                                                    <span class="person-sub">NIK {{ $p->nik }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td data-label="Diajukan" class="cell-num">{{ optional($p->requested_at ?? $p->created_at)->format('d M Y H:i') }}</td>
                                        <td data-label="Status"><span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span></td>
                                        <td data-label="Diproses">{{ $p->processed_by ?? '-' }}</td>
                                        <td class="cell-actions">
                                            <a href="{{ route('admin.bpjs.show', $p->id) }}" class="btn btn-sm">{{ $p->status === 'pending' ? 'Proses' : 'Detail' }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($pengajuan->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $pengajuan->currentPage() }} dari {{ $pengajuan->lastPage() }}</span>
                        {{ $pengajuan->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
