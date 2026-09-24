{{-- Form hari libur bersama untuk create & edit. $master = null saat menambah. --}}
@php
    $edit = isset($master);
    $jenisTerpilih = old('jenis_libur', $master->jenis_libur ?? null);
    $cabangTerpilih = old('kode_cabang', $selectedCabang ?? []) ?: [];
    $deptTerpilih = old('kode_dept', $selectedDept ?? []) ?: [];
    $pilihan = [
        ['cabang', 'Cabang', 'kode_cabang', $cabang, 'kode_cabang', 'nama_cabang', $cabangTerpilih],
        ['dept', 'Departemen', 'kode_dept', $departemen, 'kode_dept', 'nama_dept', $deptTerpilih],
    ];
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
                @foreach ($pilihan as [$kunci, $label, $nama, $koleksi, $kolomKode, $kolomNama, $terpilih])
                    <div class="check-list" data-check-list>
                        <div class="check-list-head">
                            <label class="form-check m-0">
                                <input class="form-check-input" type="checkbox" data-check-all>
                                <span class="form-check-label fw-medium">Semua {{ strtolower($label) }}</span>
                            </label>
                            <span class="check-list-count" data-check-count>0 dipilih</span>
                        </div>
                        <div class="check-list-body">
                            @foreach ($koleksi as $item)
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" data-check-item name="{{ $nama }}[]"
                                        value="{{ $item->{$kolomKode} }}" @checked(in_array($item->{$kolomKode}, $terpilih))>
                                    <span class="form-check-label text-truncate" title="{{ $item->{$kolomNama} }}">{{ $item->{$kolomNama} }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error($nama)
                            <div class="text-danger small px-3 pb-2">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </fieldset>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('harilibur.index') }}" class="btn">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $edit ? 'Simpan perubahan' : 'Simpan hari libur' }}</button>
    </div>
</form>

@push('myscript')
    <script>
        // Daftar centang dengan "pilih semua" + penghitung.
        document.querySelectorAll('[data-check-list]').forEach(function(daftar) {
            const semua = daftar.querySelector('[data-check-all]');
            const item = daftar.querySelectorAll('[data-check-item]');
            const hitung = daftar.querySelector('[data-check-count]');

            function sinkron() {
                const n = daftar.querySelectorAll('[data-check-item]:checked').length;
                hitung.textContent = n + ' dipilih';
                semua.checked = n > 0 && n === item.length;
                semua.indeterminate = n > 0 && n < item.length;
            }

            semua.addEventListener('change', function() {
                item.forEach((el) => el.checked = semua.checked);
                sinkron();
            });
            item.forEach((el) => el.addEventListener('change', sinkron));
            sinkron();
        });
    </script>
@endpush
