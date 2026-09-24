<form action="{{ route('jabatan.update', $jabatan->id) }}" method="POST" id="formJabatanEdit" class="modal-form-body">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label required" for="nama_jabatan_edit">Nama jabatan</label>
            <input type="text" class="form-control" name="nama_jabatan" id="nama_jabatan_edit"
                value="{{ $jabatan->nama_jabatan }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label required" for="guard_select_edit">Panel akses</label>
            <select name="guard_name" class="form-select" id="guard_select_edit" required>
                <option value="user" @selected(($jabatan->role->guard_name ?? '') == 'user')>Admin Panel</option>
                <option value="karyawan" @selected(($jabatan->role->guard_name ?? '') == 'karyawan')>Karyawan (Mobile)</option>
            </select>
        </div>
        <div>
            <label class="form-label required" for="role_id_edit">Role</label>
            <select name="role_id" class="form-select" id="role_id_edit" required>
                <option value="">Pilih role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}" @selected($jabatan->role_id == $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
    </div>
</form>

<script>
    $(function() {
        // Role yang bisa dipilih mengikuti panel akses.
        function filterRoleEdit() {
            let selectedGuard = $('#guard_select_edit').val();
            $('#role_id_edit option').each(function() {
                $(this).toggle($(this).data('guard') == selectedGuard || $(this).val() == '');
            });
        }

        filterRoleEdit();
        $('#guard_select_edit').on('change', function() {
            filterRoleEdit();
            $('#role_id_edit').val('');
        });
    });
</script>
