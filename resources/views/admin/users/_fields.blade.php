{{-- Isian user admin untuk tambah ($user null, $sufiks '') dan edit ($sufiks '_edit'). --}}
<fieldset class="form-section">
    <legend class="form-section-title">Akun</legend>
    <div class="form-grid">
        <div>
            <label class="form-label required" for="name{{ $sufiks }}">Nama</label>
            <input type="text" id="name{{ $sufiks }}" name="name" class="form-control"
                value="{{ $user ? $user->name : old('name') }}" placeholder="Nama lengkap" autocomplete="off" data-field="Nama User">
        </div>
        <div>
            <label class="form-label required" for="email{{ $sufiks }}">Email</label>
            <input type="email" id="email{{ $sufiks }}" name="email" class="form-control"
                value="{{ $user ? $user->email : old('email') }}" placeholder="nama@perusahaan.com" autocomplete="off" data-field="Email">
        </div>
        <div class="form-grid-full">
            <label class="form-label {{ $user ? '' : 'required' }}" for="password{{ $sufiks }}">{{ $user ? 'Password baru' : 'Password' }}</label>
            <input type="password" id="password{{ $sufiks }}" name="password" class="form-control" autocomplete="new-password"
                placeholder="{{ $user ? 'Kosongkan bila tidak diganti' : 'Minimal 8 karakter' }}" data-field="Password">
        </div>
    </div>
</fieldset>
<fieldset class="form-section">
    <legend class="form-section-title">Penempatan</legend>
    <div class="form-grid">
        <div>
            <label class="form-label required" for="jabatan_id{{ $sufiks }}">Jabatan</label>
            <select name="jabatan_id" id="jabatan_id{{ $sufiks }}" class="form-select">
                <option value="">Pilih jabatan</option>
                @foreach ($jabatan as $j)
                    <option value="{{ $j->id }}" @selected(($user->jabatan_id ?? old('jabatan_id')) == $j->id)>{{ $j->nama_jabatan }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label required" for="kode_dept{{ $sufiks }}">Departemen</label>
            <select name="kode_dept" id="kode_dept{{ $sufiks }}" class="form-select">
                <option value="">Pilih departemen</option>
                @foreach ($departemen as $d)
                    <option value="{{ $d->kode_dept }}" @selected(($user->kode_dept ?? old('kode_dept')) == $d->kode_dept)>{{ $d->nama_dept }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-grid-full">
            <label class="form-label" for="kode_cabang{{ $sufiks }}">Cabang</label>
            <select name="kode_cabang" id="kode_cabang{{ $sufiks }}" class="form-select">
                <option value="">Semua cabang</option>
                @foreach ($cabang as $c)
                    <option value="{{ $c->kode_cabang }}" @selected(($user->kode_cabang ?? old('kode_cabang')) == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                @endforeach
            </select>
            <div class="form-hint">Isi hanya untuk admin cabang; datanya dibatasi ke cabang ini.</div>
        </div>
    </div>
</fieldset>
