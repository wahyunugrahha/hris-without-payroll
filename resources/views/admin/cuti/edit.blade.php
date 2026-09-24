@if (isset($cuti))
    <form action="{{ route('cuti.update', ['kode_cuti' => $cuti->kode_cuti]) }}" method="POST" id="formCutiEdit"
        class="modal-form-body">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="form-grid">
                <div>
                    <label class="form-label" for="kode_cuti_edit">Kode cuti</label>
                    <input type="text" id="kode_cuti_edit" value="{{ $cuti->kode_cuti }}" class="form-control"
                        name="kode_cuti_edit" maxlength="3">
                </div>
                <div>
                    <label class="form-label required" for="jml_hari_edit">Jatah</label>
                    <div class="input-group">
                        <input type="text" id="jml_hari_edit" value="{{ $cuti->jml_hari }}" class="form-control"
                            name="jml_hari_edit" inputmode="numeric" data-field="Jumlah Hari">
                        <span class="input-group-text">hari</span>
                    </div>
                </div>
                <div class="form-grid-full">
                    <label class="form-label required" for="nama_cuti_edit">Nama cuti</label>
                    <input type="text" id="nama_cuti_edit" value="{{ $cuti->nama_cuti }}" class="form-control"
                        name="nama_cuti_edit" data-field="Nama Cuti">
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
        <p class="text-danger mb-0">Data cuti tidak ditemukan.</p>
    </div>
@endif
