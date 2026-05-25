@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan</div>
                    <h2 class="page-title">Data Pengajuan Kenaikan Gaji</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0">
                {{-- Filter Section --}}
                <div class="card-header bg-transparent border-bottom-1 py-3">
                    <form action="{{ route('admin.kenaikan_gaji.index') }}" method="GET" autocomplete="off"
                        class="w-100">
                        <div class="row g-2 mb-2">
                            {{-- Filter Bulan --}}
                            <div class="col-12 col-md-4 col-xl-4">
                                <select name="bulan" class="form-select">
                                    <option value="">Semua Bulan</option>
                                    @php
                                        $namaBulan = [
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ];
                                    @endphp
                                    @foreach ($namaBulan as $m => $nama)
                                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                            {{ $nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filter Tahun --}}
                            <div class="col-12 col-md-4 col-xl-4">
                                <select name="tahun" class="form-select">
                                    <option value="">Semua Tahun</option>
                                    @php
                                        $tahunSekarang = date('Y');
                                        $tahunMulai = 2022; // Sesuaikan dengan awal data
                                    @endphp
                                    @for ($t = $tahunSekarang; $t >= $tahunMulai; $t--)
                                        <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            {{-- Filter Status --}}
                            <div class="col-12 col-md-4 col-xl-4">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>
                                        Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2">
                            {{-- Search & Tombol Cari --}}
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="nama_karyawan"
                                        placeholder="Cari Nama / NIK Karyawan" value="{{ request('nama_karyawan') }}">
                                    <button class="btn btn-success" type="submit">
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

                {{-- Alert --}}
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

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Karyawan</th>
                                <th>Tgl Pengajuan</th>
                                <th>Persentase</th>
                                <th>Catatan Karyawan</th>
                                <th>Persetujuan</th>
                                <th class="text-center">Status</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengajuan as $p)
                                <tr>
                                    <td>{{ $loop->iteration + $pengajuan->firstItem() - 1 }}</td>
                                    <td>
                                        <div class="d-flex py-1 align-items-center">
                                            <div class="flex-fill">
                                                <div class="font-weight-medium">{{ $p->nama_lengkap }}</div>
                                                <div class="text-muted small">{{ $p->nik }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark">{{ date('d M Y', strtotime($p->tanggal_pengajuan)) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-blue-lt">{{ $p->persentase }}%</span>
                                    </td>
                                    <td class="text-muted">
                                        <div>{{ $p->catatan ?? '-' }}</div>
                                    </td>
                                    <td>
                                        @if ($p->approved_at)
                                            <div class="text-dark small">By: {{ $p->approved_by }}</div>
                                            <div class="text-muted small">
                                                {{ date('d M Y H:i', strtotime($p->approved_at)) }}</div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($p->status == 'pending')
                                            <span class="badge bg-warning-lt">Pending</span>
                                        @elseif($p->status == 'approved')
                                            <span class="badge bg-success-lt">Approved</span>
                                        @elseif($p->status == 'rejected')
                                            <span class="badge bg-danger-lt">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            @if ($p->status == 'pending')
                                                {{-- Tombol Setujui --}}
                                                <form action="{{ route('admin.kenaikan_gaji.approve', $p->id) }}"
                                                    method="POST" style="margin: 0; padding: 0;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-icon btn-outline-success"
                                                        title="Setujui Pengajuan">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M5 12l5 5l10 -10" />
                                                        </svg>
                                                    </button>
                                                </form>

                                                {{-- Tombol Tolak --}}
                                                <a href="#" class="btn btn-icon btn-outline-danger btn-reject"
                                                    data-id="{{ $p->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#modal-reject" title="Tolak Pengajuan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M18 6l-12 12" />
                                                        <path d="M6 6l12 12" />
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="text-muted small">Selesai</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Tidak ada data pengajuan
                                        ditemukan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="card-footer d-flex align-items-center">
                    {{ $pengajuan->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reject -->
    <div class="modal modal-blur fade" id="modal-reject" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan Kenaikan Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="form-reject">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="catatan" rows="4" placeholder="Jelaskan alasan pengajuan ditolak..."
                                required></textarea>
                            <small class="text-muted">Komentar ini akan tersimpan pada detail pengajuan karyawan.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('.btn-reject').click(function() {
                var id = $(this).attr('data-id');
                // Pastikan base url route-nya sudah sesuai dengan routing di web.php Anda. 
                // Jika route name tersedia, sangat disarankan mengubah string dinamis ini ke route helper bila memungkinkan.
                var actionUrl = "{{ url('panel/kenaikan-gaji') }}/" + id + "/reject";
                $('#form-reject').attr('action', actionUrl);
            });
        });
    </script>
@endpush
