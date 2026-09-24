@extends('layouts.admin.tabler')

@php
    $statusDinas = ['menunggu' => ['Menunggu', 'warning'], 'acc' => ['Disetujui', 'success'], 'tolak' => ['Ditolak', 'danger']];
    $statusKini = request('status_acc');
    $urlStatus = fn ($s) => route('dinasluars.approval', array_merge(request()->except(['page', 'status_acc']), $s === null ? [] : ['status_acc' => $s]));
    $filterAktif = collect([request('dari'), request('sampai'), request('kode_cabang'), request('nama_lengkap')])->filter(fn ($v) => filled($v))->count();
    $rupiah = fn ($n) => is_null($n) ? '-' : 'Rp ' . number_format($n, 0, ',', '.');
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Persetujuan Dinas Luar</h2>
                    <p class="page-subtitle">
                        Tinjau perjalanan dinas dan biaya yang diajukan{{ !empty($isAdminCabang) ? ' · dibatasi untuk cabang Anda' : '' }}.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar dinas luar">
                <nav class="list-tabs" aria-label="Status pengajuan">
                    <a href="{{ $urlStatus(null) }}" class="list-tab {{ blank($statusKini) ? 'is-current' : '' }}" @if (blank($statusKini)) aria-current="page" @endif>Semua <span class="list-tab-count">{{ $jumlahStatus['semua'] ?? 0 }}</span></a>
                    @foreach ($statusDinas as $nilai => [$label, $nada])
                        <a href="{{ $urlStatus($nilai) }}" class="list-tab {{ $statusKini === $nilai ? 'is-current' : '' }}" @if ($statusKini === $nilai) aria-current="page" @endif>{{ $label }} <span class="list-tab-count list-tab-count--{{ $nada }}">{{ $jumlahStatus[(string) $nilai] ?? 0 }}</span></a>
                    @endforeach
                    <span class="list-tabs-note">Jumlah {{ $periodeJumlah }}</span>
                </nav>

                <form action="{{ route('dinasluars.approval') }}" method="GET" class="list-toolbar" autocomplete="off">
                    @if (filled($statusKini))
                        <input type="hidden" name="status_acc" value="{{ $statusKini }}">
                    @endif
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_lengkap" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Berangkat dari</span>
                            <input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm">
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Sampai</span>
                            <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm">
                        </label>
                        @if (empty($isAdminCabang))
                            <label class="filter-field">
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabangs as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        @if ($filterAktif > 0)
                            <a href="{{ $urlStatus($statusKini ?: null) }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($dinasluars->total() > 0)
                        Menampilkan <strong>{{ $dinasluars->firstItem() }}–{{ $dinasluars->lastItem() }}</strong>
                        dari <strong>{{ $dinasluars->total() }}</strong> pengajuan
                    @endif
                </div>

                @if ($dinasluars->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada pengajuan dinas luar yang cocok.</p>
                        <p class="mb-0 text-secondary small">Ubah kata kunci, rentang tanggal, atau status.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Periode</th>
                                    <th>Keperluan &amp; tujuan</th>
                                    <th class="text-end">Biaya diajukan</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dinasluars as $d)
                                    @php
                                        $mulai = \Carbon\Carbon::parse($d->tgl_mulai);
                                        $selesai = \Carbon\Carbon::parse($d->tgl_selesai);
                                        $jumlahHari = abs($selesai->diffInDays($mulai)) + 1;
                                        [$labelStatus, $nadaStatus] = $statusDinas[$d->status_acc] ?? $statusDinas['menunggu'];
                                        $menunggu = $d->status_acc === 'menunggu';
                                        $foto = !empty($d->karyawan->foto) ? asset_v('storage/uploads/karyawan/' . $d->karyawan->foto) : asset_v('assets/img/nophoto.png');
                                        $dataDetail = [
                                            'id' => $d->id,
                                            'nama' => $d->karyawan->nama_lengkap ?? '-',
                                            'nik' => $d->karyawan->nik ?? '-',
                                            'jabatan' => $d->karyawan->jabatan_nama ?? '-',
                                            'cabang' => $d->karyawan->cabang->nama_cabang ?? '-',
                                            'keperluan' => $d->alasan,
                                            'lokasi' => $d->lokasi_tujuan,
                                            'dana-raw' => $d->dana_diajukan,
                                            'dana' => $rupiah($d->dana_diajukan),
                                            'berangkat' => $mulai->format('d-m-Y'),
                                            'pulang' => $selesai->format('d-m-Y'),
                                            'dasar' => $d->dasar_perjalanan,
                                            'transportasi' => $d->transportasi,
                                            'keterangan' => $d->keterangan,
                                        ];
                                        $atributDetail = collect($dataDetail)->map(fn ($v, $k) => 'data-' . $k . '="' . e($v) . '"')->implode(' ');
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person">
                                                <span class="avatar" style="background-image: url('{{ $foto }}')"></span>
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $dataDetail['nama'] }}">{{ $dataDetail['nama'] }}</span>
                                                    <span class="person-sub">{{ $dataDetail['nik'] }} · {{ $dataDetail['cabang'] }}</span>
                                                </span>
                                            </span>
                                        </td>
                                        <td data-label="Periode" class="cell-num">
                                            <div class="cell-main">{{ $mulai->format('d M') }} – {{ $selesai->format('d M Y') }}</div>
                                            <div class="cell-sub">{{ $jumlahHari }} hari{{ $d->transportasi ? ' · ' . ucfirst($d->transportasi) : '' }}</div>
                                        </td>
                                        <td data-label="Keperluan">
                                            <div class="cell-clamp cell-main" title="{{ $d->alasan }}">{{ $d->alasan }}</div>
                                            <div class="cell-sub">{{ $d->lokasi_tujuan ?? '-' }}</div>
                                        </td>
                                        <td data-label="Biaya" class="cell-num text-lg-end">{{ $rupiah($d->dana_diajukan) }}</td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                @if ($menunggu)
                                                    <button type="button" class="btn btn-sm btn-approve-dl" data-mode="approve" {!! $atributDetail !!}>Proses</button>
                                                @endif
                                                <x-admin.row-menu :label="$dataDetail['nama']">
                                                    <button type="button" class="dropdown-item btn-approve-dl" data-mode="{{ $menunggu ? 'approve' : 'read' }}" {!! $atributDetail !!}>
                                                        {{ $menunggu ? 'Proses pengajuan' : 'Lihat detail' }}
                                                    </button>
                                                    <a href="{{ route('dinasluars.cetak', $d->id) }}" class="dropdown-item" target="_blank" rel="noopener">Cetak formulir</a>
                                                    @unless ($menunggu)
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger btn-cancel-dl" data-id="{{ $d->id }}">Batalkan keputusan</button>
                                                    @endunless
                                                </x-admin.row-menu>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($dinasluars->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $dinasluars->currentPage() }} dari {{ $dinasluars->lastPage() }}</span>
                        {{ $dinasluars->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-dinasluar" tabindex="-1" aria-labelledby="judulDinasLuar" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulDinasLuar">Detail dinas luar</h5>
                        <p class="modal-subtitle"><span id="mdNama">-</span> · NIK <span id="mdNik">-</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('dinasluars.approveorreject') }}" method="POST" class="modal-form-body">
                    @csrf
                    <input type="hidden" id="id_dinasluar_form" name="id">
                    <div class="modal-body">
                        <section class="form-section">
                            <h6 class="form-section-title">Perjalanan</h6>
                            <dl class="info-grid">
                                <div><dt>Jabatan</dt><dd id="mdJabatan">-</dd></div>
                                <div><dt>Cabang</dt><dd id="mdCabang">-</dd></div>
                                <div class="info-full"><dt>Keperluan</dt><dd id="mdKeperluan" class="info-note">-</dd></div>
                                <div><dt>Lokasi tujuan</dt><dd id="mdLokasi">-</dd></div>
                                <div><dt>Dasar perjalanan</dt><dd id="mdDasar">-</dd></div>
                                <div><dt>Berangkat</dt><dd id="mdBerangkat" class="cell-num">-</dd></div>
                                <div><dt>Pulang</dt><dd id="mdPulang" class="cell-num">-</dd></div>
                                <div><dt>Transportasi</dt><dd id="mdTransport">-</dd></div>
                                <div><dt>Dana diajukan</dt><dd id="mdDana" class="cell-num fw-medium">-</dd></div>
                                <div class="info-full d-none" id="mdKeteranganWrap"><dt>Keterangan tambahan</dt><dd id="mdKeterangan" class="info-note">-</dd></div>
                            </dl>
                        </section>

                        <section class="form-section" id="form-approval-content">
                            <h6 class="form-section-title">Keputusan</h6>
                            <div class="form-grid">
                                <div class="form-grid-full decision-options decision-options--row">
                                    <label class="decision-option">
                                        <input type="radio" name="status_acc" value="acc" checked>
                                        <span class="emp-status emp-status--success">Setujui</span>
                                    </label>
                                    <label class="decision-option">
                                        <input type="radio" name="status_acc" value="tolak">
                                        <span class="emp-status emp-status--danger">Tolak</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="form-label" for="dana_disetujui">Biaya disetujui</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="dana_disetujui" id="dana_disetujui" class="form-control" inputmode="numeric" placeholder="0">
                                    </div>
                                    <div class="form-hint">Boleh berbeda dari yang diajukan.</div>
                                </div>
                                <div>
                                    <label class="form-label" for="catatan_approval">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                                    <textarea name="catatan_approval" id="catatan_approval" class="form-control" rows="2" placeholder="Catatan untuk karyawan…"></textarea>
                                </div>
                            </div>
                        </section>

                        <p id="read-only-content" class="text-secondary small mb-0 d-none">
                            Pengajuan ini sudah diputuskan. Gunakan “Batalkan keputusan” di menu baris untuk mengubahnya.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanKeputusan">Simpan keputusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="frmBatalkanDL" method="POST" action="" hidden>
        @csrf
    </form>
