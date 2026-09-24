@extends('layouts.admin.tabler')

@php
    $statusGaji = ['pending' => ['Menunggu', 'warning'], 'approved' => ['Disetujui', 'success'], 'rejected' => ['Ditolak', 'danger']];
    $statusKini = request('status');
    $urlStatus = fn ($s) => route('admin.kenaikan_gaji.index', array_merge(request()->except(['page', 'status']), $s === null ? [] : ['status' => $s]));
    $filterAktif = collect([request('bulan'), request('tahun'), request('nama_karyawan')])->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan</div>
                    <h2 class="page-title">Pengajuan Kenaikan Gaji</h2>
                    <p class="page-subtitle">Persentase kenaikan yang diajukan karyawan lewat aplikasi.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar pengajuan kenaikan gaji">
                <nav class="list-tabs" aria-label="Status pengajuan">
                    <a href="{{ $urlStatus(null) }}" class="list-tab {{ blank($statusKini) ? 'is-current' : '' }}" @if (blank($statusKini)) aria-current="page" @endif>Semua <span class="list-tab-count">{{ $jumlahStatus['semua'] ?? 0 }}</span></a>
                    @foreach ($statusGaji as $nilai => [$label])
                        <a href="{{ $urlStatus($nilai) }}" class="list-tab {{ $statusKini === $nilai ? 'is-current' : '' }}" @if ($statusKini === $nilai) aria-current="page" @endif>{{ $label }} <span class="list-tab-count">{{ $jumlahStatus[(string) $nilai] ?? 0 }}</span></a>
                    @endforeach
                    <span class="list-tabs-note">Jumlah {{ $periodeJumlah }}</span>
                </nav>

                <form action="{{ route('admin.kenaikan_gaji.index') }}" method="GET" class="list-toolbar" autocomplete="off">
                    @if (filled($statusKini))
                        <input type="hidden" name="status" value="{{ $statusKini }}">
                    @endif
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_karyawan" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Bulan</span>
                            <select name="bulan" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" @selected(request('bulan') == $m)>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Tahun</span>
                            <select name="tahun" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @for ($t = (int) date('Y'); $t >= 2022; $t--)
                                    <option value="{{ $t }}" @selected(request('tahun') == $t)>{{ $t }}</option>
                                @endfor
                            </select>
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
                        <p class="mb-1 fw-medium">Belum ada pengajuan kenaikan gaji.</p>
                        <p class="mb-0 text-secondary small">Pengajuan dari aplikasi karyawan akan tampil di sini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Diajukan</th>
                                    <th class="text-end">Kenaikan</th>
                                    <th>Catatan</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pengajuan as $p)
                                    @php [$labelStatus, $nadaStatus] = $statusGaji[$p->status] ?? [$p->status, 'neutral']; @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name" title="{{ $p->nama_lengkap }}">{{ $p->nama_lengkap }}</span>
                                            <span class="person-sub">NIK {{ $p->nik }}</span>
                                        </td>
                                        <td data-label="Diajukan" class="cell-num">{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y') }}</td>
                                        <td data-label="Kenaikan" class="cell-num text-lg-end fw-medium">{{ $p->persentase }}%</td>
                                        <td data-label="Catatan">
                                            <div class="cell-clamp" title="{{ $p->catatan }}">{{ $p->catatan ?: '-' }}</div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                                            @if ($p->approved_at)
                                                <div class="cell-sub">{{ $p->approved_by }} · {{ \Carbon\Carbon::parse($p->approved_at)->format('d M Y H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="cell-actions">
                                            @if ($p->status == 'pending')
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <button type="button" class="btn btn-sm btn-reject" data-id="{{ $p->id }}" data-nama="{{ $p->nama_lengkap }}">Tolak</button>
                                                    <form action="{{ route('admin.kenaikan_gaji.approve', $p->id) }}" method="POST" class="m-0">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary"
                                                            data-confirm="Kenaikan {{ $p->persentase }}% untuk {{ $p->nama_lengkap }} akan disetujui."
                                                            data-confirm-title="Setujui pengajuan?" data-confirm-ok="Ya, setujui">Setujui</button>
                                                    </form>
                                                </div>
                                            @endif
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

    <form action="" method="POST" id="form-reject" hidden>
        @csrf
        <input type="hidden" name="catatan" id="catatan-reject">
    </form>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('.btn-reject').on('click', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Tolak pengajuan?',
                    text: $(this).data('nama'),
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Terlihat oleh karyawan di detail pengajuan…',
                    inputValidator: (v) => !v.trim() && 'Alasan penolakan wajib diisi.',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Tolak pengajuan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    $('#catatan-reject').val(r.value.trim());
                    $('#form-reject').attr('action', "{{ url('panel/kenaikan-gaji') }}/" + id + '/reject').trigger('submit');
                });
            });
        });
    </script>
@endpush
