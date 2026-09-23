@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manajemen Permission</h2>
                    <div class="text-muted mt-1">Kelola hak akses spesifik pengguna dalam sistem</div>
                </div>
                <div class="col-auto ms-auto d-print-none d-flex gap-2 align-items-center">
                    <div class="d-none d-sm-block" style="min-width:260px;">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                    <path d="M21 21l-6 -6" />
                                </svg>
                            </span>
                            <input type="text" id="permission-search" class="form-control"
                                placeholder="Cari permission...">
                        </div>
                    </div>

                    @can('permissions-create-admin')
                        <button class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                            data-bs-target="#modalCreate">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            Tambah Permission
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">No.</th>
                                <th>Nama Permission</th>
                                <th>Akses Untuk (Guard)</th>
                                <th class="w-1 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permissions as $permission)
                                <tr>
                                    <td><span class="text-muted">{{ $loop->iteration }}</span></td>
                                    <td><span class="fw-bold text-dark">{{ $permission->name }}</span></td>
                                    <td>
                                        <span
                                            class="fw-medium {{ $permission->guard_name == 'user' ? 'text-blue' : 'text-green' }}">
                                            {{ strtoupper($permission->guard_name == 'user' ? 'Admin Panel' : 'Karyawan App') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap justify-content-center">
                                            @can('permissions-edit-admin')
                                                <button class="btn btn-ghost-primary btn-icon" data-bs-toggle="modal"
                                                    data-bs-target="#edit{{ $permission->id }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-pencil" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                        <path d="M13.5 6.5l4 4" />
                                                    </svg>
                                                </button>
                                            @endcan

                                            @can('permissions-delete-admin')
                                                <button class="btn btn-ghost-danger btn-icon" data-bs-toggle="modal"
                                                    data-bs-target="#delete{{ $permission->id }}" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-trash" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M4 7l16 0" />
                                                        <path d="M10 11l0 6" />
                                                        <path d="M14 11l0 6" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                {{-- MODAL EDIT --}}
                                <div class="modal modal-blur fade" id="edit{{ $permission->id }}" tabindex="-1"
                                    role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <form method="POST"
                                                action="{{ route('users.permissions.update', $permission->id) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Permission</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Permission</label>
                                                        <input type="text" class="form-control" name="name"
                                                            value="{{ $permission->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Akses Untuk (Guard)</label>
                                                        <select name="guard_name" class="form-select" required>
                                                            <option value="user"
                                                                {{ $permission->guard_name == 'user' ? 'selected' : '' }}>
                                                                USER (Admin Panel)</option>
                                                            <option value="karyawan"
                                                                {{ $permission->guard_name == 'karyawan' ? 'selected' : '' }}>
                                                                KARYAWAN</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-link link-secondary me-auto"
                                                        data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan
                                                        Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- MODAL DELETE --}}
                                <div class="modal modal-blur fade" id="delete{{ $permission->id }}" tabindex="-1"
                                    role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                            <div class="modal-status bg-danger"></div>
                                            <div class="modal-body text-center py-4">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon mb-2 text-danger icon-lg" width="24" height="24"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 9v2m0 4v.01" />
                                                    <path
                                                        d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                                                </svg>
                                                <h3>Apakah anda yakin?</h3>
                                                <div class="text-muted">Hapus permission <b>{{ $permission->name }}</b>?
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="w-100">
                                                    <div class="row">
                                                        <div class="col"><a href="#" class="btn w-100"
                                                                data-bs-dismiss="modal">Batal</a></div>
                                                        <div class="col">
                                                            <form
                                                                action="{{ route('users.permissions.destroy', $permission->id) }}"
                                                                method="POST">
                                                                @csrf @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-danger w-100">Hapus</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada data permission.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div class="modal modal-blur fade" id="modalCreate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('users.permissions.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Permission Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Permission</label>
                            <input type="text" class="form-control" name="name" placeholder="fitur-aksi-guard"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Akses Untuk (Guard)</label>
                            <select name="guard_name" class="form-select" required>
                                <option value="user">USER (Admin Panel)</option>
                                <option value="karyawan">KARYAWAN</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('permission-search');
            if (!input) return;
            input.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('.datatable tbody tr').forEach(function(tr) {
                    const name = tr.querySelector('td:nth-child(2)')?.textContent.toLowerCase() ||
                        '';
                    tr.style.display = name.includes(q) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
