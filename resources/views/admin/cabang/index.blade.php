@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Kantor Cabang</h2>
                    <p class="page-subtitle">Lokasi kantor & radius yang dipakai untuk validasi absensi.</p>
                </div>
                @can('cabang-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="btnTambahCabang" data-bs-toggle="modal"
                            data-bs-target="#modal-inputcabang" aria-label="Tambah cabang">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Cabang</span>
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
            <section class="card list-card" aria-label="Daftar cabang">
                <form action="{{ route('cabang.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_cabang" placeholder="Cari nama cabang…" label="Cari cabang" />
                    </div>
                </form>

                <div class="list-meta">
                    @if ($cabang->total() > 0)
                        Menampilkan <strong>{{ $cabang->firstItem() }}–{{ $cabang->lastItem() }}</strong>
                        dari <strong>{{ $cabang->total() }}</strong> cabang
                    @endif
                </div>

                @if ($cabang->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada cabang yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if (request()->filled('nama_cabang'))
                                Ubah kata kunci — atau <a href="{{ route('cabang.index') }}">tampilkan semua</a>.
                            @else
                                Tambahkan cabang pertama lewat tombol di kanan atas.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Cabang</th>
                                    <th>Lokasi kantor</th>
                                    <th class="text-end">Radius absen</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cabang as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <a href="{{ route('cabanglokasi.index', ['kode_cabang' => $data->kode_cabang]) }}" class="person">
                                                <span class="min-w-0">
                                                    <span class="person-name">{{ $data->nama_cabang }}</span>
                                                    <span class="person-sub">Kode {{ $data->kode_cabang }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td data-label="Lokasi" class="cell-num text-secondary">{{ $data->lokasi_kantor ?: '-' }}</td>
                                        <td data-label="Radius" class="cell-num text-lg-end">{{ number_format((int) $data->radius, 0, ',', '.') }} m</td>
                                        <td class="cell-actions">
                                            <x-admin.row-menu :label="$data->nama_cabang">
                                                <a href="{{ route('cabanglokasi.index', ['kode_cabang' => $data->kode_cabang]) }}"
                                                    class="dropdown-item">Kelola titik lokasi</a>
                                                @can('cabang-edit-admin')
                                                    <button type="button" class="dropdown-item edit-cabang"
                                                        data-kode_cabang="{{ $data->kode_cabang }}">Edit</button>
                                                @endcan
                                                @can('cabang-delete-admin')
                                                    <div class="dropdown-divider"></div>
                                                    <button type="button" class="dropdown-item text-danger delete-confirm-cabang"
                                                        data-kode_cabang="{{ $data->kode_cabang }}"
                                                        data-nama_cabang="{{ $data->nama_cabang }}">Hapus</button>
                                                @endcan
                                            </x-admin.row-menu>
                                            @can('cabang-delete-admin')
                                                <form action="{{ route('cabang.destroy', ['kode_cabang' => $data->kode_cabang]) }}"
                                                    method="POST" id="deleteFormCabang{{ $data->kode_cabang }}" hidden>
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($cabang->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $cabang->currentPage() }} dari {{ $cabang->lastPage() }}</span>
                        {{ $cabang->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('cabang-create-admin')
        <div class="modal modal-blur fade" id="modal-inputcabang" tabindex="-1" aria-labelledby="judulTambahCabang" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahCabang">Tambah Cabang</h5>
                            <p class="modal-subtitle">Titik lokasi tambahan bisa diatur setelah cabang dibuat.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('cabang.store') }}" method="POST" id="formCabang" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <div class="form-grid">
                                <div>
                                    <label class="form-label required" for="kode_cabang">Kode cabang</label>
                                    <input type="text" id="kode_cabang" name="kode_cabang" value="{{ old('kode_cabang') }}"
                                        class="form-control" placeholder="Contoh: JKT01" maxlength="8" autocomplete="off"
                                        data-field="Kode Cabang">
                                    <div class="form-hint">3–8 huruf kapital/angka.</div>
                                </div>
                                <div>
                                    <label class="form-label required" for="nama_cabang">Nama cabang</label>
                                    <input type="text" id="nama_cabang" name="nama_cabang" value="{{ old('nama_cabang') }}"
                                        class="form-control" placeholder="Contoh: Kantor Jakarta" data-field="Nama Cabang">
                                </div>
                                <div class="form-grid-full">
                                    <label class="form-label required" for="lokasi_kantor">Lokasi kantor</label>
                                    <input type="text" id="lokasi_kantor" name="lokasi_kantor" value="{{ old('lokasi_kantor') }}"
                                        class="form-control" placeholder="-6.200000,106.816666" data-field="Lokasi Kantor">
                                    <div class="form-hint">Koordinat latitude,longitude.</div>
                                </div>
                                <div>
                                    <label class="form-label required" for="radius">Radius absen</label>
                                    <div class="input-group">
                                        <input type="text" id="radius" name="radius" value="{{ old('radius') }}" class="form-control"
                                            placeholder="100" inputmode="numeric" data-field="Radius" onkeypress="onlyNumberInput(event)">
                                        <span class="input-group-text">meter</span>
                                    </div>
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
    @endcan

    <div class="modal modal-blur fade" id="modal-editcabang" tabindex="-1" aria-labelledby="judulEditCabang" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditCabang">Edit Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadededitformcabang" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        function onlyNumberInput(event) {
            if (!/^\d$/.test(event.key) && !event.metaKey && !event.ctrlKey && event.key.length === 1) {
                event.preventDefault();
            }
        }

        function validateCabangForm(e, isEdit) {
            const val = (id) => $('#' + id + (isEdit ? '_edit' : '')).val().trim();
            const kode_cabang = val('kode_cabang');
            const radius = val('radius');

            function peringatan(text, id) {
                e.preventDefault();
                Swal.fire({ title: 'Periksa isian', text: text, icon: 'warning', confirmButtonText: 'Ok' })
                    .then(() => $('#' + id + (isEdit ? '_edit' : '')).focus());
                return false;
            }

            if (!isEdit) {
                if (kode_cabang === '') return peringatan('Kode cabang wajib diisi.', 'kode_cabang');
                if (!/^[A-Z0-9]{3,8}$/.test(kode_cabang)) return peringatan('Kode cabang harus 3–8 huruf kapital/angka.', 'kode_cabang');
            }
            if (val('nama_cabang') === '') return peringatan('Nama cabang harus diisi.', 'nama_cabang');
            if (val('lokasi_kantor') === '') return peringatan('Lokasi kantor harus diisi.', 'lokasi_kantor');
            if (radius === '') return peringatan('Radius wajib diisi.', 'radius');
            if (isNaN(radius) || parseInt(radius) <= 0) return peringatan('Radius harus berupa angka positif.', 'radius');

            return true;
        }

        $(function() {
            $('#formCabang').on('submit', function(e) {
                return validateCabangForm(e, false);
            });

            $(document).on('submit', '#formCabangEdit', function(e) {
                return validateCabangForm(e, true);
            });

            $('.edit-cabang').on('click', function() {
                $.get('/cabang/' + $(this).data('kode_cabang') + '/edit', function(data) {
                    $('#loadededitformcabang').html(data);
                    $('#modal-editcabang').modal('show');
                }).fail(function() {
                    Swal.fire('Gagal', 'Form edit cabang tidak dapat dimuat.', 'error');
                });
            });

            // Hapus: tampilkan dulu data yang bergantung pada cabang ini.
            $('.delete-confirm-cabang').on('click', function() {
                let kode = $(this).data('kode_cabang');
                hapusDenganRelasi({
                    url: '/cabang/' + kode + '/relations',
                    jenis: 'cabang',
                    nama: $(this).data('nama_cabang') + ' (' + kode + ')',
                    form: document.getElementById('deleteFormCabang' + kode),
                    kosongkan: { karyawan: 'karyawan', user: 'user admin' },
                    ikutTerhapus: { location: 'titik lokasi', configuration: 'konfigurasi jam kerja', holiday: 'hari libur', kpi: 'data KPI' }
                });
            });
        });
    </script>
@endpush
