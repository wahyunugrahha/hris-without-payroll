<!doctype html>
<html lang="en">

<head>
    <meta name="google-site-verification" content="hYU3pwWeiYF78Ki6m4fvdx3VZaTH3uGxbCFbnJvwuec" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1e74fd">
    <title>Login Karyawan | Sistem Absensi DevHRIS</title>
    <meta name="description" content="Sistem Absensi Karyawan DevHRIS">
    <meta name="keywords" content="absensi, login, devhris" />
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/180x180.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="manifest" href="{{ asset('__manifest.json') }}">
    <style>
        .register-row {
            text-align: center;
            margin-top: 24px;
            color: #6b7280;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body class="login-page">
    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div id="appCapsule">
        <div class="login-form">
            <img src="{{ asset('assets/img/logo.png') }}" alt="DevHRIS" class="form-image">

            <div class="login-header">
                <h1>Selamat Datang</h1>
                <p>Silakan masuk ke akun Anda</p>
            </div>

            @if (Session::has('warning'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                    <span>{{ Session::get('warning') }}</span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('login.process') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="nik">NIK</label>
                    <div class="input-wrapper">
                        <ion-icon name="person-outline" class="input-icon"></ion-icon>
                        <input type="text" name="nik" class="form-control" id="nik"
                            placeholder="Masukkan NIK Anda" value="{{ old('nik') }}" autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper password-wrapper">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Masukkan password Anda" autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <ion-icon name="eye-outline" id="toggleIcon"></ion-icon>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Masuk
                </button>
            </form>

            <div class="register-row">
                Belum punya akun?
                <a href="{{ route('registrasi.pretoken') }}" class="register-link">Daftar di sini</a>
            </div>

            <div class="mt-3 text-center">
                Copyright &copy; 2026
                <a href="#" class="link-secondary">DevHRIS</a>.
                All rights reserved.
            </div>            
        </div>
    </div>

    <script src="{{ asset('assets/js/lib/jquery-3.4.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/lib/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/lib/bootstrap.min.js') }}"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $("#loader").fadeOut(250);
            }, 300);
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('name', 'eye-off-outline');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('name', 'eye-outline');
            }
        }

        $(document).ready(function() {
            if ($('.alert').length > 0) {
                setTimeout(function() {
                    $('.alert').fadeOut('slow');
                }, 5000);
            }

            @if (Session::has('error_swal'))
                Swal.fire({
                    title: 'Akses Ditolak!',
                    text: "{{ Session::get('error_swal') }}",
                    icon: 'error',
                    confirmButtonColor: '#1e74fd'
                });
            @endif
        });

        document.addEventListener('DOMContentLoaded', function() {
            var loginForm = document.querySelector('form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    var nikInput = document.getElementById('nik');
                    var passwordInput = document.getElementById('password');

                    var nikVal = nikInput.value.trim();
                    var passwordVal = passwordInput.value.trim();

                    if (!nikVal) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'NIK tidak boleh kosong.',
                            icon: 'warning',
                            confirmButtonColor: '#1e74fd'
                        });
                        nikInput.focus();
                        return false;
                    }

                    if (!passwordVal) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'Password tidak boleh kosong.',
                            icon: 'warning',
                            confirmButtonColor: '#1e74fd'
                        });
                        passwordInput.focus();
                        return false;
                    }

                    var btnSubmit = loginForm.querySelector('.btn-login');
                    if (btnSubmit) {
                        btnSubmit.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
                        btnSubmit.disabled = true;
                    }
                });
            }
        });
    </script>
</body>

</html>