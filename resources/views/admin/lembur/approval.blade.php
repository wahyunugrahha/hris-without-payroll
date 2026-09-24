@extends('layouts.admin.tabler')

@php
    $statusLembur = [0 => ['Menunggu', 'warning'], 1 => ['Disetujui', 'success'], 2 => ['Ditolak', 'danger']];
    $statusKini = request('status');
    $urlStatus = fn ($s) => route('admin.lembur.approval', array_merge(request()->except(['page', 'status']), $s === null ? [] : ['status' => $s]));
    $filterAktif = collect([request('tanggal'), request('jabatan_id'), request('kode_dept'), request('kode_cabang'), request('search')])->filter(fn ($v) => filled($v))->count();
    $jam = fn ($t) => $t ? substr($t, 0, 5) : '-';
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Data Lembur</h2>
                    <p class="page-subtitle">Tinjau jam lembur, koreksi bila perlu, lalu setujui atau tolak.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar lembur">
                <nav class="list-tabs" aria-label="Status lembur">
                    <a href="{{ $urlStatus(null) }}" class="list-tab {{ blank($statusKini) ? 'is-current' : '' }}" @if (blank($statusKini)) aria-current="page" @endif>Semua <span class="list-tab-count">{{ $jumlahStatus['semua'] ?? 0 }}</span></a>
                    @foreach ($statusLembur as $nilai => [$label, $nada])
                        @php $aktif = $statusKini === (string) $nilai; @endphp
                        <a href="{{ $urlStatus($nilai) }}" class="list-tab {{ $aktif ? 'is-current' : '' }}" @if ($aktif) aria-current="page" @endif>{{ $label }} <span class="list-tab-count list-tab-count--{{ $nada }}">{{ $jumlahStatus[(string) $nilai] ?? 0 }}</span></a>
                    @endforeach
                    <span class="list-tabs-note">Jumlah {{ $periodeJumlah }}</span>
                </nav>

                <form action="{{ route('admin.lembur.approval') }}" method="GET" class="list-toolbar" autocomplete="off">
                    @if (filled($statusKini))
                        <input type="hidden" name="status" value="{{ $statusKini }}">
                    @endif
                    <div class="list-toolbar-row">
                        <x-admin.search name="search" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                        <button type="button" class="btn d-lg-none" data-bs-toggle="collapse" data-bs-target="#filterPanel"
                            aria-expanded="false" aria-controls="filterPanel">
                            Filter
                            @if ($filterAktif > 0)
                                <span class="filter-count">{{ $filterAktif }}</span>
                            @endif
                        </button>
                    </div>
                    <div class="collapse filter-panel" id="filterPanel">
                        <div class="filter-bar">
                            <label class="filter-field">
                                <span class="filter-label">Tanggal</span>
                                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm" data-auto-submit>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Jabatan</span>
                                <select name="jabatan_id" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($jabatans as $j)
                                        <option value="{{ $j->id }}" @selected(request('jabatan_id') == $j->id)>{{ $j->nama_jabatan }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Departemen</span>
                                <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}" @selected(request('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @if ($filterAktif > 0)
                                <a href="{{ $urlStatus(filled($statusKini) ? $statusKini : null) }}" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($lemburs->total() > 0)
                        Menampilkan <strong>{{ $lemburs->firstItem() }}–{{ $lemburs->lastItem() }}</strong>
                        dari <strong>{{ $lemburs->total() }}</strong> lembur
                    @endif
                </div>

                @if ($lemburs->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada data lembur yang cocok.</p>
                        <p class="mb-0 text-secondary small">Ubah tanggal, kata kunci, atau filter.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Tanggal</th>
                                    <th>Pekerjaan</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lemburs as $lembur)
                                    @php
                                        [$labelStatus, $nadaStatus] = $statusLembur[(int) $lembur->status_approved] ?? $statusLembur[0];
                                        $tanggal = \Carbon\Carbon::parse($lembur->tanggal_lembur);
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name" title="{{ $lembur->karyawan->nama_lengkap ?? '-' }}">{{ $lembur->karyawan->nama_lengkap ?? '-' }}</span>
                                            <span class="person-sub">{{ $lembur->nik }} · {{ $lembur->karyawan->jabatan_nama ?? '-' }}</span>
                                        </td>
                                        <td data-label="Tanggal" class="cell-num">
                                            <div class="cell-main">{{ $tanggal->translatedFormat('d M Y') }}</div>
                                            <div class="cell-sub">{{ $lembur->karyawan->cabang->nama_cabang ?? '-' }}</div>
                                        </td>
                                        <td data-label="Pekerjaan">
                                            <div class="cell-clamp cell-main" title="{{ $lembur->pekerjaan }}">{{ $lembur->pekerjaan }}</div>
                                            <div class="cell-sub">{{ \Illuminate\Support\Str::limit($lembur->tempat, 40) }}</div>
                                        </td>
                                        <td data-label="Jam" class="cell-num">
                                            <div class="cell-main">{{ $jam($lembur->jam_mulai) }}–{{ $jam($lembur->jam_selesai) }}</div>
                                            <div class="cell-sub">
                                                {{ $lembur->total_jam }} jam
                                                @if ($lembur->update_count > 0)
                                                    · <span title="Awal: {{ $lembur->jam_selesai_awal }}&#10;Final: {{ $lembur->jam_selesai }}&#10;Diubah: {{ \Carbon\Carbon::parse($lembur->last_update_at)->format('d-m-Y H:i') }}">diubah {{ $lembur->update_count }}×</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            <button type="button" class="btn btn-sm btn-detail-lembur" data-bs-toggle="modal"
                                                data-bs-target="#modal-detail-lembur" data-id="{{ $lembur->id }}"
                                                data-status="{{ $lembur->status_approved }}"
                                                data-nama="{{ $lembur->karyawan->nama_lengkap ?? '-' }}" data-nik="{{ $lembur->nik }}"
                                                data-jabatan="{{ $lembur->karyawan->jabatan_nama ?? '-' }}"
                                                data-cabang="{{ $lembur->karyawan->cabang->nama_cabang ?? '-' }}"
                                                data-tanggal="{{ $tanggal->format('d-m-Y') }}" data-pekerjaan="{{ $lembur->pekerjaan }}"
                                                data-tempat="{{ $lembur->tempat }}" data-jammulai="{{ $lembur->jam_mulai }}"
                                                data-jamselesai="{{ $lembur->jam_selesai }}" data-totaljam="{{ $lembur->total_jam }}"
                                                data-kode="{{ $lembur->kode_lembur }}" data-keterangan="{{ $lembur->keterangan ?? '-' }}"
                                                data-foto-masuk="{{ !empty($lembur->foto_masuk) ? asset('storage/uploads/absensi/' . $lembur->foto_masuk) : '' }}"
                                                data-foto-keluar="{{ !empty($lembur->foto_keluar) ? asset('storage/uploads/absensi/' . $lembur->foto_keluar) : '' }}">
                                                {{ (int) $lembur->status_approved === 0 ? 'Proses' : 'Detail' }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($lemburs->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $lemburs->currentPage() }} dari {{ $lemburs->lastPage() }}</span>
                        {{ $lemburs->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-detail-lembur" tabindex="-1" aria-labelledby="judulDetailLembur" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulDetailLembur">Detail lembur</h5>
                        <p class="modal-subtitle"><span id="mdNama">-</span> · NIK <span id="mdNik">-</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <section class="form-section">
                        <h6 class="form-section-title">Ringkasan</h6>
                        <dl class="info-grid">
                            <div><dt>Status</dt><dd><span id="mdStatus" class="emp-status">-</span></dd></div>
                            <div><dt>Kode lembur</dt><dd id="mdKode" class="cell-num">-</dd></div>
                            <div><dt>Jabatan</dt><dd id="mdJabatan">-</dd></div>
                            <div><dt>Cabang</dt><dd id="mdCabang">-</dd></div>
                            <div><dt>Tanggal</dt><dd id="mdTanggal" class="cell-num">-</dd></div>
                            <div><dt>Jam · total</dt><dd class="cell-num"><span id="mdJamMulai">-</span>–<span id="mdJamSelesai">-</span> · <span id="mdTotalJam">-</span> jam</dd></div>
                            <div class="info-full"><dt>Pekerjaan</dt><dd id="mdPekerjaan" class="info-note">-</dd></div>
                            <div><dt>Tempat</dt><dd id="mdTempat">-</dd></div>
                            <div><dt>Keterangan</dt><dd id="mdKeterangan">-</dd></div>
                        </dl>
                    </section>

                    <section class="form-section">
                        <h6 class="form-section-title">Koreksi jam</h6>
                        <form id="formUpdateJam" action="{{ route('admin.lembur.update-jam') }}" method="POST" class="form-grid align-items-end">
                            @csrf
                            <input type="hidden" name="id" id="updateJamId">
                            <div>
                                <label for="editJamMulai" class="form-label">Jam mulai</label>
                                <input type="time" class="form-control" id="editJamMulai" name="jam_mulai" required>
                            </div>
                            <div>
                                <label for="editJamSelesai" class="form-label">Jam selesai</label>
                                <input type="time" class="form-control" id="editJamSelesai" name="jam_selesai" required>
                            </div>
                            <div class="form-grid-full d-flex align-items-center gap-2">
                                <button type="submit" class="btn" id="btnUpdateJam">Simpan jam</button>
                                <span id="editJamHint" class="form-hint m-0 d-none"></span>
                            </div>
                        </form>
                    </section>

                    <section class="form-section">
                        <h6 class="form-section-title">Foto</h6>
                        <div class="detail-media detail-media--2">
                            <figure>
                                <div id="wrapFotoMasuk"></div>
                                <figcaption>Foto masuk</figcaption>
                            </figure>
                            <figure>
                                <div id="wrapFotoKeluar"></div>
                                <figcaption>Foto keluar</figcaption>
                            </figure>
                        </div>
                    </section>
                </div>
                <div class="modal-footer" id="mdActionWrap">
                    <form id="formCancel" action="{{ route('admin.lembur.cancel') }}" method="POST" class="d-none me-auto">
                        @csrf
                        <input type="hidden" name="id" id="cancelId">
                        <button type="submit" class="btn btn-outline-danger" data-confirm="Status lembur dikembalikan ke Menunggu."
                            data-confirm-title="Batalkan persetujuan?" data-confirm-ok="Ya, batalkan">Batalkan persetujuan</button>
                    </form>
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                    <form id="formReject" action="{{ route('admin.lembur.reject') }}" method="POST" class="d-none">
                        @csrf
                        <input type="hidden" name="id" id="rejectId">
                        <input type="hidden" name="catatan" id="rejectCatatanHidden">
                        <button type="button" class="btn btn-outline-danger" id="btnTolak">Tolak</button>
                    </form>
                    <form id="formApprove" action="{{ route('admin.lembur.approve') }}" method="POST" class="d-none">
                        @csrf
                        <input type="hidden" name="id" id="approveId">
                        <button type="submit" class="btn btn-primary">Setujui</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nadaStatus = { '0': ['Menunggu', 'warning'], '1': ['Disetujui', 'success'], '2': ['Ditolak', 'danger'] };
            const el = (id) => document.getElementById(id);
            const setText = (id, value) => el(id).textContent = value || '-';
            const jam = (v) => v && v !== '-' ? String(v).substring(0, 5) : '';

            function foto(wrap, url, alt) {
                wrap.replaceChildren();
                if (url) {
                    const a = Object.assign(document.createElement('a'), { href: url, target: '_blank', rel: 'noopener' });
                    a.append(Object.assign(document.createElement('img'), { src: url, alt: alt }));
                    wrap.append(a);
                } else {
                    wrap.append(Object.assign(document.createElement('div'), { className: 'detail-media-empty', textContent: 'Tidak ada foto' }));
                }
            }

            el('modal-detail-lembur').addEventListener('show.bs.modal', function(event) {
                const b = event.relatedTarget;
                if (!b) return;
                const d = (k) => b.getAttribute('data-' + k);

                ['nama', 'nik', 'jabatan', 'cabang', 'tanggal', 'kode', 'pekerjaan', 'tempat', 'keterangan'].forEach((k) =>
                    setText('md' + k.charAt(0).toUpperCase() + k.slice(1), d(k)));
                setText('mdJamMulai', jam(d('jammulai')));
                setText('mdJamSelesai', jam(d('jamselesai')));
                setText('mdTotalJam', d('totaljam'));

                const id = d('id');
                ['approveId', 'rejectId', 'cancelId', 'updateJamId'].forEach((k) => el(k).value = id);
                el('editJamMulai').value = jam(d('jammulai'));
                el('editJamSelesai').value = jam(d('jamselesai'));

                const status = d('status');
                const [label, nada] = nadaStatus[status] || nadaStatus['0'];
                el('mdStatus').textContent = label;
                el('mdStatus').className = 'emp-status emp-status--' + nada;

                const pending = status === '0';
                el('formApprove').classList.toggle('d-none', !pending);
                el('formReject').classList.toggle('d-none', !pending);
                el('formCancel').classList.toggle('d-none', status !== '1');

                // Koreksi jam hanya saat menunggu.
                ['editJamMulai', 'editJamSelesai', 'btnUpdateJam'].forEach((k) => el(k).disabled = !pending);
                el('editJamHint').classList.toggle('d-none', pending);
                el('editJamHint').textContent = pending ? '' : 'Koreksi jam hanya tersedia saat status Menunggu.';

                foto(el('wrapFotoMasuk'), d('foto-masuk'), 'Foto masuk');
                foto(el('wrapFotoKeluar'), d('foto-keluar'), 'Foto keluar');
            });

            // Tolak: alasan wajib diisi.
            el('btnTolak').addEventListener('click', function() {
                // Tutup modal dulu: focus trap Bootstrap menghalangi input di SweetAlert.
                bootstrap.Modal.getInstance(el('modal-detail-lembur'))?.hide();
                Swal.fire({
                    title: 'Tolak lembur?',
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Tulis alasan penolakan…',
                    inputValidator: (v) => !v.trim() && 'Alasan penolakan wajib diisi.',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Tolak lembur',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    el('rejectCatatanHidden').value = r.value.trim();
                    el('formReject').submit();
                });
            });
        });
    </script>
@endpush
