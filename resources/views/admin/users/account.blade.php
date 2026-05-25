@extends('layouts.admin.tabler')

@section('content')
<div class="container-xl">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Settings</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('users.account.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep)</label>
                            <input type="password" name="password" class="form-control" placeholder="New password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Departemen</label>
                            <select name="kode_dept" class="form-select" required>
                                @foreach($departemen as $d)
                                    <option value="{{ $d->kode_dept }}" {{ $user->kode_dept == $d->kode_dept ? 'selected' : '' }}>{{ $d->nama_dept }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cabang (optional)</label>
                            <select name="kode_cabang" class="form-select">
                                <option value="">--</option>
                                @foreach($cabang as $c)
                                    <option value="{{ $c->kode_cabang }}" {{ ($user->kode_cabang ?? '') == $c->kode_cabang ? 'selected' : '' }}>{{ $c->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