@endsection

@push('myscript')
    <script>
        $(function() {
            $(document).on('click', '.btn-approve-dl', function() {
                const d = $(this).data();
                const approve = d.mode === 'approve';

                $('#id_dinasluar_form').val(d.id);
                $('#mdNama').text(d.nama);
                $('#mdNik').text(d.nik);
                $('#mdJabatan').text(d.jabatan);
                $('#mdCabang').text(d.cabang);
                $('#mdKeperluan').text(d.keperluan || '-');
                $('#mdLokasi').text(d.lokasi || '-');
                $('#mdBerangkat').text(d.berangkat);
                $('#mdPulang').text(d.pulang);
                $('#mdDasar').text(d.dasar || '-');
                $('#mdTransport').text(d.transportasi || '-');
                $('#mdDana').text(d.dana);
                $('#dana_disetujui').val(d.danaRaw || 0);
                $('#mdKeterangan').text(d.keterangan || '');
                $('#mdKeteranganWrap').toggleClass('d-none', !d.keterangan);

                $('#form-approval-content').toggleClass('d-none', !approve);
                $('#btnSimpanKeputusan').toggleClass('d-none', !approve);
                $('#read-only-content').toggleClass('d-none', approve);
                $('#judulDinasLuar').text(approve ? 'Proses dinas luar' : 'Detail dinas luar');

                $('#modal-dinasluar').modal('show');
            });

            $(document).on('click', '.btn-cancel-dl', function() {
                const url = "{{ url('/dinas-luar') }}/" + $(this).data('id') + '/cancel';
                Swal.fire({
                    title: 'Batalkan keputusan?',
                    text: 'Status dinas luar dikembalikan ke Menunggu.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, batalkan',
                    cancelButtonText: 'Tutup',
                    reverseButtons: true
                }).then((r) => {
                    if (r.isConfirmed) $('#frmBatalkanDL').attr('action', url).trigger('submit');
                });
            });
        });
    </script>
@endpush
