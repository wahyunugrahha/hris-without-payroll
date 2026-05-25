<form action="{{ route('konfigurasi.updatejamkerja', ['kode_jam_kerja' => $jam_kerja->kode_jam_kerja]) }}" method="POST"
    id="formJamKerjaEdit">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-12">
            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG Barcode Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-barcode">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M4 7v-1a2 2 0 0 1 2 -2h2" />
                        <path d="M4 17v1a2 2 0 0 0 2 2h2" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v1" />
                        <path d="M16 20h2a2 2 0 0 0 2 -2v-1" />
                        <path d="M5 11h1v2h-1z" />
                        <path d="M10 11l0 2" />
                        <path d="M14 11h1v2h-1z" />
                        <path d="M19 11l0 2" />
                    </svg>
                </span>
                <input type="text" id="kode_jam_kerja_edit" value="{{ $jam_kerja->kode_jam_kerja }}"
                    class="form-control" name="kode_jam_kerja_edit" placeholder="Kode Jam Kerja (4 Char)" maxlength="4">
            </div>

            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG File Text Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                        <path d="M9 11h6" />
                        <path d="M9 15h6" />
                    </svg>
                </span>
                <input type="text" id="nama_jam_kerja_edit" value="{{ $jam_kerja->nama_jam_kerja }}"
                    class="form-control" name="nama_jam_kerja" placeholder="Nama Jam Kerja" data-field="Nama Jam Kerja">
            </div>

            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG Clock Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock-hour-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 12v3.5" />
                        <path d="M12 7v5" />
                    </svg>
                </span>
                <input type="text" id="awal_jam_masuk_edit" value="{{ $jam_kerja->awal_jam_masuk }}"
                    class="form-control" name="awal_jam_masuk" placeholder="Awal Jam Masuk" data-field="Awal Jam Masuk">
            </div>

            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG Clock Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock-hour-3">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 12h3.5" />
                        <path d="M12 7v5" />
                    </svg>
                </span>
                <input type="text" id="jam_masuk_edit" value="{{ $jam_kerja->jam_masuk }}" class="form-control"
                    name="jam_masuk" placeholder="Jam Masuk" data-field="Jam Masuk">
            </div>

            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG Clock Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock-hour-5">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 12v3" />
                        <path d="M12 7v5" />
                        <path d="M15.5 15.5l-3.5 -3.5" />
                    </svg>
                </span>
                <input type="text" id="akhir_jam_masuk_edit" value="{{ $jam_kerja->akhir_jam_masuk }}"
                    class="form-control" name="akhir_jam_masuk" placeholder="Akhir Jam Masuk"
                    data-field="Akhir Jam Masuk">
            </div>

            <div class="input-icon mb-3">
                <span class="input-icon-addon">
                    {{-- SVG Clock Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-clock-hour-9">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 12h-3.5" />
                        <path d="M12 7v5" />
                    </svg>
                </span>
                <input type="text" id="jam_pulang_edit" value="{{ $jam_kerja->jam_pulang }}" class="form-control"
                    name="jam_pulang" placeholder="Jam Pulang" data-field="Jam Pulang">
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <select name="lintashari" id="lintashari_edit" class="form-select" data-field="Lintas Hari">
                        <option value="">Lintas Hari</option>
                        <option value="1" {{ $jam_kerja->lintashari == 1 ? 'selected' : '' }}>Ya</option>
                        <option value="0" {{ $jam_kerja->lintashari == 0 ? 'selected' : '' }}>Tidak</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-refresh-dot">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -5v5h5" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 5v-5h-5" />
                            <path d="M12 9h.01" />
                        </svg>
                        Update Jam Kerja
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
