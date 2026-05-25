@if (isset($user))
    <form action="{{ route('users.update', ['id' => $user->id]) }}" method="POST" id="formUserEdit">
        @csrf
        @method('PUT')

        <div class="modal-body">
            <div class="row">
                <div class="col-12">

                    {{-- NAMA USER --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M8 7a4 4 0 1 0 8 0"></path>
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                            </svg>
                        </span>
                        <input type="text" id="name_edit" name="name" class="form-control"
                            value="{{ $user->name }}" placeholder="Nama User">
                    </div>

                    {{-- EMAIL --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                <path d="M3 7l9 6l9 -6" />
                            </svg>
                        </span>
                        <input type="email" id="email_edit" name="email" class="form-control"
                            value="{{ $user->email }}" placeholder="Email">
                    </div>

                    {{-- PASSWORD --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                            </svg>
                        </span>
                        <input type="password" id="password_edit" name="password" class="form-control"
                            placeholder="Password Baru (opsional)">
                    </div>

                    {{-- DEPARTEMEN --}}
                    <div class="mb-3">
                        <select name="kode_dept" id="kode_dept_edit" class="form-select">
                            <option value="">Departemen</option>
                            @foreach ($departemen as $d)
                                <option value="{{ $d->kode_dept }}"
                                    {{ $user->kode_dept == $d->kode_dept ? 'selected' : '' }}>
                                    {{ $d->nama_dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CABANG --}}
                    <div class="mb-3">
                        <select name="kode_cabang" id="kode_cabang_edit" class="form-select">
                            <option value="">Cabang (Admin Cabang)</option>
                            @foreach ($cabang as $c)
                                <option value="{{ $c->kode_cabang }}"
                                    {{ $user->kode_cabang == $c->kode_cabang ? 'selected' : '' }}>
                                    {{ $c->nama_cabang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <select name="jabatan_id" id="jabatan_id_edit" class="form-select">
                            <option value="">Pilih Jabatan</option>
                            @foreach ($jabatan as $j)
                                <option value="{{ $j->id }}"
                                    {{ $user->jabatan_id == $j->id ? 'selected' : '' }}>
                                    {{ strtoupper($j->nama_jabatan) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-send">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10 14l11 -11" />
                            <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
@else
    <div class="modal-body">
        <div class="alert alert-danger">
            Gagal memuat data user.
        </div>
    </div>
@endif
