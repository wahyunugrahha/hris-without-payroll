@extends('layouts.admin.tabler')

@php
    $inisial = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
    $peran = $user->getRoleNames()->implode(', ');
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Akun</div>
                    <h2 class="page-title">Profil & Pengaturan Akun</h2>
                    <p class="page-subtitle">Ubah nama, email, atau password akun admin Anda.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-3">
                <div class="col-lg-4">
                    <section class="card" aria-label="Ringkasan akun">
                        <div class="card-body text-center">
                            <span class="avatar avatar-xl mb-3">{{ $inisial ?: '?' }}</span>
                            <h3 class="mb-0">{{ $user->name }}</h3>
                            <p class="text-secondary mb-0">{{ $user->email }}</p>
                        </div>
                        <dl class="info-grid info-grid--single border-top p-3 m-0">
                            <div><dt>Peran</dt><dd>{{ $peran ?: '-' }}</dd></div>
                            <div><dt>Departemen</dt><dd>{{ $user->departemen->nama_dept ?? '-' }}</dd></div>
                            <div><dt>Cabang</dt><dd>{{ $user->cabang->nama_cabang ?? '-' }}</dd></div>
                        </dl>
                        <div class="card-footer text-secondary small">
                            Departemen, cabang, dan peran hanya bisa diubah super admin lewat Manajemen User.
                        </div>
                    </section>
                </div>

                <div class="col-lg-8">
                    <form action="{{ route('users.account.update') }}" method="POST" class="card">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <fieldset class="form-section">
                                <legend class="form-section-title">Profil</legend>
                                <div class="form-grid">
                                    <div>
                                        <label class="form-label required" for="akun-nama">Nama</label>
                                        <input type="text" name="name" id="akun-nama" class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" autocomplete="name" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label required" for="akun-email">Email</label>
                                        <input type="email" name="email" id="akun-email" class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" autocomplete="email" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="form-section">
                                <legend class="form-section-title">Ganti password</legend>
                                <p class="text-secondary small mb-3">Kosongkan bila tidak ingin mengganti password.</p>
                                <div class="form-grid">
                                    <div class="form-grid-full">
                                        <label class="form-label" for="akun-password-lama">Password saat ini</label>
                                        <input type="password" name="current_password" id="akun-password-lama"
                                            class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                                        <div class="form-hint">Wajib diisi bila mengganti password.</div>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="akun-password">Password baru</label>
                                        <input type="password" name="password" id="akun-password"
                                            class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="akun-password-ulang">Ulangi password baru</label>
                                        <input type="password" name="password_confirmation" id="akun-password-ulang" class="form-control" autocomplete="new-password">
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
