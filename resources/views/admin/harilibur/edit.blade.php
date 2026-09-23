@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Edit Hari Libur</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <circle cx="12" cy="12" r="9" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <div>{{ session('error') }}</div>
                    <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            <form action="{{ route('harilibur.update', $master->id) }}" method="POST" autocomplete="off" id="form-libur">
                @csrf
                @method('PUT')

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom-1 py-3">
                        <h3 class="card-title mb-0">Form Update</h3>
                    </div>
                    <div class="card-body">
                        {{-- Baris 1: Input Dasar --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted fw-bold required">Tanggal Libur</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <rect x="4" y="5" width="16" height="16" rx="2" />
                                            <line x1="16" y1="3" x2="16" y2="7" />
                                            <line x1="8" y1="3" x2="8" y2="7" />
                                            <line x1="4" y1="11" x2="20" y2="11" />
                                            <line x1="11" y1="15" x2="12" y2="15" />
                                            <line x1="12" y1="15" x2="12" y2="18" />
                                        </svg>
                                    </span>
                                    <input type="date" name="tanggal_libur"
                                        class="form-control @error('tanggal_libur') is-invalid @enderror"
                                        value="{{ old('tanggal_libur', \Carbon\Carbon::parse($master->tanggal_libur)->format('Y-m-d')) }}"
                                        required>
                                </div>
                                @error('tanggal_libur')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted fw-bold required">Kategori</label>
                                <select class="form-select @error('jenis_libur') is-invalid @enderror" name="jenis_libur"
                                    id="jenis_libur" required>
                                    <option value="" disabled>Pilih Kategori</option>
                                    <option value="nasional"
                                        {{ old('jenis_libur', $master->jenis_libur) == 'nasional' ? 'selected' : '' }}>
                                        Libur Nasional
                                    </option>
                                    <option value="cuti_bersama"
                                        {{ old('jenis_libur', $master->jenis_libur) == 'cuti_bersama' ? 'selected' : '' }}>
                                        Cuti Bersama
                                    </option>
                                    <option value="lokal"
                                        {{ old('jenis_libur', $master->jenis_libur) == 'lokal' ? 'selected' : '' }}>
                                        Lokal / Khusus
                                    </option>
                                </select>
                                @error('jenis_libur')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted fw-bold required">Keterangan</label>
                                <input type="text" name="keterangan"
                                    class="form-control @error('keterangan') is-invalid @enderror"
                                    placeholder="Contoh: HUT Kota Surabaya"
                                    value="{{ old('keterangan', $master->keterangan) }}" required>
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Baris 2: Cakupan Wilayah --}}
                        <div id="section_lokasi">
                            <div class="hr-text text-uppercase mt-4 mb-3">Cakupan Wilayah</div>

                            <div class="alert alert-info bg-blue-lt border-0 mb-3" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="12" r="9" />
                                            <line x1="12" y1="8" x2="12.01" y2="8" />
                                            <polyline points="11 12 12 12 12 16 13 16" />
                                        </svg>
                                    </div>
                                    <div>
                                        <strong>Info:</strong> Kosongkan semua pilihan jika ingin mengubah menjadi libur
                                        <strong>Semua Cabang/Departemen</strong>.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold mb-0">Pilih Cabang</label>
                                        <span class="badge bg-secondary-lt" id="count-cabang">0 terpilih</span>
                                    </div>
                                    <div class="card" style="height: 300px; overflow-y: auto;">
                                        <div class="card-body p-2">
                                            <label
                                                class="form-check sticky-top bg-white border-bottom pb-2 mb-2 d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" id="checkAllCabang">
                                                <span class="form-check-label fw-bold">Pilih Semua Cabang</span>
                                            </label>

                                            <div class="row g-2">
                                                @foreach ($cabang as $c)
                                                    <div class="col-sm-6">
                                                        <label class="form-check">
                                                            <input type="checkbox"
                                                                class="form-check-input check-cabang-item"
                                                                name="kode_cabang[]" value="{{ $c->kode_cabang }}"
                                                                {{ in_array($c->kode_cabang, old('kode_cabang', $selectedCabang)) ? 'checked' : '' }}>
                                                            <span class="form-check-label text-truncate"
                                                                title="{{ $c->nama_cabang }}">
                                                                {{ $c->nama_cabang }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @error('kode_cabang')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold mb-0">Pilih Departemen</label>
                                        <span class="badge bg-secondary-lt" id="count-dept">0 terpilih</span>
                                    </div>
                                    <div class="card" style="height: 300px; overflow-y: auto;">
                                        <div class="card-body p-2">
                                            <label
                                                class="form-check sticky-top bg-white border-bottom pb-2 mb-2 d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" id="checkAllDept">
                                                <span class="form-check-label fw-bold">Pilih Semua Departemen</span>
                                            </label>

                                            <div class="row g-2">
                                                @foreach ($departemen as $d)
                                                    <div class="col-sm-6">
                                                        <label class="form-check">
                                                            <input type="checkbox"
                                                                class="form-check-input check-dept-item"
                                                                name="kode_dept[]" value="{{ $d->kode_dept }}"
                                                                {{ in_array($d->kode_dept, old('kode_dept', $selectedDept)) ? 'checked' : '' }}>
                                                            <span class="form-check-label text-truncate"
                                                                title="{{ $d->nama_dept }}">
                                                                {{ $d->nama_dept }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @error('kode_dept')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('harilibur.index') }}" class="btn btn-link link-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M14 4l0 4l-6 0l0 -4" />
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(document).ready(function() {
            // Logic Checkbox Cabang (Select All)
            $('#checkAllCabang').change(function() {
                $('.check-cabang-item').prop('checked', $(this).prop('checked'));
                updateCount();
            });

            $('.check-cabang-item').change(function() {
                let allChecked = $('.check-cabang-item:checked').length == $('.check-cabang-item').length;
                let anyUnchecked = false === $(this).prop("checked");

                if (anyUnchecked) $('#checkAllCabang').prop('checked', false);
                if (allChecked) $('#checkAllCabang').prop('checked', true);

                updateCount();
            });

            // Logic Checkbox Departemen (Select All)
            $('#checkAllDept').change(function() {
                $('.check-dept-item').prop('checked', $(this).prop('checked'));
                updateCount();
            });

            $('.check-dept-item').change(function() {
                let allChecked = $('.check-dept-item:checked').length == $('.check-dept-item').length;
                let anyUnchecked = false === $(this).prop("checked");

                if (anyUnchecked) $('#checkAllDept').prop('checked', false);
                if (allChecked) $('#checkAllDept').prop('checked', true);

                updateCount();
            });

            // Helper: Update text jumlah & cek status Select All saat load
            function updateCount() {
                let countCabang = $('.check-cabang-item:checked').length;
                let countDept = $('.check-dept-item:checked').length;

                $('#count-cabang').text(countCabang + ' terpilih');
                $('#count-dept').text(countDept + ' terpilih');

                // Auto-check "Select All" jika data loaded dari server sudah full
                if (countCabang > 0 && countCabang == $('.check-cabang-item').length) {
                    $('#checkAllCabang').prop('checked', true);
                }
                if (countDept > 0 && countDept == $('.check-dept-item').length) {
                    $('#checkAllDept').prop('checked', true);
                }
            }

            updateCount();
        });
    </script>
@endpush
