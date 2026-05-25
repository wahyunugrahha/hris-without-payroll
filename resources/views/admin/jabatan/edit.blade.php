<form action="{{ route('jabatan.update', $jabatan->id) }}" method="POST" id="formJabatanEdit">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Nama Jabatan</label>
        <input type="text" class="form-control" name="nama_jabatan" id="nama_jabatan_edit"
            value="{{ $jabatan->nama_jabatan }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Panel Akses</label>
        <select name="guard_name" class="form-select" id="guard_select_edit" required>
            {{-- Menentukan guard default berdasarkan relasi role yang ada --}}
            <option value="user" {{ ($jabatan->role->guard_name ?? '') == 'user' ? 'selected' : '' }}>Admin Panel
            </option>
            <option value="karyawan" {{ ($jabatan->role->guard_name ?? '') == 'karyawan' ? 'selected' : '' }}>Karyawan
                (Mobile)</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select" id="role_id_edit" required>
            <option value="">Pilih Role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" data-guard="{{ $role->guard_name }}"
                    {{ $jabatan->role_id == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
</form>

<script>
    $(function() {
        // Fungsi untuk filter role berdasarkan guard saat modal edit terbuka
        function filterRoleEdit() {
            let selectedGuard = $('#guard_select_edit').val();
            $('#role_id_edit option').each(function() {
                let optionGuard = $(this).data('guard');
                if (optionGuard == selectedGuard || $(this).val() == "") {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        // Jalankan filter saat pertama kali form dimuat (untuk menyesuaikan data yang ada)
        filterRoleEdit();

        // Jalankan filter saat dropdown guard diubah
        $('#guard_select_edit').on('change', function() {
            filterRoleEdit();
            $('#role_id_edit').val(""); // Reset pilihan role jika guard diubah
        });
    });
</script>
