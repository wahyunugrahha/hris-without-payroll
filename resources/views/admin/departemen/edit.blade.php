@if (isset($departemen))
    <form action="{{ route('departemen.update', ['kode_dept' => $departemen->kode_dept]) }}" method="POST"
        id="formDepartemenEdit" class="modal-form-body">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label" for="kode_dept_edit_dept">Kode departemen</label>
                <input type="text" id="kode_dept_edit_dept" value="{{ $departemen->kode_dept }}" class="form-control"
                    name="kode_dept_edit" maxlength="3">
            </div>
            <div>
                <label class="form-label required" for="nama_dept_edit">Nama departemen</label>
                <input type="text" id="nama_dept_edit" value="{{ $departemen->nama_dept }}" class="form-control"
                    name="nama_dept_edit" data-field="Nama Departemen">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>
@else
    <div class="modal-body">
        <p class="text-danger mb-0">Data departemen tidak ditemukan.</p>
    </div>
@endif
