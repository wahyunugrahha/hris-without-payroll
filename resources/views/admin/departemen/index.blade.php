@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Departemen</h2>
                    <p class="page-subtitle">Unit kerja yang dipakai di data karyawan, jam kerja, dan KPI.</p>
                </div>
                @can('departemen-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="btnTambahDepartemen" data-bs-toggle="modal"
                            data-bs-target="#modal-inputdepartemen" aria-label="Tambah departemen">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Departemen</span>
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
            <section class="card list-card" aria-label="Daftar departemen">
                <form action="{{ route('departemen.index') }}" method="GET" class="list-toolbar">
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_dept" placeholder="Cari nama departemen…" label="Cari departemen" />
                    </div>
                </form>

                <div class="list-meta">
                    @if ($departemen->total() > 0)
                        Menampilkan <strong>{{ $departemen->firstItem() }}–{{ $departemen->lastItem() }}</strong>
                        dari <strong>{{ $departemen->total() }}</strong> departemen
                    @endif
                </div>

                @if ($departemen->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada departemen yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if (request()->filled('nama_dept'))
                                Ubah kata kunci — atau <a href="{{ route('departemen.index') }}">tampilkan semua</a>.
                            @else
                                Tambahkan departemen pertama lewat tombol di kanan atas.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Departemen</th>
                                    <th>Kode</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($departemen as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name">{{ $data->nama_dept }}</span>
                                        </td>
                                        <td data-label="Kode" class="cell-num">{{ $data->kode_dept }}</td>
                                        <td class="cell-actions">
                                            @canany(['departemen-edit-admin', 'departemen-delete-admin'])
                                                <x-admin.row-menu :label="$data->nama_dept">
                                                    @can('departemen-edit-admin')
                                                        <button type="button" class="dropdown-item edit-dept"
                                                            data-kode_dept="{{ $data->kode_dept }}">Edit</button>
                                                    @endcan
                                                    @can('departemen-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger delete-confirm-dept"
                                                            data-kode_dept="{{ $data->kode_dept }}"
                                                            data-nama_dept="{{ $data->nama_dept }}">Hapus</button>
                                                    @endcan
                                                </x-admin.row-menu>
                                                @can('departemen-delete-admin')
                                                    <form action="{{ route('departemen.destroy', ['kode_dept' => $data->kode_dept]) }}"
                                                        method="POST" id="deleteFormDept{{ $data->kode_dept }}" hidden>
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

                @if ($departemen->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $departemen->currentPage() }} dari {{ $departemen->lastPage() }}</span>
                        {{ $departemen->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('departemen-create-admin')
        <div class="modal modal-blur fade" id="modal-inputdepartemen" tabindex="-1" aria-labelledby="judulTambahDept" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahDept">Tambah Departemen</h5>
                            <p class="modal-subtitle">Kode dipakai sebagai acuan di data lain.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('departemen.store') }}" method="POST" id="formDepartemen" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="kode_dept">Kode departemen</label>
                                <input type="text" id="kode_dept" name="kode_dept" value="{{ old('kode_dept') }}"
                                    class="form-control" placeholder="Contoh: HRD" maxlength="3" autocomplete="off"
                                    data-field="Kode Departemen">
                                <div class="form-hint">3 karakter huruf kapital atau angka.</div>
                            </div>
                            <div>
                                <label class="form-label required" for="nama_dept">Nama departemen</label>
                                <input type="text" id="nama_dept" name="nama_dept" value="{{ old('nama_dept') }}"
                                    class="form-control" placeholder="Contoh: Human Resource" data-field="Nama Departemen">
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

    <div class="modal modal-blur fade" id="modal-editdepartemen" tabindex="-1" aria-labelledby="judulEditDept" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditDept">Edit Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadededitformdept" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        function validateDepartemenForm(e, isEdit) {
            const kode_dept = isEdit ? $('#kode_dept_edit_dept').val().trim() : $('#kode_dept').val().trim();
            const nama_dept = isEdit ? $('#nama_dept_edit').val().trim() : $('#nama_dept').val().trim();

            function peringatan(text, id) {
                e.preventDefault();
                Swal.fire({ title: 'Periksa isian', text: text, icon: 'warning', confirmButtonText: 'Ok' })
                    .then(() => $(id).focus());
                return false;
            }

            if (!isEdit) {
                if (kode_dept === '') return peringatan('Kode departemen harus diisi.', '#kode_dept');
                if (!/^[A-Z0-9]{3}$/.test(kode_dept)) return peringatan('Kode departemen harus 3 karakter huruf kapital/angka.', '#kode_dept');
            }
            if (nama_dept === '') return peringatan('Nama departemen harus diisi.', isEdit ? '#nama_dept_edit' : '#nama_dept');

            return true;
        }

        $(function() {
            $('#formDepartemen').on('submit', function(e) {
                return validateDepartemenForm(e, false);
            });

            $(document).on('submit', '#formDepartemenEdit', function(e) {
                return validateDepartemenForm(e, true);
            });

            $('.edit-dept').on('click', function() {
                $.get('/departemen/' + $(this).data('kode_dept') + '/edit', function(data) {
                    $('#loadededitformdept').html(data);
                    $('#modal-editdepartemen').modal('show');
                }).fail(function() {
                    Swal.fire('Gagal', 'Form edit departemen tidak dapat dimuat.', 'error');
                });
            });

            // Hapus: tampilkan dulu data yang bergantung pada departemen ini.
            $('.delete-confirm-dept').on('click', function() {
                let kode = $(this).data('kode_dept');
                hapusDenganRelasi({
                    url: '/departemen/' + kode + '/relations',
                    jenis: 'departemen',
                    nama: $(this).data('nama_dept') + ' (' + kode + ')',
                    form: document.getElementById('deleteFormDept' + kode),
                    kosongkan: { karyawan: 'karyawan', user: 'user admin' },
                    ikutTerhapus: { configuration: 'konfigurasi jam kerja', holiday: 'hari libur', kpi: 'data KPI' }
                });
            });
        });
    </script>
@endpush
