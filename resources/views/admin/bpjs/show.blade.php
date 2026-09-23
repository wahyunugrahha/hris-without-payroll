@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan</div>
                    <h2 class="page-title">Detail Pengajuan BPJS</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('admin.bpjs.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Update BPJS Karyawan</h3>
                </div>
                <form action="{{ route('admin.bpjs.update', $pengajuan->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control" value="{{ $karyawan->nik }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Karyawan</label>
                                <input type="text" class="form-control" value="{{ $karyawan->nama_lengkap }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. BPJS Kesehatan</label>
                                <input type="text" name="no_bpjs_kesehatan"
                                    class="form-control @error('no_bpjs_kesehatan') is-invalid @enderror"
                                    value="{{ old('no_bpjs_kesehatan', $karyawan->no_bpjs_kesehatan) }}"
                                    placeholder="Masukkan nomor BPJS Kesehatan">
                                @error('no_bpjs_kesehatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. BPJS Ketenagakerjaan</label>
                                <input type="text" name="no_bpjs_ketenagakerjaan"
                                    class="form-control @error('no_bpjs_ketenagakerjaan') is-invalid @enderror"
                                    value="{{ old('no_bpjs_ketenagakerjaan', $karyawan->no_bpjs_ketenagakerjaan) }}"
                                    placeholder="Masukkan nomor BPJS Ketenagakerjaan">
                                @error('no_bpjs_ketenagakerjaan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Foto BPJS Kesehatan</label>
                                <input type="file" name="foto_bpjs_kesehatan"
                                    class="form-control @error('foto_bpjs_kesehatan') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Format JPG/JPEG/PNG maksimal 3MB.</small>
                                @error('foto_bpjs_kesehatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if (!empty($karyawan->foto_bpjs_kesehatan))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_kesehatan) }}"
                                            alt="BPJS Kesehatan" class="img-fluid rounded border"
                                            style="max-height: 150px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Foto BPJS Ketenagakerjaan</label>
                                <input type="file" name="foto_bpjs_ketenagakerjaan"
                                    class="form-control @error('foto_bpjs_ketenagakerjaan') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Format JPG/JPEG/PNG maksimal 3MB.</small>
                                @error('foto_bpjs_ketenagakerjaan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if (!empty($karyawan->foto_bpjs_ketenagakerjaan))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_ketenagakerjaan) }}"
                                            alt="BPJS Ketenagakerjaan" class="img-fluid rounded border"
                                            style="max-height: 150px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label">Catatan Admin (Opsional)</label>
                                <textarea name="catatan" class="form-control @error('catatan') is-invalid @enderror" rows="3"
                                    placeholder="Tambahkan catatan jika diperlukan">{{ old('catatan', $pengajuan->catatan) }}</textarea>
                                @error('catatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if ($pengajuan->status === 'processed')
                            <div class="alert alert-success mt-4 mb-0">
                                Pengajuan ini sudah diproses pada
                                {{ optional($pengajuan->processed_at)->format('d-m-Y H:i') ?? '-' }}
                                oleh {{ $pengajuan->processed_by ?? '-' }}.
                            </div>
                        @endif
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary text-white">Simpan Data BPJS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
