<form action="{{ route('karyawan.update', $karyawan->nik) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @if ($errors->any())
        <div class="alert alert-danger m-3">
            <div class="d-flex">
                <div class="me-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-circle"
                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                        <path d="M12 8v4" />
                        <path d="M12 16h.01" />
                    </svg>
                </div>
                <div>
                    <h4 class="alert-title">Gagal Disimpan!</h4>
                    <ul class="text-muted mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    {{-- Header Tabs --}}
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item">
                <a href="#tabs-pribadi" class="nav-link active" data-bs-toggle="tab">Data Pribadi</a>
            </li>
            <li class="nav-item">
                <a href="#tabs-kepegawaian" class="nav-link" data-bs-toggle="tab">Kepegawaian</a>
            </li>
            <li class="nav-item">
                <a href="#tabs-legal" class="nav-link" data-bs-toggle="tab">Legal & Keluarga</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">
            {{-- TAB 1: DATA PRIBADI --}}
            <div class="tab-pane active show" id="tabs-pribadi">
                <div class="row">
                    <div class="col-lg-4 text-center">
                        <label class="form-label">Foto Profil</label>
                        @php
                            $fotoPath = !empty($karyawan->foto)
                                ? asset('storage/uploads/karyawan/' . $karyawan->foto)
                                : asset('assets/img/nophoto.png');
                        @endphp
                        <div class="mb-3">
                            <span class="avatar avatar-xl rounded"
                                style="background-image: url({{ $fotoPath }})"></span>
                        </div>
                        <input type="file" name="foto" class="form-control form-control-sm mb-3">

                        <div class="text-start">
                            <div class="mb-3">
                                <label class="form-label required">NIK (Login)</label>
                                <input type="text" name="nik" value="{{ $karyawan->nik }}"
                                    class="form-control font-weight-bold">
                                <small class="form-hint text-warning">NIK ini terhubung ke data Absensi &
                                    Lembur.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Isi jika ingin ubah password">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label required">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="{{ $karyawan->nama_lengkap }}"
                                    class="form-control font-weight-bold" required>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label required">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan" value="{{ $karyawan->nama_panggilan }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select">
                                    <option value="L" {{ $karyawan->jenis_kelamin == 'L' ? 'selected' : '' }}>
                                        Laki-laki</option>
                                    <option value="P" {{ $karyawan->jenis_kelamin == 'P' ? 'selected' : '' }}>
                                        Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" value="{{ $karyawan->tempat_lahir }}"
                                    class="form-control">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir"
                                    value="{{ optional($karyawan->tanggal_lahir)->format('Y-m-d') }}"
                                    class="form-control">
                            </div>

                            <div class="col-6 mb-2">
                                <label class="form-label">Agama</label>
                                <select name="agama" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $agm)
                                        <option value="{{ $agm }}"
                                            {{ $karyawan->agama == $agm ? 'selected' : '' }}>
                                            {{ $agm }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 mb-2">
                                <label class="form-label">Status Pernikahan</label>
                                <select name="status_pernikahan" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $sp)
                                        <option value="{{ $sp }}"
                                            {{ $karyawan->status_pernikahan == $sp ? 'selected' : '' }}>
                                            {{ $sp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Status PTKP</label>
                                <select name="status_ptkp" class="form-select">
                                    <option value="TK" {{ $karyawan->status_ptkp == 'TK' ? 'selected' : '' }}>Tak
                                        Kawin (TK)
                                    </option>
                                    @for ($i = 0; $i <= 10; $i++)
                                        <option value="K/{{ $i }}"
                                            {{ $karyawan->status_ptkp == "K/$i" ? 'selected' : '' }}>
                                            Kawin, {{ $i }} Tanggungan (K/{{ $i }})
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label required">No HP</label>
                                <input type="text" name="no_hp" value="{{ $karyawan->no_hp }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ $karyawan->email }}"
                                    class="form-control">
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label">Alamat Domisili</label>
                                <textarea name="alamat" class="form-control" rows="2">{{ $karyawan->alamat }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: KEPEGAWAIAN --}}
            <div class="tab-pane" id="tabs-kepegawaian">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label required">Jabatan</label>
                        <select name="jabatan_id" class="form-select" required>
                            <option value="">Pilih Jabatan</option>
                            @foreach ($jabatans as $j)
                                <option value="{{ $j->id }}"
                                    {{ $karyawan->jabatan_id == $j->id ? 'selected' : '' }}>
                                    {{ $j->nama_jabatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 mb-3"><label class="form-label required">Status Karyawan</label><select
                            name="status_karyawan" class="form-select">
                            <option value="">Pilih Status Karyawan</option>
                            <option value="PKWT" {{ $karyawan->status_karyawan == 'PKWT' ? 'selected' : '' }}>PKWT
                            </option>
                            <option value="Tetap" {{ $karyawan->status_karyawan == 'Tetap' ? 'selected' : '' }}>Tetap
                            </option>
                            <option value="Harian" {{ $karyawan->status_karyawan == 'Harian' ? 'selected' : '' }}>
                                Harian</option>
                        </select></div>
                    <div class="col-6 mb-3"><label class="form-label required">Departemen</label><select
                            name="kode_dept" class="form-select" required>
                            <option value="">Pilih Departemen</option>
                            @foreach ($departemen as $d)
                                <option value="{{ $d->kode_dept }}"
                                    {{ $karyawan->kode_dept == $d->kode_dept ? 'selected' : '' }}>{{ $d->nama_dept }}
                                </option>
                            @endforeach
                        </select></div>
                    <div class="col-6 mb-3"><label class="form-label required">PT / Cabang</label><select
                            name="kode_cabang" class="form-select" required>
                            <option value="">Pilih PT / Cabang</option>
                            @foreach ($cabang as $c)
                                <option value="{{ $c->kode_cabang }}"
                                    {{ $karyawan->kode_cabang == $c->kode_cabang ? 'selected' : '' }}>
                                    {{ $c->nama_cabang }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-6 mb-3"><label class="form-label">TMT (Join Date)</label><input type="date"
                            name="tmt" value="{{ optional($karyawan->tmt)->format('Y-m-d') }}"
                            class="form-control"></div>
                    <div class="col-6 mb-3"><label class="form-label">Awal Kontrak</label><input type="date"
                            name="tanggal_awal_kontrak"
                            value="{{ optional($karyawan->tanggal_awal_kontrak)->format('Y-m-d') }}"
                            class="form-control"></div>
                    <div class="col-6 mb-3"><label class="form-label">Status Aktif</label><select name="status_aktif"
                            class="form-select">
                            <option value="">Pilih Status Aktif</option>
                            <option value="Aktif" {{ $karyawan->status_aktif == 'Aktif' ? 'selected' : '' }}>Aktif
                            </option>
                            <option value="Nonaktif" {{ $karyawan->status_aktif == 'Nonaktif' ? 'selected' : '' }}>
                                Nonaktif</option>
                            <option value="Diberhentikan"
                                {{ $karyawan->status_aktif == 'Diberhentikan' ? 'selected' : '' }}>
                                Diberhentikan</option>
                            <option value="Menunggu Approval"
                                {{ $karyawan->status_aktif == 'Menunggu Approval' ? 'selected' : '' }}>
                                Menunggu Approval</option>
                        </select></div>
                    <div class="col-6 mb-3"><label class="form-label text-danger">Akhir Kontrak</label><input
                            type="date" name="tanggal_habis_kontrak"
                            value="{{ optional($karyawan->tanggal_habis_kontrak)->format('Y-m-d') }}"
                            class="form-control"></div>
                    <div class="col-6 mb-3"><label class="form-label">Tanggal Keluar</label><input type="date"
                            name="tanggal_keluar" value="{{ optional($karyawan->tanggal_keluar)->format('Y-m-d') }}"
                            class="form-control">
                        <small class="text-muted">Wajib diisi jika status karyawan Nonaktif atau Diberhentikan.</small>
                    </div>
                    <div class="col-12 mb-3"><label class="form-label">Histori Karyawan</label>
                        <textarea name="history_karyawan" class="form-control" rows="2"
                            placeholder="Contoh: Perpanjang Kontrak, Resign, Habis Kontrak, dll...">{{ $karyawan->history_karyawan }}</textarea>
                    </div>
                    <div class="col-12 mb-3"><label class="form-label">Keterangan Tambahan / Statement</label>
                        <textarea name="statemen" class="form-control" rows="2" placeholder="Contoh: Mengembalikan APD, dll...">{{ $karyawan->statemen }}</textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_whitelist" value="1"
                                {{ old('is_whitelist', (int) $karyawan->is_whitelist) ? 'checked' : '' }}>
                            <span class="form-check-label">Whitelist (karyawan dikecualikan dari presensi
                                harian)</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- TAB 3: LEGAL --}}
            <div class="tab-pane" id="tabs-legal">
                <div class="row">
                    {{-- 1. Dasar --}}
                    <div class="col-6 mb-3">
                        <label class="form-label">No Rekening</label>
                        <input type="text" name="no_rekening" value="{{ $karyawan->no_rekening }}"
                            class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="pendidikan_terakhir" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach (['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2'] as $p)
                                <option value="{{ $p }}"
                                    {{ $karyawan->pendidikan_terakhir == $p ? 'selected' : '' }}>{{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Nama Ibu Kandung</label>
                        <input type="text" name="nama_ibu_kandung" value="{{ $karyawan->nama_ibu_kandung }}"
                            class="form-control">
                    </div>

                    {{-- 2. Kontak Darurat --}}
                    <div class="col-12 my-2">
                        <div class="hr-text text-red">Data Darurat</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama Kontak Darurat</label>
                        <input type="text" name="nama_darurat" value="{{ $karyawan->nama_darurat }}"
                            class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hubungan</label>
                        <input type="text" name="hubungan_darurat" value="{{ $karyawan->hubungan_darurat }}"
                            class="form-control" placeholder="Cth: Istri">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">No HP Darurat</label>
                        <input type="text" name="no_darurat" value="{{ $karyawan->no_darurat }}"
                            class="form-control">
                    </div>

                    {{-- 3. BPJS --}}
                    <div class="col-12 my-2">
                        <div class="hr-text text-blue">Dokumen BPJS</div>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">BPJS Kesehatan</label>
                        <input type="text" name="no_bpjs_kesehatan" value="{{ $karyawan->no_bpjs_kesehatan }}"
                            class="form-control mb-2">
                        <input type="file" name="foto_bpjs_kesehatan" class="form-control form-control-sm">
                        @if ($karyawan->foto_bpjs_kesehatan)
                            <small class="text-success mt-1 d-block"><i class="ti ti-check"></i> Sudah ada
                                file</small>
                        @endif
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">BPJS Ketenagakerjaan</label>
                        <input type="text" name="no_bpjs_ketenagakerjaan"
                            value="{{ $karyawan->no_bpjs_ketenagakerjaan }}" class="form-control mb-2">
                        <input type="file" name="foto_bpjs_ketenagakerjaan" class="form-control form-control-sm">
                        @if ($karyawan->foto_bpjs_ketenagakerjaan)
                            <small class="text-success mt-1 d-block"><i class="ti ti-check"></i> Sudah ada
                                file</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary ms-auto">Simpan Perubahan</button>
    </div>
</form>
