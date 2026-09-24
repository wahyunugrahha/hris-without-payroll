@extends('layouts.admin.tabler')

@php
    $jam = fn ($t) => $t ? substr($t, 0, 5) : '-';
    $filterAktif = collect([request('nama_jam_kerja'), request('lintashari')])->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Jam Kerja</h2>
                    <p class="page-subtitle">Master shift: jendela absen masuk dan jam pulang.</p>
                </div>
                @can('jam-kerja-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="btnTambahJK" data-bs-toggle="modal"
                            data-bs-target="#modal-inputjk" aria-label="Tambah jam kerja">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Jam Kerja</span>
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar jam kerja">
                <form action="{{ route('konfigurasi.jamkerja') }}" method="GET" class="list-toolbar" autocomplete="off">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_jam_kerja" placeholder="Cari nama jam kerja…" label="Cari jam kerja" />
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Tipe</span>
                            <select name="lintashari" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                <option value="0" @selected(request('lintashari') === '0')>Normal (satu hari)</option>
                                <option value="1" @selected(request('lintashari') === '1')>Lintas hari</option>
                            </select>
                        </label>
                        @if ($filterAktif > 0)
                            <a href="{{ route('konfigurasi.jamkerja') }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                <div class="list-meta">
                    @if ($jam_kerja->total() > 0)
                        Menampilkan <strong>{{ $jam_kerja->firstItem() }}–{{ $jam_kerja->lastItem() }}</strong>
                        dari <strong>{{ $jam_kerja->total() }}</strong> jam kerja
                    @endif
                </div>

                @if ($jam_kerja->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada jam kerja yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                Ubah kata kunci atau filter — atau <a href="{{ route('konfigurasi.jamkerja') }}">reset filter</a>.
                            @else
                                Tambahkan shift pertama lewat tombol di kanan atas.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Jam kerja</th>
                                    <th>Jendela absen masuk</th>
                                    <th>Jam kerja</th>
                                    <th>Tipe</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jam_kerja as $d)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name">{{ $d->nama_jam_kerja }}</span>
                                            <span class="person-sub">Kode {{ $d->kode_jam_kerja }}</span>
                                        </td>
                                        <td data-label="Absen masuk" class="cell-num">
                                            <div class="cell-main">{{ $jam($d->awal_jam_masuk) }} – {{ $jam($d->akhir_jam_masuk) }}</div>
                                        </td>
                                        <td data-label="Jam kerja" class="cell-num">
                                            <div class="cell-main fw-medium">{{ $jam($d->jam_masuk) }} – {{ $jam($d->jam_pulang) }}</div>
                                            <div class="cell-sub">masuk – pulang</div>
                                        </td>
                                        <td data-label="Tipe">
                                            <span class="emp-status emp-status--{{ $d->lintashari == 1 ? 'warning' : 'neutral' }}">
                                                {{ $d->lintashari == 1 ? 'Lintas hari' : 'Normal' }}
                                            </span>
                                        </td>
                                        <td class="cell-actions">
                                            @canany(['jam-kerja-edit-admin', 'jam-kerja-delete-admin'])
                                                <x-admin.row-menu :label="$d->nama_jam_kerja">
                                                    @can('jam-kerja-edit-admin')
                                                        <button type="button" class="dropdown-item edit-jk" data-kode_jk="{{ $d->kode_jam_kerja }}">Edit</button>
                                                    @endcan
                                                    @can('jam-kerja-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger delete-confirm-jk"
                                                            data-kode_jk="{{ $d->kode_jam_kerja }}" data-nama_jk="{{ $d->nama_jam_kerja }}">Hapus</button>
                                                    @endcan
                                                </x-admin.row-menu>
                                                @can('jam-kerja-delete-admin')
                                                    <form action="{{ route('konfigurasi.destroyjamkerja', ['kode_jam_kerja' => $d->kode_jam_kerja]) }}"
                                                        method="POST" id="deleteFormJK{{ $d->kode_jam_kerja }}" hidden>
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endcan
                                            @endcanany
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($jam_kerja->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $jam_kerja->currentPage() }} dari {{ $jam_kerja->lastPage() }}</span>
                        {{ $jam_kerja->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('jam-kerja-create-admin')
        <div class="modal modal-blur fade" id="modal-inputjk" tabindex="-1" aria-labelledby="judulTambahJK" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahJK">Tambah Jam Kerja</h5>
                            <p class="modal-subtitle">Absen masuk hanya diterima di antara awal dan batas akhir masuk.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('konfigurasi.storejamkerja') }}" method="POST" id="formJK" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <fieldset class="form-section">
                                <legend class="form-section-title">Identitas</legend>
                                <div class="form-grid">
                                    <div>
                                        <label class="form-label required" for="kode_jam_kerja">Kode</label>
                                        <input type="text" id="kode_jam_kerja" name="kode_jam_kerja" value="{{ old('kode_jam_kerja') }}"
                                            class="form-control" placeholder="Contoh: JK01" maxlength="4" autocomplete="off">
                                        <div class="form-hint">4 huruf kapital/angka.</div>
                                    </div>
                                    <div>
                                        <label class="form-label required" for="nama_jam_kerja">Nama</label>
                                        <input type="text" id="nama_jam_kerja" name="nama_jam_kerja" value="{{ old('nama_jam_kerja') }}"
                                            class="form-control" placeholder="Contoh: Shift Pagi">
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="form-section">
                                <legend class="form-section-title">Waktu</legend>
                                <div class="form-grid">
                                    <div>
                                        <label class="form-label required" for="awal_jam_masuk">Awal absen masuk</label>
                                        <input type="time" id="awal_jam_masuk" name="awal_jam_masuk" value="{{ old('awal_jam_masuk') }}" class="form-control">
                                    </div>
                                    <div>
                                        <label class="form-label required" for="akhir_jam_masuk">Batas akhir masuk</label>
                                        <input type="time" id="akhir_jam_masuk" name="akhir_jam_masuk" value="{{ old('akhir_jam_masuk') }}" class="form-control">
                                    </div>
                                    <div>
                                        <label class="form-label required" for="jam_masuk">Jam masuk</label>
                                        <input type="time" id="jam_masuk" name="jam_masuk" value="{{ old('jam_masuk') }}" class="form-control">
                                        <div class="form-hint">Lewat dari jam ini dihitung terlambat.</div>
                                    </div>
                                    <div>
                                        <label class="form-label required" for="jam_pulang">Jam pulang</label>
                                        <input type="time" id="jam_pulang" name="jam_pulang" value="{{ old('jam_pulang') }}" class="form-control">
                                    </div>
                                    <div class="form-grid-full">
                                        <label class="form-label required" for="lintashari">Tipe</label>
                                        <select name="lintashari" id="lintashari" class="form-select">
                                            <option value="">Pilih tipe</option>
                                            <option value="0" @selected(old('lintashari') === '0')>Normal (satu hari)</option>
                                            <option value="1" @selected(old('lintashari') === '1')>Lintas hari</option>
                                        </select>
                                        <div class="form-hint">Pilih “Lintas hari” bila jam pulang melewati pukul 00:00.</div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal modal-blur fade" id="modal-editjk" tabindex="-1" aria-labelledby="judulEditJK" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditJK">Edit Jam Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadededitformjk" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        function validateJamKerjaForm(e, isEdit) {
            const sfx = isEdit ? '_edit' : '';
            const val = (id) => ($('#' + id + sfx).val() || '').trim();
            const regexJam = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;

            function peringatan(text, id) {
                e.preventDefault();
                Swal.fire({ title: 'Periksa isian', text: text, icon: 'warning', confirmButtonText: 'Ok' })
                    .then(() => $('#' + id + sfx).focus());
                return false;
            }

            if (!isEdit) {
                const kode = val('kode_jam_kerja');
                if (kode === '') return peringatan('Kode jam kerja harus diisi.', 'kode_jam_kerja');
                if (!/^[A-Z0-9]{4}$/.test(kode)) return peringatan('Kode jam kerja harus 4 huruf kapital/angka.', 'kode_jam_kerja');
            }
            if (val('nama_jam_kerja') === '') return peringatan('Nama jam kerja harus diisi.', 'nama_jam_kerja');

            const waktu = { awal_jam_masuk: 'Awal absen masuk', jam_masuk: 'Jam masuk', akhir_jam_masuk: 'Batas akhir masuk', jam_pulang: 'Jam pulang' };
            for (const id in waktu) {
                if (!regexJam.test(val(id))) return peringatan(waktu[id] + ' harus diisi (JJ:MM).', id);
            }
            if (val('lintashari') === '') return peringatan('Tipe jam kerja harus dipilih.', 'lintashari');

            return true;
        }

        $(function() {
            $('#formJK').on('submit', function(e) {
                return validateJamKerjaForm(e, false);
            });

            $(document).on('submit', '#formJamKerjaEdit', function(e) {
                return validateJamKerjaForm(e, true);
            });

            $('#kode_jam_kerja').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            $('.edit-jk').on('click', function() {
                $.get('/konfigurasi/jamkerja/' + $(this).data('kode_jk') + '/edit', function(data) {
                    $('#loadededitformjk').html(data);
                    $('#modal-editjk').modal('show');
                }).fail(function() {
                    Swal.fire('Gagal', 'Form edit jam kerja tidak dapat dimuat.', 'error');
                });
            });

            // Hapus: tampilkan dulu data yang bergantung pada jam kerja ini.
            $('.delete-confirm-jk').on('click', function() {
                let kode = $(this).data('kode_jk');
                hapusDenganRelasi({
                    url: '/konfigurasi/jamkerja/' + kode + '/relations',
                    jenis: 'jam kerja',
                    nama: $(this).data('nama_jk') + ' (' + kode + ')',
                    form: document.getElementById('deleteFormJK' + kode),
                    kosongkan: { presensi: 'riwayat presensi' },
                    ikutTerhapus: { configuration: 'konfigurasi jam kerja departemen', personal: 'set jam kerja karyawan' }
                });
            });
        });
    </script>
@endpush
