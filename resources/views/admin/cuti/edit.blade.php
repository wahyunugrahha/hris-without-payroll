@if (isset($cuti))
    {{-- Menggunakan kode_cuti sebagai parameter rute update --}}
    <form action="{{ route('cuti.update', ['kode_cuti' => $cuti->kode_cuti]) }}" method="POST" id="formCutiEdit">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            {{-- Icon untuk Kode Cuti --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-code"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M7 8l-4 4l4 4"></path>
                                <path d="M17 8l4 4l-4 4"></path>
                                <path d="M14 4l-4 16"></path>
                            </svg>
                        </span>
                        {{-- Field Kode Cuti (Readonly) --}}
                        <input type="text" id="kode_cuti_edit" value="{{ $cuti->kode_cuti }}" class="form-control"
                            name="kode_cuti_edit" placeholder="Kode Cuti (3 Char)" maxlength="3">
                    </div>

                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            {{-- Icon untuk Nama Cuti --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-time"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4">
                                </path>
                                <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                <path d="M3 10h18"></path>
                                <path d="M16 3v4"></path>
                                <path d="M8 3v4"></path>
                                <path d="M21 17v2l1 1"></path>
                            </svg>
                        </span>
                        {{-- Field Nama Cuti --}}
                        <input type="text" id="nama_cuti_edit" value="{{ $cuti->nama_cuti }}" class="form-control"
                            name="nama_cuti_edit" placeholder="Nama Cuti" data-field="Nama Cuti">
                    </div>

                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            {{-- Icon untuk Jumlah Hari --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-number"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 17v-10l5 10v-10" />
                                <path d="M14 17h6" />
                                <path d="M18 5l2 0" />
                            </svg>
                        </span>
                        {{-- Field Jumlah Hari --}}
                        <input type="text" id="jml_hari_edit" value="{{ $cuti->jml_hari }}" class="form-control"
                            name="jml_hari_edit" placeholder="Jumlah Hari Cuti" data-field="Jumlah Hari"
                            onkeypress="onlyNumberInput(event)">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary w-100">
                            {{-- Icon Save --}}
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
        <div class="alert alert-danger">
            Gagal memuat data cuti. Data mungkin tidak ditemukan.
        </div>
    </div>
@endif
