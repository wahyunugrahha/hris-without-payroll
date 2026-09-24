<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Sistem Absensi wndev</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="manifest" href="{{ asset('__manifest.json') }}?v=20260618">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-flags.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-payments.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-social.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css"
        type="text/css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @yield('header')

    <script>
        // Terapkan tema sebelum render agar tidak berkedip.
        try {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') === 'dark' ? 'dark' : 'light');
            if (localStorage.getItem('sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}

        // Halaman yang dipulihkan dari back-forward cache bisa berisi tampilan/data lama: muat ulang.
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                location.reload();
            }
        });
    </script>

    <style>
        @import url('https://rsms.me/inter/inter.css');
    </style>
    {{-- Sistem desain admin: lihat design.md --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin-tokens.css') }}" />
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin-theme.css') }}" />
</head>

<body>
    <div class="page">
        @include('layouts.admin.sidebar')
        <div class="sidebar-backdrop" data-sidebar-close hidden></div>
        <div class="page-wrapper">
            @include('layouts.admin.topbar')

            <div class="page-wrapper">
                @yield('content')
            </div>

            @include('layouts.admin.footer')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    {{-- Data flash untuk assets/js/admin.js --}}
    <script type="application/json" id="admin-flash">
        @json(['success' => session('success'), 'warning' => session('warning'), 'errors' => $errors->all()])
    </script>

    <script src="{{ asset_v('assets/js/admin.js') }}"></script>
    @stack('myscript')
</body>

</html>
