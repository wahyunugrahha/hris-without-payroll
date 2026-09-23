@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Profil Karyawan</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @can('karyawan-delete-admin')
                            <form action="{{ route('karyawan.destroy', $karyawan->nik) }}" method="POST"
                                class="d-inline-block">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-outline-danger delete-confirm"
                                    data-nama="{{ $karyawan->nama_lengkap }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash"
                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 7l16 0" />
                                        <path d="M10 11l0 6" />
                                        <path d="M14 11l0 6" />
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        @endcan
                        @if ($karyawan->status_aktif !== \App\Models\Karyawan::STATUS_DIBERHENTIKAN)
                            @can('karyawan-edit-admin')
                                <a href="#" class="edit btn btn-outline-primary" data-nik="{{ $karyawan->nik }}"
                                    data-bs-toggle="modal" data-bs-target="#modal-editkaryawan">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil"
                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                        <path d="M13.5 6.5l4 4" />
                                    </svg>
                                    Edit Profil
                                </a>
                            @endcan
                        @endif
                        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l14 0" />
                                <path d="M5 12l6 6" />
                                <path d="M5 12l6 -6" />
                            </svg>
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (Session::get('success'))
        <div class="alert alert-success alert-important alert-dismissible" role="alert">
            {{ Session::get('success') }}
            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    @if (Session::get('warning'))
        <div class="alert alert-warning alert-important alert-dismissible" role="alert">
            {{ Session::get('warning') }}
            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-important alert-dismissible" role="alert">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                </div>
                <div>
                    <strong>Gagal Disimpan:</strong>
                    <ul class="mb-0 ps-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                {{-- KOLOM KIRI --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-4 text-center">
                            @php
                                $path = !empty($karyawan->foto)
                                    ? asset('storage/uploads/karyawan/' . $karyawan->foto)
                                    : asset('assets/img/nophoto.png');
                            @endphp
                            <span class="avatar avatar-xl mb-3 rounded"
                                style="background-image: url({{ $path }}); width: 120px; height: 120px; box-shadow: var(--shadow-lift)"></span>
                            <h3 class="m-0 mb-1 font-weight-bold">{{ $karyawan->nama_lengkap ?? '-' }}</h3>
                            <div class="text-muted mb-3">{{ $karyawan->jabatan_nama ?? '-' }}</div>
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <span
                                    class="badge {{ $karyawan->status_aktif == 'Aktif' ? 'bg-green-lt' : ($karyawan->status_aktif == 'Diberhentikan' ? 'bg-red-lt' : 'bg-secondary-lt') }}">{{ $karyawan->status_aktif ?? '-' }}</span>
                                <span
                                    class="badge {{ str_contains($karyawan->sisa_kontrak, 'Expired') ? 'bg-red-lt' : 'bg-blue-lt' }}">{{ $karyawan->sisa_kontrak ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="d-flex">
                            <a href="{{ !empty($karyawan->email) ? 'mailto:' . $karyawan->email : '#' }}"
                                class="card-btn">Email</a>
                            <a href="{{ !empty($karyawan->no_hp) ? 'https://wa.me/' . $karyawan->no_hp : '#' }}"
                                target="_blank" class="card-btn">WhatsApp</a>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-muted small">Departemen</div>
                                    <div class="font-weight-medium">{{ $karyawan->departemen->nama_dept ?? '-' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">PT / Cabang</div>
                                    <div class="font-weight-medium">{{ $karyawan->cabang->nama_cabang ?? '-' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">NIK</div>
                                    <div class="font-weight-medium font-monospace">{{ $karyawan->nik ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN --}}
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                <li class="nav-item"><a href="#tab-pribadi" class="nav-link active"
                                        data-bs-toggle="tab">Data Pribadi</a></li>
                                <li class="nav-item"><a href="#tab-kerja" class="nav-link" data-bs-toggle="tab">
                                        Kepegawaian</a></li>
                                <li class="nav-item"><a href="#tab-legal" class="nav-link" data-bs-toggle="tab">
                                        Legal & Keluarga</a></li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                {{-- TAB PRIBADI --}}
                                <div class="tab-pane active show" id="tab-pribadi">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Nama Panggilan</div>
                                            <div class="datagrid-content">{{ $karyawan->nama_panggilan ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Jenis Kelamin</div>
                                            <div class="datagrid-content">
                                                {{ $karyawan->jenis_kelamin == 'L' ? 'Laki-laki' : ($karyawan->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">TTL</div>
                                            <div class="datagrid-content">
                                                {{ $karyawan->tempat_lahir ?? '-' }},
                                                {{ $karyawan->tanggal_lahir ? \Carbon\Carbon::parse($karyawan->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Agama</div>
                                            <div class="datagrid-content">{{ $karyawan->agama ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Status Nikah</div>
                                            <div class="datagrid-content">{{ $karyawan->status_pernikahan ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Status PTKP</div>
                                            <div class="datagrid-content">
                                                @php
                                                    $ptkpLabel = $karyawan->status_ptkp ?? '-';
                                                    if ($karyawan->status_ptkp === 'TK') {
                                                        $ptkpLabel = 'Tak Kawin (TK)';
                                                    } elseif (
                                                        !empty($karyawan->status_ptkp) &&
                                                        str_starts_with($karyawan->status_ptkp, 'K/')
                                                    ) {
                                                        $tanggungan = str_replace('K/', '', $karyawan->status_ptkp);
                                                        $ptkpLabel = "Kawin, $tanggungan Tanggungan ({$karyawan->status_ptkp})";
                                                    }
                                                @endphp
                                                <span class="badge bg-azure-lt">{{ $ptkpLabel }}</span>
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Email</div>
                                            <div class="datagrid-content text-break">{{ $karyawan->email ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item w-100">
                                            <div class="datagrid-title">Alamat</div>
                                            <div class="datagrid-content">{{ $karyawan->alamat ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                                {{-- TAB PEKERJAAN --}}
                                <div class="tab-pane" id="tab-kerja">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">TMT (Join Date)</div>
                                            <div class="datagrid-content">
                                                {{ $karyawan->tmt ? \Carbon\Carbon::parse($karyawan->tmt)->translatedFormat('d F Y') : '-' }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Awal Kontrak</div>
                                            <div class="datagrid-content">
                                                {{ $karyawan->tanggal_awal_kontrak ? \Carbon\Carbon::parse($karyawan->tanggal_awal_kontrak)->translatedFormat('d F Y') : '-' }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Status Karyawan</div>
                                            <div class="datagrid-content">{{ $karyawan->status_karyawan ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Akhir Kontrak</div>
                                            <div class="datagrid-content text-danger font-weight-bold">
                                                {{ $karyawan->tanggal_habis_kontrak ? \Carbon\Carbon::parse($karyawan->tanggal_habis_kontrak)->translatedFormat('d F Y') : '-' }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Tanggal Keluar</div>
                                            <div class="datagrid-content">
                                                {{ $karyawan->tanggal_keluar ? \Carbon\Carbon::parse($karyawan->tanggal_keluar)->translatedFormat('d F Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-text">Catatan</div>
                                    <div class="mb-2"><label class="form-label text-secondary">Histori</label>
                                        @php
                                            $displayHistory = $karyawan->history_karyawan;
                                            if (
                                                empty($displayHistory) &&
                                                empty($karyawan->tanggal_keluar) &&
                                                in_array($karyawan->status_aktif, ['Nonaktif', 'Diberhentikan']) &&
                                                $karyawan->status_karyawan == 'PKWT'
                                            ) {
                                                if (
                                                    $karyawan->updated_at &&
                                                    $karyawan->tanggal_habis_kontrak &&
                                                    \Carbon\Carbon::parse($karyawan->updated_at)->lt(
                                                        \Carbon\Carbon::parse($karyawan->tanggal_habis_kontrak),
                                                    )
                                                ) {
                                                    $displayHistory = 'Perpanjang kontrak sebelum resign';
                                                }
                                            }
                                        @endphp
                                        <div class="card card-body p-2 bg-muted-lt">{!! !empty($displayHistory) ? nl2br(e($displayHistory)) : '-' !!}</div>
                                    </div>
                                    <div class="mb-2"><label class="form-label text-secondary">Statement</label>
                                        <div class="card card-body p-2 bg-muted-lt">{!! !empty($karyawan->statemen) ? nl2br(e($karyawan->statemen)) : '-' !!}</div>
                                    </div>
                                </div>
                                {{-- TAB LEGAL --}}
                                <div class="tab-pane" id="tab-legal">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">No Rekening</div>
                                            <div class="datagrid-content">{{ $karyawan->no_rekening ?? '-' }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Pendidikan</div>
                                            <div class="datagrid-content">{{ $karyawan->pendidikan_terakhir ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">Ibu Kandung</div>
                                            <div class="datagrid-content">{{ $karyawan->nama_ibu_kandung ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="hr-text text-red">Data Darurat</div>
                                    <div class="row mb-4">
                                        <div class="col-4">
                                            <div class="text-muted small">Nama Kontak Darurat</div>
                                            <strong>{{ $karyawan->nama_darurat ?? '-' }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Hubungan</div>
                                            <strong>{{ $karyawan->hubungan_darurat ?? '-' }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">No. HP Darurat</div><strong
                                                class="text-danger">{{ $karyawan->no_darurat ?? '-' }}</strong>
                                        </div>
                                    </div>

                                    <div class="hr-text text-blue">Dokumen BPJS</div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="datagrid-item mb-2">
                                                <div class="datagrid-title">BPJS Kesehatan</div>
                                                <div class="datagrid-content">{{ $karyawan->no_bpjs_kesehatan ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="text-muted small mb-2">Foto Kartu BPJS Kesehatan</div>
                                            @if ($karyawan->foto_bpjs_kesehatan)
                                                <a href="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_kesehatan) }}"
                                                    target="_blank">
                                                    <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_kesehatan) }}"
                                                        class="img-fluid rounded border shadow-sm"
                                                        style="max-height: 150px">
                                                </a>
                                            @else
                                                <div class="card card-body bg-muted-lt text-center py-4 border-dashed">
                                                    <span class="text-muted small">Tidak ada data</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-6">
                                            <div class="datagrid-item mb-2">
                                                <div class="datagrid-title">BPJS Ketenagakerjaan</div>
                                                <div class="datagrid-content">
                                                    {{ $karyawan->no_bpjs_ketenagakerjaan ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="text-muted small mb-2">Foto Kartu BPJS Ketenagakerjaan</div>
                                            @if ($karyawan->foto_bpjs_ketenagakerjaan)
                                                <a href="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_ketenagakerjaan) }}"
                                                    target="_blank">
                                                    <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_ketenagakerjaan) }}"
                                                        class="img-fluid rounded border shadow-sm"
                                                        style="max-height: 150px">
                                                </a>
                                            @else
                                                <div class="card card-body bg-muted-lt text-center py-4 border-dashed">
                                                    <span class="text-muted small">Belum ada foto</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT CONTAINER --}}
    <div class="modal modal-blur fade" id="modal-editkaryawan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="loadededitform">
                    <div class="text-center p-3">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2">Memuat data...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            // Trigger Edit dari Halaman Show
            $('.edit').click(function(e) {
                e.preventDefault();
                var nik = $(this).data('nik');
                $('#loadededitform').load('/karyawan/' + nik + '/edit');
            });

            // Konfirmasi Hapus SweetAlert
            $('.delete-confirm').click(function(e) {
                var form = $(this).closest("form");
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Data?',
                    text: "Data " + $(this).data("nama") + " akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                })
            });

            // Auto Dismiss Alert setelah 3 detik
            setTimeout(function() {
                var alert = document.getElementById('auto-dismiss-alert');
                if (alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 3000);
        });
    </script>
@endpush
