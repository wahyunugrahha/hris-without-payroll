@if (isset($cabang))
    <form action="{{ route('cabang.update', ['kode_cabang' => $cabang->kode_cabang]) }}" method="POST"
        id="formCabangEdit" class="modal-form-body">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="form-grid">
                <div>
                    <label class="form-label" for="kode_cabang_edit">Kode cabang</label>
                    <input type="text" id="kode_cabang_edit" value="{{ $cabang->kode_cabang }}" class="form-control"
                        name="kode_cabang_edit" maxlength="8">
                </div>
                <div>
                    <label class="form-label required" for="nama_cabang_edit">Nama cabang</label>
                    <input type="text" id="nama_cabang_edit" value="{{ $cabang->nama_cabang }}" class="form-control"
                        name="nama_cabang_edit" data-field="Nama Cabang">
                </div>
                <div class="form-grid-full">
                    <label class="form-label required" for="lokasi_kantor_edit">Lokasi kantor</label>
                    <input type="text" id="lokasi_kantor_edit" value="{{ $cabang->lokasi_kantor }}" class="form-control"
                        name="lokasi_kantor_edit" data-field="Lokasi Kantor">
                    <div class="form-hint">Koordinat latitude,longitude.</div>
                </div>
                <div>
                    <label class="form-label required" for="radius_edit">Radius absen</label>
                    <div class="input-group">
                        <input type="text" id="radius_edit" value="{{ $cabang->radius }}" class="form-control"
                            name="radius_edit" inputmode="numeric" data-field="Radius" onkeypress="onlyNumberInput(event)">
                        <span class="input-group-text">meter</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>
@else
    <div class="modal-body">
        <p class="text-danger mb-0">Data cabang tidak ditemukan.</p>
    </div>
@endif
