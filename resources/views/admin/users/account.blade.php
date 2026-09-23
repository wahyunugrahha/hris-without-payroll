@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Akun</div>
                    <h2 class="page-title">Profil &amp; Pengaturan Akun</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-xl">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data akun</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('users.account.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep)</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Password (wajib jika ganti password)</label>
                            <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Departemen</label>
                            <input type="text" class="form-control" value="{{ $user->departemen->nama_dept ?? '-' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cabang</label>
                            <input type="text" class="form-control" value="{{ $user->cabang->nama_cabang ?? '-' }}" disabled>
                            <small class="form-hint">Departemen dan cabang hanya bisa diubah oleh super admin melalui Manajemen User.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
