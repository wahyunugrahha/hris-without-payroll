<form action="{{ route('kpi.master.update', $kpi->id) }}" method="POST" id="formKpiEdit">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-6">
            {{-- Kode KPI --}}
            <div class="mb-3">
                <label class="form-label required">Kode KPI</label>
                <input type="text" name="kode_master" value="{{ old('kode_master', $kpi->kode_master) }}"
                    class="form-control @error('kode_master') is-invalid @enderror" placeholder="Contoh: KPI-001"
                    required>
                @error('kode_master')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            {{-- Status --}}
            <div class="mb-3">
                <label class="form-label required">Status</label>
                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                    <option value="1" {{ old('is_active', $kpi->is_active) == 1 ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ old('is_active', $kpi->is_active) == 0 ? 'selected' : '' }}>Nonaktif
                    </option>
                </select>
                @error('is_active')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12">
            {{-- Nama KPI --}}
            <div class="mb-3">
                <label class="form-label required">Nama KPI</label>
                <input type="text" name="nama_kpi" value="{{ old('nama_kpi', $kpi->nama_kpi) }}"
                    class="form-control @error('nama_kpi') is-invalid @enderror"
                    placeholder="Masukkan deskripsi indikator kinerja" required>
                @error('nama_kpi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div class="mb-3">
                <label class="form-label required">Jabatan</label>
                <select name="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                    <option value="">Pilih Jabatan</option>
                    @foreach ($jabatan as $j)
                        <option value="{{ $j->id }}"
                            {{ old('jabatan_id', $kpi->jabatan_id) == $j->id ? 'selected' : '' }}>
                            {{ $j->nama_jabatan }}
                        </option>
                    @endforeach
                </select>
                @error('jabatan_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            {{-- Departemen --}}
            <div class="mb-3">
                <label class="form-label required">Departemen</label>
                <select name="kode_dept" class="form-select @error('kode_dept') is-invalid @enderror" required>
                    <option value="">Pilih Departemen</option>
                    @foreach ($departemen as $d)
                        <option value="{{ $d->kode_dept }}"
                            {{ old('kode_dept', $kpi->kode_dept) == $d->kode_dept ? 'selected' : '' }}>
                            {{ $d->nama_dept }}
                        </option>
                    @endforeach
                </select>
                @error('kode_dept')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- CHECKLIST CABANG (BULK) --}}
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label required">Pilih Cabang (Checklist)</label>
                <div
                    style="border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; max-height: 250px; overflow-y: auto;">
                    @forelse ($cabang as $c)
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input cabang-checkbox-edit"
                                id="edit_cabang_{{ $c->kode_cabang }}" name="kode_cabang[]"
                                value="{{ $c->kode_cabang }}"
                                {{ in_array($c->kode_cabang, old('kode_cabang', $selectedCabangs ?? [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="edit_cabang_{{ $c->kode_cabang }}">
                                {{ $c->nama_cabang }}
                            </label>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Tidak ada cabang tersedia</p>
                    @endforelse
                </div>
                <small class="text-danger d-block mt-2" id="cabang-error-edit" style="display:none;">
                    <strong>⚠ Minimal harus memilih 1 cabang!</strong>
                </small>
                @error('kode_cabang')
                    <small class="text-danger d-block mt-2">
                        <strong>⚠ {{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="row">
        <div class="col-12 text-end">
            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                Batal
            </button>
            <button type="submit" class="btn btn-primary ms-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy"
                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"></path>
                    <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                    <path d="M14 4l0 4l-4 0l0 -4"></path>
                </svg>
                Simpan Perubahan
            </button>
        </div>
    </div>
</form>
