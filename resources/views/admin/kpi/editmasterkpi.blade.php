<form action="{{ route('kpi.master.update', $kpi->id) }}" method="POST" id="formKpiEdit" class="modal-form-body">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <fieldset class="form-section">
            <legend class="form-section-title">Identitas</legend>
            <div class="form-grid">
                <div>
                    <label class="form-label required" for="edit-kpi-kode">Kode KPI</label>
                    <input type="text" name="kode_master" id="edit-kpi-kode" value="{{ old('kode_master', $kpi->kode_master) }}"
                        class="form-control @error('kode_master') is-invalid @enderror" required>
                    @error('kode_master')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="form-label required" for="edit-kpi-status">Status</label>
                    <select name="is_active" id="edit-kpi-status" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" @selected(old('is_active', $kpi->is_active) == 1)>Aktif</option>
                        <option value="0" @selected(old('is_active', $kpi->is_active) == 0)>Nonaktif</option>
                    </select>
                </div>
                <div class="form-grid-full">
                    <label class="form-label required" for="edit-kpi-nama">Nama KPI</label>
                    <input type="text" name="nama_kpi" id="edit-kpi-nama" value="{{ old('nama_kpi', $kpi->nama_kpi) }}"
                        class="form-control @error('nama_kpi') is-invalid @enderror" required>
                    @error('nama_kpi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="form-label required" for="edit-kpi-jabatan">Jabatan</label>
                    <select name="jabatan_id" id="edit-kpi-jabatan" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                        <option value="">Pilih jabatan</option>
                        @foreach ($jabatan as $j)
                            <option value="{{ $j->id }}" @selected(old('jabatan_id', $kpi->jabatan_id) == $j->id)>{{ $j->nama_jabatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label required" for="edit-kpi-dept">Departemen</label>
                    <select name="kode_dept" id="edit-kpi-dept" class="form-select @error('kode_dept') is-invalid @enderror" required>
                        <option value="">Pilih departemen</option>
                        @foreach ($departemen as $d)
                            <option value="{{ $d->kode_dept }}" @selected(old('kode_dept', $kpi->kode_dept) == $d->kode_dept)>{{ $d->nama_dept }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>
        <fieldset class="form-section">
            <legend class="form-section-title">Berlaku di cabang</legend>
            <x-admin.check-list name="kode_cabang" :items="$cabang" kode="kode_cabang" label="nama_cabang"
                :selected="old('kode_cabang', $selectedCabangs ?? [])" judul="Semua cabang" item-class="cabang-checkbox-edit" />
        </fieldset>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
    </div>
</form>
