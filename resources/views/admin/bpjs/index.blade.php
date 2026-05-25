@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan</div>
                    <h2 class="page-title">Pengajuan BPJS Karyawan</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom-1 py-3">
                    <form action="{{ route('admin.bpjs.index') }}" method="GET" autocomplete="off" class="w-100">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-6 col-xl-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                            <path d="M16 3l0 4" />
                                            <path d="M8 3l0 4" />
                                            <path d="M4 11l16 0" />
                                        </svg>
                                    </span>
                                    <input type="date" value="{{ request('dari') }}" name="dari" class="form-control"
                                        placeholder="Dari Tanggal">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                            <path d="M16 3l0 4" />
                                            <path d="M8 3l0 4" />
                                            <path d="M4 11l16 0" />
                                        </svg>
                                    </span>
                                    <input type="date" value="{{ request('sampai') }}" name="sampai"
                                        class="form-control" placeholder="Sampai Tanggal">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <input type="text" value="{{ request('nik') }}" name="nik" class="form-control"
                                    placeholder="NIK">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>
                                        Processed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="text" value="{{ request('nama_lengkap') }}" name="nama_lengkap"
                                        class="form-control" placeholder="Cari Nama Karyawan / NIK">
                                    <button type="submit" class="btn btn-success text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="10" cy="10" r="7" />
                                            <line x1="21" y1="21" x2="15" y2="15" />
                                        </svg>
                                        Cari Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if (session('success') || session('error'))
                    <div class="p-3 pb-0">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center mb-2" role="alert">
                                <div>{{ session('success') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-2" role="alert">
                                <div>{{ session('error') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Tanggal Ajukan</th>
                                <th>Status</th>
                                <th>Diproses Oleh</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengajuan as $p)
                                <tr>
                                    <td>{{ $loop->iteration + $pengajuan->firstItem() - 1 }}</td>
                                    <td>{{ $p->nik }}</td>
                                    <td>{{ $p->nama_lengkap }}</td>
                                    <td>{{ optional($p->requested_at ?? $p->created_at)->format('d-m-Y H:i') }}</td>
                                    <td>
                                        @if ($p->status === 'pending')
                                            <span class="badge bg-warning-lt">Pending</span>
                                        @else
                                            <span class="badge bg-success-lt">Processed</span>
                                        @endif
                                    </td>
                                    <td>{{ $p->processed_by ?? '-' }}</td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('admin.bpjs.show', $p->id) }}"
                                                class="btn btn-icon btn-outline-info" title="Lihat Detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="2" />
                                                    <path
                                                        d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">Belum ada pengajuan BPJS.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center">
                    {{ $pengajuan->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
