@extends('layouts.admin.tabler')

@use('App\Models\Karyawan')
@use('Carbon\Carbon')

@php
    $tgl = fn ($nilai) => $nilai ? Carbon::parse($nilai)->locale('id')->translatedFormat('d F Y') : '-';
    $isi = fn ($nilai) => filled($nilai) ? $nilai : '-';
    $foto = !empty($karyawan->foto) ? asset_v('storage/uploads/karyawan/' . $karyawan->foto) : asset_v('assets/img/nophoto.png');

    $nadaStatus = match ($karyawan->status_aktif) {
        Karyawan::STATUS_AKTIF => 'success',
        Karyawan::STATUS_MENUNGGU_APPROVAL => 'warning',
        Karyawan::STATUS_DIBERHENTIKAN => 'danger',
        default => 'neutral',
    };
    $jenisKontrak = match ($karyawan->status_karyawan) {
        'PKWTT' => 'Tetap (PKWTT)',
        'PKWT' => 'Kontrak (PKWT)',
        default => $karyawan->status_karyawan,
    };
    $ptkp = match (true) {
        $karyawan->status_ptkp === 'TK' => 'Tidak kawin (TK)',
        filled($karyawan->status_ptkp) && str_starts_with($karyawan->status_ptkp, 'K/') => 'Kawin, ' . substr($karyawan->status_ptkp, 2) . ' tanggungan (' . $karyawan->status_ptkp . ')',
        default => $isi($karyawan->status_ptkp),
    };
    // wa.me butuh format internasional (08xx -> 628xx).
    $nomorWa = filled($karyawan->no_hp) ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $karyawan->no_hp)) : null;
    $bolehEdit = $karyawan->status_aktif !== Karyawan::STATUS_DIBERHENTIKAN;

    $riwayat = $karyawan->history_karyawan;
    if (empty($riwayat) && empty($karyawan->tanggal_keluar)
        && in_array($karyawan->status_aktif, [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN], true)
        && $karyawan->status_karyawan === 'PKWT'
        && $karyawan->updated_at && $karyawan->tanggal_habis_kontrak
        && Carbon::parse($karyawan->updated_at)->lt(Carbon::parse($karyawan->tanggal_habis_kontrak))) {
        $riwayat = 'Perpanjang kontrak sebelum resign';
    }
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><a href="{{ route('karyawan.index') }}" class="text-reset">Data Karyawan</a></div>
                    <h2 class="page-title">Profil Karyawan</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">

            {{-- ═══ Header profil ═══ --}}
            <section class="card profile-card" aria-labelledby="nama-karyawan">
                <div class="profile-head">
                    <span class="avatar profile-avatar" style="background-image: url('{{ $foto }}')" role="img"
                        aria-label="Foto {{ $karyawan->nama_lengkap }}"></span>

                    <div class="profile-main">
                        <h1 class="profile-name" id="nama-karyawan">{{ $isi($karyawan->nama_lengkap) }}</h1>
                        <p class="profile-role">
                            {{ $isi($karyawan->jabatan_nama) }}
                            <span aria-hidden="true">·</span>
                            {{ $karyawan->departemen->nama_dept ?? '-' }}
                        </p>
                        <div class="profile-badges">
                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $isi($karyawan->status_aktif) }}</span>
                            @if ($jenisKontrak)
                                <span class="pill-tag">{{ $jenisKontrak }}</span>
                            @endif
                        </div>
                        <dl class="profile-meta">
                            <div><dt>NIK</dt><dd class="cell-num">{{ $karyawan->nik }}</dd></div>
                            <div><dt>PT / Cabang</dt><dd>{{ $karyawan->cabang->nama_cabang ?? '-' }}</dd></div>
                            <div><dt>Bergabung</dt><dd>{{ $tgl($karyawan->tmt) }}</dd></div>
                        </dl>
                    </div>

                    <div class="profile-actions">
                        @if ($bolehEdit)
                            @can('karyawan-edit-admin')
                                <button type="button" class="btn btn-primary edit" data-nik="{{ $karyawan->nik }}"
                                    data-bs-toggle="modal" data-bs-target="#modal-editkaryawan">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                        stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                        <path d="M13.5 6.5l4 4" />
                                    </svg>
                                    Edit Profil
                                </button>
                            @endcan
                        @endif
                        @canany(['karyawan-edit-admin', 'karyawan-delete-admin'])
                            <div class="dropdown">
                                <button type="button" class="btn btn-icon" data-bs-toggle="dropdown" aria-expanded="false"
                                    aria-label="Aksi lainnya" title="Aksi lainnya">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                    </svg>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @can('karyawan-edit-admin')
                                        <a href="{{ route('konfigurasi.setjamkerja', $karyawan->nik) }}" class="dropdown-item">Atur jam kerja</a>
                                    @endcan
                                    @can('karyawan-delete-admin')
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('karyawan.destroy', $karyawan->nik) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                data-nama="{{ $karyawan->nama_lengkap }}">Hapus karyawan</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endcanany
                    </div>
                </div>

                {{-- Kontak: aksi hanya muncul bila datanya ada --}}
                <div class="profile-contact">
                    <div class="contact-item">
                        <span class="contact-label">Email</span>
                        @if (filled($karyawan->email))
                            <a href="mailto:{{ $karyawan->email }}" class="contact-value">{{ $karyawan->email }}</a>
                        @else
                            <span class="contact-value is-empty">-</span>
                        @endif
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">WhatsApp</span>
                        @if ($nomorWa)
                            <a href="https://wa.me/{{ $nomorWa }}" target="_blank" rel="noopener" class="contact-value">{{ $karyawan->no_hp }}</a>
                        @else
                            <span class="contact-value is-empty">-</span>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ═══ Detail bertab ═══ --}}
            <section class="card profile-detail">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs profile-tabs" role="tablist">
                        <li class="nav-item" role="presentation"><a href="#tab-pribadi" class="nav-link active" data-bs-toggle="tab" role="tab" aria-selected="true">Data Pribadi</a></li>
                        <li class="nav-item" role="presentation"><a href="#tab-kerja" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">Kepegawaian</a></li>
                        <li class="nav-item" role="presentation"><a href="#tab-legal" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">Legal &amp; Keluarga</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        {{-- Data pribadi --}}
                        <div class="tab-pane active show" id="tab-pribadi" role="tabpanel">
                            <h2 class="info-section-title">Informasi pribadi</h2>
                            <dl class="info-grid">
                                <div><dt>Nama lengkap</dt><dd>{{ $isi($karyawan->nama_lengkap) }}</dd></div>
                                <div><dt>Nama panggilan</dt><dd>{{ $isi($karyawan->nama_panggilan) }}</dd></div>
                                <div><dt>Jenis kelamin</dt><dd>{{ $karyawan->jenis_kelamin === 'L' ? 'Laki-laki' : ($karyawan->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</dd></div>
                                <div><dt>Tempat, tanggal lahir</dt><dd>{{ $isi($karyawan->tempat_lahir) }}, {{ $tgl($karyawan->tanggal_lahir) }}</dd></div>
                                <div><dt>Agama</dt><dd>{{ $isi($karyawan->agama) }}</dd></div>
                                <div><dt>Status pernikahan</dt><dd>{{ $isi($karyawan->status_pernikahan) }}</dd></div>
                                <div><dt>Status PTKP</dt><dd>{{ $ptkp }}</dd></div>
                                <div><dt>Email</dt><dd class="text-break">{{ $isi($karyawan->email) }}</dd></div>
                                <div><dt>No. WhatsApp</dt><dd class="cell-num">{{ $isi($karyawan->no_hp) }}</dd></div>
                                <div class="info-full"><dt>Alamat</dt><dd>{{ $isi($karyawan->alamat) }}</dd></div>
                            </dl>
                        </div>

                        {{-- Kepegawaian --}}
                        <div class="tab-pane" id="tab-kerja" role="tabpanel">
                            <h2 class="info-section-title">Data kepegawaian</h2>
                            <dl class="info-grid">
                                <div><dt>Jabatan</dt><dd>{{ $isi($karyawan->jabatan_nama) }}</dd></div>
                                <div><dt>Departemen</dt><dd>{{ $karyawan->departemen->nama_dept ?? '-' }}</dd></div>
                                <div><dt>PT / Cabang</dt><dd>{{ $karyawan->cabang->nama_cabang ?? '-' }}</dd></div>
                                <div><dt>Status karyawan</dt><dd>{{ $isi($jenisKontrak) }}</dd></div>
                                <div><dt>Status kepegawaian</dt><dd><span class="emp-status emp-status--{{ $nadaStatus }}">{{ $isi($karyawan->status_aktif) }}</span></dd></div>
                                <div><dt>TMT (tanggal bergabung)</dt><dd>{{ $tgl($karyawan->tmt) }}</dd></div>
                                <div><dt>Awal kontrak</dt><dd>{{ $tgl($karyawan->tanggal_awal_kontrak) }}</dd></div>
                                <div>
                                    <dt>Akhir kontrak</dt>
                                    <dd>
                                        {{ $tgl($karyawan->tanggal_habis_kontrak) }}
                                        @if ($karyawan->tanggal_habis_kontrak && filled($karyawan->sisa_kontrak))
                                            <span class="pill-tag {{ str_contains($karyawan->sisa_kontrak, 'Expired') ? 'pill-tag--danger' : '' }}">{{ $karyawan->sisa_kontrak }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div><dt>Tanggal keluar</dt><dd>{{ $tgl($karyawan->tanggal_keluar) }}</dd></div>
                            </dl>

                            <h2 class="info-section-title">Catatan</h2>
                            <dl class="info-grid">
                                <div class="info-full"><dt>Riwayat</dt><dd class="info-note">{!! filled($riwayat) ? nl2br(e($riwayat)) : '-' !!}</dd></div>
                                <div class="info-full"><dt>Pernyataan</dt><dd class="info-note">{!! filled($karyawan->statemen) ? nl2br(e($karyawan->statemen)) : '-' !!}</dd></div>
                            </dl>
                        </div>

                        {{-- Legal & keluarga --}}
                        <div class="tab-pane" id="tab-legal" role="tabpanel">
                            <h2 class="info-section-title">Legal &amp; pendidikan</h2>
                            <dl class="info-grid">
                                <div><dt>No. rekening</dt><dd class="cell-num">{{ $isi($karyawan->no_rekening) }}</dd></div>
                                <div><dt>Pendidikan terakhir</dt><dd>{{ $isi($karyawan->pendidikan_terakhir) }}</dd></div>
                                <div><dt>Nama ibu kandung</dt><dd>{{ $isi($karyawan->nama_ibu_kandung) }}</dd></div>
                            </dl>

                            <h2 class="info-section-title">Kontak darurat</h2>
                            <dl class="info-grid">
                                <div><dt>Nama</dt><dd>{{ $isi($karyawan->nama_darurat) }}</dd></div>
                                <div><dt>Hubungan</dt><dd>{{ $isi($karyawan->hubungan_darurat) }}</dd></div>
                                <div><dt>No. HP</dt><dd class="cell-num">{{ $isi($karyawan->no_darurat) }}</dd></div>
                            </dl>

                            <h2 class="info-section-title">Dokumen BPJS</h2>
                            <div class="doc-grid">
                                @foreach ([
                                    ['BPJS Kesehatan', $karyawan->no_bpjs_kesehatan, $karyawan->foto_bpjs_kesehatan],
                                    ['BPJS Ketenagakerjaan', $karyawan->no_bpjs_ketenagakerjaan, $karyawan->foto_bpjs_ketenagakerjaan],
                                ] as [$judul, $nomor, $berkas])
                                    <div class="doc-item">
                                        <dl class="info-grid info-grid--single">
                                            <div><dt>{{ $judul }}</dt><dd class="cell-num">{{ $isi($nomor) }}</dd></div>
                                        </dl>
                                        @if ($berkas)
                                            @php $urlBerkas = asset_v('storage/uploads/karyawan/bpjs/' . $berkas); @endphp
                                            <a href="{{ $urlBerkas }}" target="_blank" rel="noopener" class="doc-thumb">
                                                <img src="{{ $urlBerkas }}" alt="Kartu {{ $judul }}" loading="lazy">
                                            </a>
                                        @else
                                            <p class="doc-empty">Belum ada foto kartu.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- MODAL EDIT CONTAINER (form dimuat via AJAX dari karyawan.edit) --}}
    <div class="modal modal-blur fade" id="modal-editkaryawan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
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
    <script>
        $(function() {
            // Form edit dimuat dari rute karyawan.edit ke dalam modal.
            $('.edit').click(function(e) {
                e.preventDefault();
                $('#loadededitform').load('/karyawan/' + $(this).data('nik') + '/edit');
            });

            // Hapus selalu lewat konfirmasi.
            $('.delete-confirm').click(function(e) {
                var form = $(this).closest('form');
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Data?',
                    text: 'Data ' + $(this).data('nama') + ' akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, Hapus!'
                }).then(function(result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endpush
