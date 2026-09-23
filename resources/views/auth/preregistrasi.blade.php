<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#2d6ea6">
    <title>Verifikasi Token | Sistem Absensi wndev</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="manifest" href="{{ asset('__manifest.json') }}?v=20260618">
    <link rel="stylesheet" href="{{ asset_v('assets/css/style.css') }}">
    <style>
        :root {
            --theme-color: #2a988d;
            --theme-hover: #2f8f85;
            --bg-body: #e8eef4;
        }

        body.login-page {
            background-color: var(--bg-body);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-form {
            background: #fff;
            padding: 40px 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .form-image {
            width: 80px;
            margin-bottom: 24px;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }

        .form-control {
            width: 100%;
            height: 52px;
            padding: 10px 15px 10px 45px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            font-size: 15px;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--theme-color);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(42, 152, 141, 0.1);
            outline: none;
        }

        .btn-verify {
            width: 100%;
            height: 54px;
            background: linear-gradient(135deg, #2f8f85 0%, #2a988d 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(42, 152, 141, 0.3);
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(42, 152, 141, 0.4);
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .back-link:hover {
            color: var(--theme-color);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }
    </style>
</head>

<body class="login-page">

    <div class="login-form">
        <x-brand-logo variant="square" alt="wndev" class="form-image" />

        <div class="login-header">
            <h1>Verifikasi Token</h1>
            <p>Silakan masukkan token registrasi yang tertera pada Monitor Ruangan HRD</p>
        </div>

        @if (Session::has('warning'))
            <div class="alert alert-danger">
                <ion-icon name="alert-circle-outline" style="font-size: 20px;"></ion-icon>
                <span>{{ Session::get('warning') }}</span>
            </div>
        @endif

        <form action="{{ route('registrasi.verify') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label">Registration Token</label>
                <div class="input-wrapper">
                    <ion-icon name="key-outline" class="input-icon"></ion-icon>
                    <input type="text" name="token" class="form-control" placeholder="Masukkan 6 digit token"
                        maxlength="7" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn-verify">
                Verifikasi
            </button>
        </form>

        <a href="{{ route('login') }}" class="back-link">
            <ion-icon name="arrow-back-outline" style="vertical-align: middle;"></ion-icon> Kembali ke Login
        </a>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>
