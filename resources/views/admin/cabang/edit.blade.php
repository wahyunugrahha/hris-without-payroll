@if (isset($cabang))
    {{-- Form action diubah ke route cabang.update dan menggunakan kode_cabang sebagai parameter --}}
    <form action="{{ route('cabang.update', ['kode_cabang' => $cabang->kode_cabang]) }}" method="POST"
        id="formCabangEdit">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    {{-- Kode Cabang (Primary Key): Tidak dapat diubah (readonly) --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                <path
                                    d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z">
                                </path>
                            </svg>
                        </span>
                        {{-- ID field diubah (kode_cabang_edit) dan value diisi dari $cabang --}}
                        <input type="text" id="kode_cabang_edit" value="{{ $cabang->kode_cabang }}"
                            class="form-control" name="kode_cabang_edit" placeholder="Kode Cabang (3-8 Char)" maxlength="8">
                    </div>

                    {{-- Nama Cabang --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="icon icon-tabler icon-tabler-building-factory" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path
                                    d="M12 21h-5a2 2 0 0 1 -2 -2v-10a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v10a2 2 0 0 1 -2 2h-5">
                                </path>
                                <path d="M7 9l0 12"></path>
                                <path d="M17 9l0 12"></path>
                                <path d="M10 10l-2 6"></path>
                                <path d="M16 10l2 6"></path>
                                <path d="M12 7l0 -3"></path>
                                <path d="M15 6l0 2"></path>
                                <path d="M9 6l0 2"></path>
                            </svg>
                        </span>
                        {{-- ID field diubah (nama_cabang_edit) dan value diisi dari $cabang --}}
                        <input type="text" id="nama_cabang_edit" value="{{ $cabang->nama_cabang }}"
                            class="form-control" name="nama_cabang_edit" placeholder="Nama Cabang"
                            data-field="Nama Cabang">
                    </div>

                    {{-- Lokasi Kantor --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="icon icon-tabler icon-tabler-current-location" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0"></path>
                                <path d="M12 12l0 0.01"></path>
                            </svg>
                        </span>
                        <input type="text" id="lokasi_kantor_edit" value="{{ $cabang->lokasi_kantor }}"
                            class="form-control" name="lokasi_kantor_edit" placeholder="Lokasi Kantor (Lat/Long/Alamat)"
                            data-field="Lokasi Kantor">
                    </div>

                    {{-- Radius --}}
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-target-arrow"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                <path d="M8.2 8.2l3.8 -3.8"></path>
                                <path d="M12 21a9 9 0 1 0 0 -18a9 9 0 0 0 0 18z"></path>
                                <path d="M12 3v18"></path>
                                <path d="M3 12h18"></path>
                            </svg>
                        </span>
                        <input type="text" id="radius_edit" value="{{ $cabang->radius }}" class="form-control"
                            name="radius_edit" placeholder="Radius Absensi (meter)" data-field="Radius"
                            onkeypress="onlyNumberInput(event)">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M14 4l0 4l-6 0l0 -4" />
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@else
    <div class="modal-body">
        {{-- Pesan disesuaikan --}}
        <div class="alert alert-danger">
            Gagal memuat data cabang. Data mungkin tidak ditemukan.
        </div>
    </div>
@endif
