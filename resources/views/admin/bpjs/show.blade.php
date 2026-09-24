@extends('layouts.admin.tabler')

@php
    $diproses = $pengajuan->status === 'processed';
    $kartu = [
        ['kesehatan', 'BPJS Kesehatan', 'no_bpjs_kesehatan', 'foto_bpjs_kesehatan'],
        ['ketenagakerjaan', 'BPJS Ketenagakerjaan', 'no_bpjs_ketenagakerjaan', 'foto_bpjs_ketenagakerjaan'],
    ];
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Permintaan Karyawan / Pengajuan BPJS</div>
                    <h2 class="page-title">{{ $karyawan->nama_lengkap }}</h2>
                    <p class="page-subtitle">
                        NIK {{ $karyawan->nik }} ·
                        <span class="emp-status emp-status--{{ $diproses ? 'success' : 'warning' }}">{{ $diproses ? 'Diproses' : 'Menunggu' }}</span>
                    </p>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.bpjs.index') }}" class="btn">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl container-narrow-lg">
            <form action="{{ route('admin.bpjs.update', $pengajuan->id) }}" method="POST" enctype="multipart/form-data" class="card">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if ($diproses)
                        <p class="text-secondary small mb-3">
                            Diproses {{ optional($pengajuan->processed_at)->format('d M Y H:i') ?? '-' }} oleh {{ $pengajuan->processed_by ?? '-' }}.
                        </p>
                    @endif

                    @foreach ($kartu as [$kunci, $judul, $kolomNo, $kolomFoto])
                        <fieldset class="form-section">
                            <legend class="form-section-title">{{ $judul }}</legend>
                            <div class="form-grid">
                                <div>
                                    <label class="form-label" for="{{ $kolomNo }}">Nomor kartu</label>
                                    <input type="text" name="{{ $kolomNo }}" id="{{ $kolomNo }}" inputmode="numeric"
                                        class="form-control cell-num @error($kolomNo) is-invalid @enderror"
                                        value="{{ old($kolomNo, $karyawan->{$kolomNo}) }}" placeholder="Nomor {{ $judul }}">
                                    @error($kolomNo)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label" for="{{ $kolomFoto }}">Foto kartu</label>
                                    <input type="file" name="{{ $kolomFoto }}" id="{{ $kolomFoto }}" accept=".jpg,.jpeg,.png"
                                        class="form-control @error($kolomFoto) is-invalid @enderror">
                                    <div class="form-hint">JPG/PNG, maksimal 3 MB. Kosongkan bila tidak diganti.</div>
                                    @error($kolomFoto)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if (!empty($karyawan->{$kolomFoto}))
                                    @php $urlFoto = asset_v('storage/uploads/karyawan/bpjs/' . $karyawan->{$kolomFoto}); @endphp
                                    <div class="form-grid-full">
                                        <a href="{{ $urlFoto }}" target="_blank" rel="noopener" class="image-preview">
                                            <img src="{{ $urlFoto }}" alt="Foto kartu {{ $judul }}">
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </fieldset>
                    @endforeach

                    <fieldset class="form-section">
                        <legend class="form-section-title">Catatan</legend>
                        <label class="form-label" for="catatan">Catatan admin <span class="text-secondary fw-normal">(opsional)</span></label>
                        <textarea name="catatan" id="catatan" class="form-control @error('catatan') is-invalid @enderror" rows="3"
                            placeholder="Terlihat oleh karyawan">{{ old('catatan', $pengajuan->catatan) }}</textarea>
                        @error('catatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </fieldset>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.bpjs.index') }}" class="btn">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan data BPJS</button>
                </div>
            </form>
        </div>
    </div>
@endsection
