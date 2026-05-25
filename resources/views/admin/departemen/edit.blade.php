@if (isset($departemen))
    <form action="{{ route('departemen.update', ['kode_dept' => $departemen->kode_dept]) }}" method="POST"
        id="formDepartemenEdit">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    <div class="input-icon mb-3">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-binary-tree"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M6 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M18 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M6 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M18 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M6 8l0 8"></path>
                                <path d="M18 8l0 8"></path>
                            </svg>
                        </span>
                        <input type="text" id="kode_dept_edit_dept" value="{{ $departemen->kode_dept }}"
                            class="form-control" name="kode_dept_edit" placeholder="Kode Departemen (3 Char)" maxlength="3">
                    </div>

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
                        <input type="text" id="nama_dept_edit" value="{{ $departemen->nama_dept }}"
                            class="form-control" name="nama_dept_edit" placeholder="Nama Departemen"
                            data-field="Nama Departemen">
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
        <div class="alert alert-danger">
            Gagal memuat data departemen. Data mungkin tidak ditemukan.
        </div>
    </div>
@endif
