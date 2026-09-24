@extends('layouts.admin.tabler')

@php
    $jenisPelanggaran = ['late' => 'Keterlambatan', 'absent' => 'Mangkir / alpha', 'discipline' => 'Pelanggaran disiplin', 'other' => 'Lainnya'];
    $filterAktif = collect([request('jabatan_id'), request('kode_cabang'), request('level'), request('status'), request('q')])->filter(fn ($v) => filled($v))->count();
    $urlLevel = fn ($level) => route('suratperingatan.index', array_filter(['level' => $level, 'status' => 'active']));
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Surat Peringatan</h2>
                    <p class="page-subtitle">SP otomatis & manual beserta masa berlakunya.</p>
                </div>
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-sp" aria-label="Tambah SP">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                            stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg><span class="d-none d-sm-inline">Tambah SP</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar surat peringatan">
                <div class="summary-strip" role="group" aria-label="Ringkasan SP aktif">
                    <a href="{{ $urlLevel(null) }}" class="summary-item summary-item--danger {{ request('status') === 'active' && blank(request('level')) ? 'is-current' : '' }}">
                        <span class="summary-label">Karyawan dengan SP aktif</span>
                        <span class="summary-value">{{ $stats['total_aktif'] }}</span>
                    </a>
                    @foreach ([1, 2, 3] as $lv)
                        <a href="{{ $urlLevel($lv) }}" class="summary-item summary-item--warning {{ request('status') === 'active' && request('level') == $lv ? 'is-current' : '' }}">
                            <span class="summary-label">SP {{ $lv }} aktif</span>
                            <span class="summary-value">{{ $stats['sp' . $lv . '_aktif'] }}</span>
                        </a>
                    @endforeach
                </div>

                <form action="{{ route('suratperingatan.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="q" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
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
                                <span class="filter-label">Tingkat</span>
                                <select name="level" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ([1, 2, 3] as $lv)
                                        <option value="{{ $lv }}" @selected(request('level') == $lv)>SP {{ $lv }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Status</span>
                                <select name="status" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    <option value="active" @selected(request('status') == 'active')>Aktif</option>
                                    <option value="expired" @selected(request('status') == 'expired')>Berakhir</option>
                                </select>
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
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @if ($filterAktif > 0)
                                <a href="{{ route('suratperingatan.index') }}" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($items->total() > 0)
                        Menampilkan <strong>{{ $items->firstItem() }}–{{ $items->lastItem() }}</strong>
                        dari <strong>{{ $items->total() }}</strong> surat peringatan
                    @endif
                </div>

                @if ($items->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada surat peringatan yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                Ubah kata kunci atau filter — atau <a href="{{ route('suratperingatan.index') }}">reset filter</a>.
                            @else
                                SP otomatis muncul di sini saat karyawan melewati batas pelanggaran.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Tingkat &amp; pelanggaran</th>
                                    <th>Berlaku hingga</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $it)
                                    @php
                                        $expires = \Carbon\Carbon::parse($it->expires_at);
                                        $aktif = $expires->isFuture();
                                        $sisaHari = (int) round(now()->diffInDays($expires, false));
                                        $nama = $it->karyawan?->nama_lengkap ?? 'Tidak diketahui';
                                        $fotoUrl = !empty($it->karyawan?->foto) ? asset_v('storage/uploads/karyawan/' . $it->karyawan->foto) : asset_v('assets/img/nophoto.png');
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person">
                                                <span class="avatar" style="background-image: url('{{ $fotoUrl }}')"></span>
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $nama }}">{{ $nama }}</span>
                                                    <span class="person-sub">{{ $it->nik }} · {{ $it->karyawan?->jabatan_nama ?? '-' }}</span>
                                                </span>
                                            </span>
                                        </td>
                                        <td data-label="Tingkat">
                                            <div class="cell-main fw-medium">SP {{ $it->level }}</div>
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::PELANGGARAN_SP[$it->violation_type] ?? 'slate' }}">{{ $jenisPelanggaran[$it->violation_type] ?? 'Lainnya' }}</span>
                                        </td>
                                        <td data-label="Berlaku" class="cell-num">
                                            <div class="cell-main">{{ $expires->translatedFormat('d M Y') }}</div>
                                            <div class="cell-sub">{{ $aktif ? 'sisa ' . $sisaHari . ' hari' : 'sudah berakhir' }}</div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $aktif ? 'danger' : 'neutral' }}">{{ $aktif ? 'Aktif' : 'Berakhir' }}</span>
                                        </td>
                                        <td data-label="Catatan">
                                            <div class="cell-clamp" title="{{ $it->note }}">{{ $it->note ?: '-' }}</div>
                                        </td>
                                        <td class="cell-actions">
                                            <x-admin.row-menu :label="$nama">
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-detail"
                                                    data-nama="{{ $nama }}" data-nik="{{ $it->nik }}"
                                                    data-jabatan="{{ $it->karyawan?->jabatan_nama ?? '-' }}"
                                                    data-cabang="{{ $it->karyawan?->cabang?->nama_cabang ?? '-' }}"
                                                    data-dept="{{ $it->karyawan?->departemen?->nama_dept ?? '-' }}"
                                                    data-level="{{ $it->level }}" data-jenis="{{ $jenisPelanggaran[$it->violation_type] ?? 'Lainnya' }}"
                                                    data-hue="{{ \App\Support\WarnaJenis::PELANGGARAN_SP[$it->violation_type] ?? 'slate' }}"
                                                    data-tgl-terbit="{{ date('d-m-Y', strtotime($it->issued_at)) }}"
                                                    data-tgl-akhir="{{ $expires->format('d-m-Y') }}"
                                                    data-aktif="{{ $aktif ? 1 : 0 }}" data-keterangan="{{ $it->note }}">Lihat detail</button>
                                                <a href="{{ route('suratperingatan.cetak', $it->id) }}" target="_blank" rel="noopener" class="dropdown-item">Cetak surat</a>
                                                @if ($aktif)
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('suratperingatan.pemutihan', $it->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item"
                                                            data-confirm="SP {{ $it->level }} milik {{ $nama }} akan dinonaktifkan sekarang."
                                                            data-confirm-title="Putihkan SP?" data-confirm-ok="Ya, putihkan">Putihkan SP</button>
                                                    </form>
                                                @endif
                                            </x-admin.row-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($items->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
                        {{ $items->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-detail" tabindex="-1" aria-labelledby="judulDetailSp" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulDetailSp">Detail surat peringatan</h5>
                        <p class="modal-subtitle"><span id="mdl-nama">-</span> · NIK <span id="mdl-nik">-</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <dl class="info-grid">
                        <div><dt>Tingkat</dt><dd id="mdl-level" class="fw-medium">-</dd></div>
                        <div><dt>Status</dt><dd><span id="mdl-status" class="emp-status">-</span></dd></div>
                        <div><dt>Jenis pelanggaran</dt><dd><span id="mdl-jenis" class="tag">-</span></dd></div>
                        <div><dt>Masa berlaku</dt><dd class="cell-num"><span id="mdl-tgl-terbit">-</span> s/d <span id="mdl-tgl-akhir">-</span></dd></div>
                        <div><dt>Jabatan</dt><dd id="mdl-jabatan">-</dd></div>
                        <div><dt>Departemen · cabang</dt><dd><span id="mdl-dept">-</span> · <span id="mdl-cabang">-</span></dd></div>
                        <div class="info-full"><dt>Catatan</dt><dd id="mdl-keterangan" class="info-note">-</dd></div>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-tambah-sp" tabindex="-1" aria-labelledby="judulTambahSp" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulTambahSp">Tambah surat peringatan</h5>
                        <p class="modal-subtitle">SP aktif sebelumnya akan dinonaktifkan bila digantikan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('suratperingatan.store') }}" method="POST" id="form-tambah-sp" class="modal-form-body">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-grid-full">
                                <label class="form-label required" for="select-karyawan">Karyawan</label>
                                <select class="form-select" id="select-karyawan" name="nik" required style="width: 100%;">
                                    <option value="">Cari nama atau NIK…</option>
                                    @foreach ($karyawanList as $k)
                                        <option value="{{ $k->nik }}">{{ $k->nik }} — {{ $k->nama_lengkap }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label required" for="sp-level">Tingkat</label>
                                <select class="form-select" name="level" id="sp-level" required>
                                    <option value="">Pilih tingkat</option>
                                    @foreach ([1, 2, 3] as $lv)
                                        <option value="{{ $lv }}">SP {{ $lv }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label required" for="sp-jenis">Jenis pelanggaran</label>
                                <select class="form-select" name="violation_type" id="sp-jenis" required>
                                    <option value="">Pilih jenis</option>
                                    @foreach ($jenisPelanggaran as $kode => $label)
                                        <option value="{{ $kode }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label required" for="issued_at">Tanggal terbit</label>
                                <input type="date" class="form-control" id="issued_at" name="issued_at" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="form-label" for="durasi_bulan">Durasi</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="durasi_bulan" placeholder="6" min="1" max="36">
                                    <span class="input-group-text">bulan</span>
                                </div>
                                <div class="form-hint">Mengisi tanggal berakhir otomatis.</div>
                            </div>
                            <div>
                                <label class="form-label required" for="expires_at">Tanggal berakhir</label>
                                <input type="date" class="form-control" id="expires_at" name="expires_at" required>
                            </div>
                            <div class="form-grid-full">
                                <label class="form-label" for="sp-note">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                                <textarea class="form-control" name="note" id="sp-note" rows="3" placeholder="Keterangan tambahan tentang pelanggaran"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('#select-karyawan').select2({
                dropdownParent: $('#modal-tambah-sp'),
                placeholder: 'Cari nama atau NIK…',
                allowClear: true,
                width: '100%'
            });

            // Durasi (bulan) → tanggal berakhir.
            $('#durasi_bulan, #issued_at').on('input change', function() {
                const durasi = parseInt($('#durasi_bulan').val());
                const terbit = $('#issued_at').val();
                if (!durasi || !terbit) return;
                const [y, m, d] = terbit.split('-').map(Number);
                const akhir = new Date(y, m - 1 + durasi, d, 12);
                $('#expires_at').val(akhir.getFullYear() + '-' + String(akhir.getMonth() + 1).padStart(2, '0') + '-' + String(akhir.getDate()).padStart(2, '0'));
            });

            // Sebelum simpan: cek SP aktif karyawan.
            $('#form-tambah-sp').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const nik = $('#select-karyawan').val();
                if (!nik) {
                    Swal.fire('Periksa isian', 'Pilih karyawan terlebih dahulu.', 'warning');
                    return;
                }

                Swal.fire({ title: 'Memeriksa SP aktif…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.get(`/panel/surat-peringatan/check-active/${nik}`)
                    .done(function(response) {
                        Swal.close();
                        if (!response.active) return form.submit();

                        const levelBaru = parseInt($('#sp-level').val());
                        const levelAktif = parseInt(response.level);
                        if (levelBaru < levelAktif) {
                            Swal.fire('Tidak bisa ditambahkan', `Karyawan masih memiliki SP ${levelAktif} yang aktif.`, 'error');
                            return;
                        }
                        Swal.fire({
                            title: 'Ada SP aktif',
                            html: `Karyawan masih memiliki <strong>SP ${levelAktif}</strong> aktif hingga <strong>${response.expires_at}</strong>.<br>SP baru akan menonaktifkan SP tersebut.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Gantikan & simpan',
                            cancelButtonText: 'Batal',
                            reverseButtons: true
                        }).then((r) => r.isConfirmed && form.submit());
                    })
                    .fail(function() {
                        // Server tetap menonaktifkan SP lama bila perlu.
                        Swal.close();
                        form.submit();
                    });
            });

            document.getElementById('modal-detail').addEventListener('show.bs.modal', (event) => {
                const d = event.relatedTarget.dataset;
                const set = (id, v) => document.getElementById(id).textContent = v || '-';
                set('mdl-nama', d.nama);
                set('mdl-nik', d.nik);
                set('mdl-jabatan', d.jabatan);
                set('mdl-cabang', d.cabang);
                set('mdl-dept', d.dept);
                set('mdl-tgl-terbit', d.tglTerbit);
                set('mdl-tgl-akhir', d.tglAkhir);
                set('mdl-keterangan', d.keterangan);
                set('mdl-level', 'SP ' + d.level);
                set('mdl-jenis', d.jenis);
                document.getElementById('mdl-jenis').className = 'tag hue-' + (d.hue || 'slate');
                const status = document.getElementById('mdl-status');
                status.textContent = d.aktif === '1' ? 'Aktif' : 'Berakhir';
                status.className = 'emp-status emp-status--' + (d.aktif === '1' ? 'danger' : 'neutral');
            });
        });
    </script>
@endpush
