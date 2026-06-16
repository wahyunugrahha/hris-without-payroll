<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login Admin | Sistem Absensi wndev</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-main.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/180x180.png') }}">
    <link rel="manifest" href="{{ asset('__manifest.json') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-flags.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-payments.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-social.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css" />

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-primary: #2fb344;
            --tblr-primary-rgb: 47, 179, 68;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .login-card {
            border-radius: 1rem;
        }

        .login-logo img {
            width: min(100%, 280px);
            max-width: 280px;
        }

        .login-bg {
            position: relative;
        }

        .login-bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.25));
        }

        .login-bg-inner {
            position: relative;
            z-index: 1;
        }
    </style>
</head>

<body class="d-flex flex-column bg-body">
    <div class="page page-center">
        <div class="row g-0 flex-fill">
            {{-- Left: form --}}
            <div class="col-12 col-lg-6 col-xl-4 d-flex flex-column justify-content-center bg-white">
                <div class="container container-tight my-5 px-lg-5">

                    {{-- Logo --}}
                    <div class="text-center mb-4 login-logo">
                        <a href="{{ url('/') }}" aria-label="wndev"
                            class="navbar-brand navbar-brand-autodark d-inline-flex align-items-center justify-content-center">
                            <x-brand-logo variant="square" alt="wndev" class="img-fluid d-block mx-auto" />
                        </a>
                    </div>

                    <div class="card shadow-sm login-card border-0">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="h3 text-center mb-2">Login Administrator</h2>

                            {{-- Flash warning --}}
                            @if (Session::get('warning'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <div class="d-flex">
                                        <div>
                                            {{ Session::get('warning') }}
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('loginadmin.process') }}" method="post" autocomplete="off"
                                novalidate>
                                @csrf

                                {{-- Email --}}
                                <div class="mb-3">
                                    <label class="form-label">Email address</label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="you@example.com" autocomplete="off" value="{{ old('email') }}"
                                        autofocus required>
                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div class="mb-2">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Password</span>
                                        <span class="form-label-description">
                                            <a href="#" id="forgot-password-link"
                                                class="text-decoration-none small">I forgot password</a>
                                        </span>
                                    </label>
                                    <div class="input-group input-group-flat">
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Your password" autocomplete="off" required>
                                        <span class="input-group-text">
                                            <a href="#" class="link-secondary text-decoration-none"
                                                title="Show password" data-bs-toggle="password">
                                                {{-- Tabler eye icon --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20"
                                                    height="20" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                    <path
                                                        d="M21 12c-2.4 3.333 -5.4 5 -9 5c-3.6 0 -6.6 -1.667 -9 -5c2.4 -3.333 5.4 -5 9 -5c3.6 0 6.6 1.667 9 5" />
                                                </svg>
                                            </a>
                                        </span>
                                        @error('password')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Remember me --}}
                                <div class="mb-3">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" name="remember">
                                        <span class="form-check-label">Remember me on this device</span>
                                    </label>
                                </div>

                                {{-- Submit --}}
                                <div class="form-footer mt-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20"
                                            height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l5 5l10 -10" />
                                        </svg>
                                        Sign in
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="card-footer text-center text-secondary small">
                            &copy; {{ date('Y') }} wndev. All rights reserved.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: image --}}
            <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
                <div class="login-bg bg-cover h-100 min-vh-100"
                    style="background-image: url('{{ asset('assets/img/login-bg.jpg') }}'); background-position:center; background-size:cover;">
                    <div class="login-bg-inner h-100 d-flex flex-column justify-content-between p-4 p-xl-5 text-white">
                        <div class="d-flex justify-content-end">
                            <span class="badge bg-success text-uppercase">
                                Admin Panel
                            </span>
                        </div>
                        <div>
                            <h1 class="display-6 fw-semibold mb-3">
                                Sistem Absensi & Monitoring Karyawan
                            </h1>
                            <p class="lead mb-4">
                                Kelola kehadiran, izin, dan laporan karyawan dengan lebih mudah dan terpusat.
                            </p>
                        </div>
                        <div class="small text-white-50">
                            WN Developer
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-bs-toggle="password"]').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    var wrapper = this.closest('.input-group');
                    if (!wrapper) return;
                    var input = wrapper.querySelector('input[type="password"], input[type="text"]');
                    if (!input) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                });
            });

            var forgotLink = document.getElementById('forgot-password-link');
            if (forgotLink) {
                forgotLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Lupa Password?',
                        text: 'Silahkan hubungi tim IT untuk reset password akun Anda.',
                        icon: 'info',
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: '#2fb344'
                    });
                });
            }

            var loginForm = document.querySelector('form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    var emailInput = document.querySelector('input[name="email"]');
                    var passwordInput = document.querySelector('input[name="password"]');

                    var emailVal = emailInput.value.trim();
                    var passwordVal = passwordInput.value.trim();

                    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!emailVal) {
                        e.preventDefault();
                        Swal.fire('Peringatan!', 'Email tidak boleh kosong.', 'warning');
                        emailInput.focus();
                        return false;
                    }

                    if (!emailPattern.test(emailVal)) {
                        e.preventDefault();
                        Swal.fire('Peringatan!', 'Format email tidak valid.', 'warning');
                        emailInput.focus();
                        return false;
                    }

                    if (!passwordVal) {
                        e.preventDefault();
                        Swal.fire('Peringatan!', 'Password tidak boleh kosong.', 'warning');
                        passwordInput.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>

</html>
