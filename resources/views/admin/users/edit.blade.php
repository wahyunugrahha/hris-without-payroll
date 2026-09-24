@if (isset($user))
    <form action="{{ route('users.update', ['id' => $user->id]) }}" method="POST" id="formUserEdit" class="modal-form-body">
        @csrf
        @method('PUT')
        <div class="modal-body">
            @include('admin.users._fields', ['sufiks' => '_edit'])
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>
@else
    <div class="modal-body">
        <p class="text-danger mb-0">Data user tidak ditemukan.</p>
    </div>
@endif
