{{-- Form hari libur bersama untuk create & edit. $master = null saat menambah. --}}
@php
    $edit = isset($master);
    $jenisTerpilih = old('jenis_libur', $master->jenis_libur ?? null);
    $cabangTerpilih = old('kode_cabang', $selectedCabang ?? []) ?: [];
    $deptTerpilih = old('kode_dept', $selectedDept ?? []) ?: [];
@endphp

<form action="{{ $edit ? route('harilibur.update', $master->id) : route('harilibur.store') }}" method="POST"
    autocomplete="off" id="form-libur" class="card">
    @csrf
    @if ($edit)
        @method('PUT')
    @endif

    <div class="card-body">
        <fieldset class="form-section">
            <legend class="form-section-title">Detail hari libur</legend>
            <div class="form-grid">
                @if ($edit)
                    <div>
                        <label class="form-label required" for="tanggal_libur">Tanggal</label>
                        <input type="date" name="tanggal_libur" id="tanggal_libur"
                            class="form-control @error('tanggal_libur') is-invalid @enderror"
                            value="{{ old('tanggal_libur', \Carbon\Carbon::parse($master->tanggal_libur)->format('Y-m-d')) }}" required>
                        @error('tanggal_libur')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    <div>
                        <label class="form-label required" for="tanggal_libur_dari">Mulai tanggal</label>
                        <input type="date" name="tanggal_libur_dari" id="tanggal_libur_dari"
                            class="form-control @error('tanggal_libur_dari') is-invalid @enderror"
                            value="{{ old('tanggal_libur_dari') }}" required>
                        @error('tanggal_libur_dari')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label required" for="tanggal_libur_sampai">Sampai tanggal</label>
                        <input type="date" name="tanggal_libur_sampai" id="tanggal_libur_sampai"
                            class="form-control @error('tanggal_libur_sampai') is-invalid @enderror"
                            value="{{ old('tanggal_libur_sampai') }}" required>
                        @error('tanggal_libur_sampai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Isi sama dengan tanggal mulai untuk libur satu hari.</div>
                    </div>
                @endif
                <div>
                    <label class="form-label required" for="jenis_libur">Jenis</label>
                    <select class="form-select @error('jenis_libur') is-invalid @enderror" name="jenis_libur" id="jenis_libur" required>
                        <option value="" disabled @selected(!$jenisTerpilih)>Pilih jenis</option>
                        <option value="nasional" @selected($jenisTerpilih == 'nasional')>Libur nasional</option>
                        <option value="cuti_bersama" @selected($jenisTerpilih == 'cuti_bersama')>Cuti bersama</option>
                        <option value="lokal" @selected($jenisTerpilih == 'lokal')>Lokal / khusus</option>
                    </select>
                    @error('jenis_libur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div @class(['form-grid-full' => !$edit])>
                    <label class="form-label required" for="keterangan">Keterangan</label>
                    <input type="text" name="keterangan" id="keterangan"
                        class="form-control @error('keterangan') is-invalid @enderror" placeholder="Contoh: Idul Fitri"
                        value="{{ old('keterangan', $master->keterangan ?? '') }}" required>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="form-section-title">Berlaku untuk</legend>
            <p class="text-secondary small mb-3">Biarkan semua kosong agar libur berlaku untuk <strong>semua cabang & departemen</strong>.</p>
            <div class="form-grid">
                <x-admin.check-list name="kode_cabang" :items="$cabang" kode="kode_cabang" label="nama_cabang"
                    :selected="$cabangTerpilih" judul="Semua cabang" />
                <x-admin.check-list name="kode_dept" :items="$departemen" kode="kode_dept" label="nama_dept"
                    :selected="$deptTerpilih" judul="Semua departemen" />
            </div>
        </fieldset>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('harilibur.index') }}" class="btn">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $edit ? 'Simpan perubahan' : 'Simpan hari libur' }}</button>
    </div>
</form>
