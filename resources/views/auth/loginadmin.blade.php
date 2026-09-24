<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login Admin | Sistem Absensi wndev</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="manifest" href="{{ asset('__manifest.json') }}?v=20260618">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin-tokens.css') }}" />
    <script>
        // Halaman yang dipulihkan dari back-forward cache bisa berisi tampilan/data lama: muat ulang.
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                location.reload();
            }
        });
    </script>

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --login-scrim: oklch(18% 0.03 255 / 0.62);
            --login-on-photo: oklch(99% 0 0);
            --login-on-photo-muted: oklch(99% 0 0 / 0.8);
            --login-on-photo-rule: oklch(99% 0 0 / 0.4);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--color-paper);
            color: var(--color-ink-2);
            overflow-x: clip;
        }

        .login-panel {
            background: var(--color-paper);
        }

        .login-logo img {
            width: min(100%, 200px);
        }

        .login-card {
            border: 1px solid var(--color-rule);
            border-radius: var(--radius-card);
            background: var(--color-surface);
            box-shadow: var(--shadow-lift);
        }

        .login-card .card-footer {
            background: var(--color-surface-2);
            border-color: var(--color-rule);
        }

        .login-title {
            color: var(--color-ink);
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            overflow-wrap: anywhere;
        }

        .form-control,
        .input-group-text {
            border-color: var(--color-rule-2);
            border-radius: var(--radius-control);
        }

        .form-control:focus {
            border-color: var(--color-focus);
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-focus) 20%, transparent);
        }

        .password-toggle {
            border: 0;
            background: transparent;
            color: var(--color-muted);
            padding: 0;
            line-height: 0;
        }

        .password-toggle:hover {
            color: var(--color-ink);
        }

        .btn-primary {
            border-radius: var(--radius-control);
            font-weight: 500;
        }

        :is(.btn, a, .password-toggle, .form-check-input):focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
            box-shadow: none;
        }

        .login-hero {
            position: relative;
            background-position: center;
            background-size: cover;
        }

        /* Scrim datar agar teks putih terbaca di atas foto. */
        .login-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--login-scrim);
        }

        .login-hero-inner {
            position: relative;
            z-index: 1;
            color: var(--login-on-photo);
        }

        .login-hero-tag {
            border: 1px solid var(--login-on-photo-rule);
            border-radius: var(--radius-control);
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .login-hero-title {
            font-size: clamp(1.75rem, 2.4vw + 0.5rem, 2.75rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.15;
            max-width: 18ch;
            overflow-wrap: anywhere;
            min-width: 0;
        }

        .login-hero-lede {
            max-width: 46ch;
            color: var(--login-on-photo-muted);
        }
    </style>
</head>

<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="row g-0 flex-fill">
            {{-- Kiri: formulir --}}
            <div class="col-12 col-lg-6 col-xl-4 d-flex flex-column justify-content-center login-panel">
                <div class="container container-tight my-5 px-lg-5">

                    <div class="text-center mb-4 login-logo">
                        <a href="{{ url('/') }}" aria-label="wndev" class="d-inline-flex">
                            <x-brand-logo variant="square" alt="wndev" class="img-fluid d-block mx-auto" />
                        </a>
                    </div>

                    <div class="card login-card">
                        <div class="card-body p-4 p-md-5">
                            <h1 class="login-title text-center mb-1">Masuk ke Panel Admin</h1>
                            <p class="text-secondary text-center small mb-4">Gunakan akun administrator Anda.</p>

                            @if (Session::get('warning'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ Session::get('warning') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Tutup"></button>
                                </div>
                            @endif

                            <form action="{{ route('loginadmin.process') }}" method="post" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" name="email" id="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="nama@perusahaan.com" autocomplete="username"
                                        value="{{ old('email') }}" autofocus required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0" for="password">Kata sandi</label>
                                        <a href="#" id="forgot-password-link" class="small text-decoration-none">
                                            Lupa kata sandi?
                                        </a>
                                    </div>
                                    <div class="input-group input-group-flat">
                                        <input type="password" name="password" id="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Kata sandi" autocomplete="current-password" required>
                                        <span class="input-group-text">
                                            <button type="button" class="password-toggle" data-password-toggle
                                                aria-controls="password" aria-pressed="false"
                                                aria-label="Tampilkan kata sandi">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20"
                                                    height="20" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round" aria-hidden="true">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                    <path
                                                        d="M21 12c-2.4 3.333 -5.4 5 -9 5c-3.6 0 -6.6 -1.667 -9 -5c2.4 -3.333 5.4 -5 9 -5c3.6 0 6.6 1.667 9 5" />
                                                </svg>
                                            </button>
                                        </span>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" name="remember">
                                        <span class="form-check-label">Ingat saya di perangkat ini</span>
                                    </label>
                                </div>

                                <div class="form-footer mt-3">
                                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                                </div>
                            </form>
                        </div>

                        <div class="card-footer text-center text-secondary small">
                            &copy; {{ date('Y') }} wndev
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: gambar --}}
            <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
                <div class="login-hero h-100 min-vh-100"
                    style="background-image: url('{{ asset('assets/img/login-bg.jpg') }}');">
                    <div class="login-hero-inner h-100 d-flex flex-column justify-content-between p-4 p-xl-5">
                        <div class="d-flex justify-content-end">
                            <span class="login-hero-tag">Panel Admin</span>
                        </div>
                        <div>
                            <h2 class="login-hero-title mb-3">Sistem Absensi &amp; Monitoring Karyawan</h2>
                            <p class="login-hero-lede lead mb-0">
                                Kelola kehadiran, izin, dan laporan karyawan dari satu tempat.
                            </p>
                        </div>
                        <div class="small login-hero-lede">WN Developer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    var input = document.getElementById(toggle.getAttribute('aria-controls'));
                    var tampil = input.type === 'password';
                    input.type = tampil ? 'text' : 'password';
                    toggle.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                    toggle.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            });

            document.getElementById('forgot-password-link').addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Lupa kata sandi?',
                    text: 'Hubungi tim IT untuk mengatur ulang kata sandi akun Anda.',
                    icon: 'info',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: 'var(--color-accent)'
                });
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                var email = document.getElementById('email');
                var password = document.getElementById('password');
                var pesan = null;
                var target = null;

                if (!email.value.trim()) {
                    pesan = 'Email wajib diisi.';
                    target = email;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                    pesan = 'Format email tidak valid.';
                    target = email;
                } else if (!password.value.trim()) {
                    pesan = 'Kata sandi wajib diisi.';
                    target = password;
                }

                if (pesan) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Periksa lagi',
                        text: pesan,
                        icon: 'warning',
                        confirmButtonColor: 'var(--color-accent)'
                    });
                    target.focus();
                }
            });
        });
    </script>
</body>

</html>
