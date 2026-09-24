@php
    $jam = fn ($t) => $t ? substr($t, 0, 5) : '';
@endphp
<form action="{{ route('konfigurasi.updatejamkerja', ['kode_jam_kerja' => $jam_kerja->kode_jam_kerja]) }}" method="POST"
    id="formJamKerjaEdit" class="modal-form-body">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <fieldset class="form-section">
            <legend class="form-section-title">Identitas</legend>
            <div class="form-grid">
                <div>
                    <label class="form-label" for="kode_jam_kerja_edit">Kode</label>
                    <input type="text" id="kode_jam_kerja_edit" value="{{ $jam_kerja->kode_jam_kerja }}" class="form-control"
                        name="kode_jam_kerja_edit" maxlength="4">
                </div>
                <div>
                    <label class="form-label required" for="nama_jam_kerja_edit">Nama</label>
                    <input type="text" id="nama_jam_kerja_edit" value="{{ $jam_kerja->nama_jam_kerja }}" class="form-control"
                        name="nama_jam_kerja" data-field="Nama Jam Kerja">
                </div>
            </div>
        </fieldset>
        <fieldset class="form-section">
            <legend class="form-section-title">Waktu</legend>
            <div class="form-grid">
                <div>
                    <label class="form-label required" for="awal_jam_masuk_edit">Awal absen masuk</label>
                    <input type="time" id="awal_jam_masuk_edit" value="{{ $jam($jam_kerja->awal_jam_masuk) }}" class="form-control"
                        name="awal_jam_masuk" data-field="Awal Jam Masuk">
                </div>
                <div>
                    <label class="form-label required" for="akhir_jam_masuk_edit">Batas akhir masuk</label>
                    <input type="time" id="akhir_jam_masuk_edit" value="{{ $jam($jam_kerja->akhir_jam_masuk) }}" class="form-control"
                        name="akhir_jam_masuk" data-field="Akhir Jam Masuk">
                </div>
                <div>
                    <label class="form-label required" for="jam_masuk_edit">Jam masuk</label>
                    <input type="time" id="jam_masuk_edit" value="{{ $jam($jam_kerja->jam_masuk) }}" class="form-control"
                        name="jam_masuk" data-field="Jam Masuk">
                </div>
                <div>
                    <label class="form-label required" for="jam_pulang_edit">Jam pulang</label>
                    <input type="time" id="jam_pulang_edit" value="{{ $jam($jam_kerja->jam_pulang) }}" class="form-control"
                        name="jam_pulang" data-field="Jam Pulang">
                </div>
                <div class="form-grid-full">
                    <label class="form-label required" for="lintashari_edit">Tipe</label>
                    <select name="lintashari" id="lintashari_edit" class="form-select" data-field="Lintas Hari">
                        <option value="0" @selected($jam_kerja->lintashari == 0)>Normal (satu hari)</option>
                        <option value="1" @selected($jam_kerja->lintashari == 1)>Lintas hari</option>
                    </select>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
    </div>
</form>
