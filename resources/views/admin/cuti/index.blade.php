@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Master Cuti</h2>
                    <p class="page-subtitle">Jenis cuti beserta jatah hari per tahun.</p>
                </div>
                @can('cuti-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="btnTambahCuti" data-bs-toggle="modal"
                            data-bs-target="#modal-inputcuti" aria-label="Tambah jenis cuti">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Cuti</span>
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
            <section class="card list-card" aria-label="Daftar jenis cuti">
                <form action="{{ route('cuti.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_cuti" placeholder="Cari nama cuti…" label="Cari cuti" />
                    </div>
                </form>

                <div class="list-meta">
                    @if ($cuti->total() > 0)
                        Menampilkan <strong>{{ $cuti->firstItem() }}–{{ $cuti->lastItem() }}</strong>
                        dari <strong>{{ $cuti->total() }}</strong> jenis cuti
                    @endif
                </div>

                @if ($cuti->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada jenis cuti yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if (request()->filled('nama_cuti'))
                                Ubah kata kunci — atau <a href="{{ route('cuti.index') }}">tampilkan semua</a>.
                            @else
                                Tambahkan jenis cuti pertama lewat tombol di kanan atas.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Jenis cuti</th>
                                    <th>Kode</th>
                                    <th class="text-end">Jatah</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cuti as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name">{{ $data->nama_cuti }}</span>
                                        </td>
                                        <td data-label="Kode" class="cell-num">{{ $data->kode_cuti }}</td>
                                        <td data-label="Jatah" class="cell-num text-lg-end">{{ $data->jml_hari }} hari</td>
                                        <td class="cell-actions">
                                            @canany(['cuti-edit-admin', 'cuti-delete-admin'])
                                                <x-admin.row-menu :label="$data->nama_cuti">
                                                    @can('cuti-edit-admin')
                                                        <button type="button" class="dropdown-item edit-cuti"
                                                            data-kode_cuti="{{ $data->kode_cuti }}">Edit</button>
                                                    @endcan
                                                    @can('cuti-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger delete-confirm-cuti"
                                                            data-kode_cuti="{{ $data->kode_cuti }}"
                                                            data-nama_cuti="{{ $data->nama_cuti }}">Hapus</button>
                                                    @endcan
                                                </x-admin.row-menu>
                                                @can('cuti-delete-admin')
                                                    <form action="{{ route('cuti.destroy', ['kode_cuti' => $data->kode_cuti]) }}"
                                                        method="POST" id="deleteFormCuti{{ $data->kode_cuti }}" hidden>
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

                @if ($cuti->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $cuti->currentPage() }} dari {{ $cuti->lastPage() }}</span>
                        {{ $cuti->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('cuti-create-admin')
        <div class="modal modal-blur fade" id="modal-inputcuti" tabindex="-1" aria-labelledby="judulTambahCuti" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahCuti">Tambah Jenis Cuti</h5>
                            <p class="modal-subtitle">Jatah hari berlaku per karyawan per tahun.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('cuti.store') }}" method="POST" id="formCuti" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <div class="form-grid">
                                <div>
                                    <label class="form-label required" for="kode_cuti">Kode cuti</label>
                                    <input type="text" id="kode_cuti" name="kode_cuti" value="{{ old('kode_cuti') }}"
                                        class="form-control" placeholder="Contoh: C01" maxlength="3" autocomplete="off"
                                        data-field="Kode Cuti">
                                    <div class="form-hint">3 huruf kapital/angka.</div>
                                </div>
                                <div>
                                    <label class="form-label required" for="jml_hari">Jatah</label>
                                    <div class="input-group">
                                        <input type="text" id="jml_hari" name="jml_hari" value="{{ old('jml_hari') }}"
                                            class="form-control" placeholder="12" inputmode="numeric" data-field="Jumlah Hari">
                                        <span class="input-group-text">hari</span>
                                    </div>
                                </div>
                                <div class="form-grid-full">
                                    <label class="form-label required" for="nama_cuti">Nama cuti</label>
                                    <input type="text" id="nama_cuti" name="nama_cuti" value="{{ old('nama_cuti') }}"
                                        class="form-control" placeholder="Contoh: Cuti Tahunan" data-field="Nama Cuti">
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

    <div class="modal modal-blur fade" id="modal-editcuti" tabindex="-1" aria-labelledby="judulEditCuti" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditCuti">Edit Jenis Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadededitformcuti" class="modal-form-body"></div>
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

        function validateCutiForm(e, isEdit) {
            const val = (id) => $('#' + id + (isEdit ? '_edit' : '')).val().trim();
            const kode_cuti = val('kode_cuti');
            const jml_hari = parseInt(val('jml_hari'));

            function peringatan(text, id) {
                e.preventDefault();
                Swal.fire({ title: 'Periksa isian', text: text, icon: 'warning', confirmButtonText: 'Ok' })
                    .then(() => $('#' + id + (isEdit ? '_edit' : '')).focus());
                return false;
            }

            if (!isEdit) {
                if (kode_cuti === '') return peringatan('Kode cuti harus diisi.', 'kode_cuti');
                if (!/^[A-Z0-9]{3}$/.test(kode_cuti)) return peringatan('Kode cuti harus 3 huruf kapital/angka.', 'kode_cuti');
            }
            if (val('nama_cuti') === '') return peringatan('Nama cuti harus diisi.', 'nama_cuti');
            if (isNaN(jml_hari)) return peringatan('Jumlah hari harus diisi dengan angka.', 'jml_hari');
            if (jml_hari < 1 || jml_hari > 365) return peringatan('Jumlah hari harus antara 1 sampai 365.', 'jml_hari');

            return true;
        }

        $(function() {
            $('#formCuti').on('submit', function(e) {
                return validateCutiForm(e, false);
            });

            $(document).on('submit', '#formCutiEdit', function(e) {
                return validateCutiForm(e, true);
            });

            $('.edit-cuti').on('click', function() {
                $.get('/cuti/' + $(this).data('kode_cuti') + '/edit', function(data) {
                    $('#loadededitformcuti').html(data);
                    $('#modal-editcuti').modal('show');
                }).fail(function() {
                    Swal.fire('Gagal', 'Form edit cuti tidak dapat dimuat.', 'error');
                });
            });

            // Hapus: tampilkan dulu riwayat pengajuan yang memakai jenis cuti ini.
            $('.delete-confirm-cuti').on('click', function() {
                let kode = $(this).data('kode_cuti');
                hapusDenganRelasi({
                    url: '/cuti/' + kode + '/relations',
                    jenis: 'jenis cuti',
                    nama: $(this).data('nama_cuti') + ' (' + kode + ')',
                    form: document.getElementById('deleteFormCuti' + kode),
                    kosongkan: { izin: 'riwayat pengajuan izin/cuti karyawan' }
                });
            });

            $('#kode_cuti').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });
            $('#jml_hari').on('keypress', onlyNumberInput);
            $(document).on('keypress', '#jml_hari_edit', onlyNumberInput);
        });
    </script>
@endpush
