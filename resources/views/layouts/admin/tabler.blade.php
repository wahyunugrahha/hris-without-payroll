<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Sistem Absensi DevHRIS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/180x180.png') }}">
    <link rel="manifest" href="{{ asset('__manifest.json') }}">
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
        // Apply theme immediately before page renders (prevents flash)
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-primary: #2fb344;
            --tblr-primary-rgb: 47, 179, 68;
            --admin-header-height: 66px;
        }

        .navbar-vertical {
            background-color: #ffffff;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .navbar-vertical .navbar-brand {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            min-height: var(--admin-header-height);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: .35rem 0;
            margin-bottom: .6rem;
        }

        .navbar-vertical .admin-sidebar-logo {
            height: clamp(34px, calc(var(--admin-header-height) - 24px), 42px);
            width: auto;
            max-width: min(250px, 94%);
            object-fit: contain;
        }

        @media (max-width: 1199.98px) {
            .navbar-vertical .admin-sidebar-logo {
                height: 34px;
                max-width: min(220px, 84vw);
            }
        }

        .navbar-vertical .nav-link {
            color: #495057;
        }

        .navbar-vertical .nav-link .icon {
            opacity: .85;
        }

        .navbar-vertical .nav-link:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.06);
            color: #212529;
        }

        .navbar-vertical .nav-link.active,
        .navbar-vertical .dropdown-item.active {
            color: var(--tblr-primary);
            background-color: rgba(var(--tblr-primary-rgb), 0.10);
            font-weight: 500;
        }

        .navbar-vertical .dropdown-menu .dropdown-item {
            color: #495057;
        }

        .navbar-vertical .dropdown-menu .dropdown-item:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.06);
        }

        .app-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            min-height: var(--admin-header-height);
        }

        .app-header .nav-link {
            color: #495057;
        }

        .app-header .nav-link:hover {
            color: var(--tblr-primary);
            background-color: rgba(var(--tblr-primary-rgb), 0.06);
            border-radius: .375rem;
        }

        .app-header .icon-1 {
            width: 22px;
            height: 22px;
        }

        .app-header .badge-notif {
            position: absolute;
            top: 4px;
            right: 2px;
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background-color: var(--tblr-primary);
            border: 2px solid #ffffff;
            padding: 0;
        }

        .app-header .avatar {
            box-shadow: 0 0 0 2px rgba(var(--tblr-primary-rgb), 0.25);
        }

        .app-header .fw-semibold {
            font-weight: 600;
        }

        [data-bs-theme="dark"] .navbar-vertical {
            background-color: #1e293b !important;
            border-right-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .navbar-brand {
            border-bottom-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .nav-link {
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .nav-link:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.15) !important;
            color: #f1f5f9 !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .nav-link.active,
        [data-bs-theme="dark"] .navbar-vertical .dropdown-item.active {
            background-color: rgba(var(--tblr-primary-rgb), 0.20) !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .dropdown-menu {
            background-color: #0f172a !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .dropdown-menu .dropdown-item {
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] .navbar-vertical .dropdown-menu .dropdown-item:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.15) !important;
        }

        [data-bs-theme="dark"] .app-header {
            background-color: #1e293b !important;
            border-bottom-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .app-header .nav-link {
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] .app-header .nav-link:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.15) !important;
        }

        [data-bs-theme="dark"] .page-wrapper {
            background-color: #0f172a !important;
        }

        [data-bs-theme="dark"] .card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .text-secondary {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .page-title,
        [data-bs-theme="dark"] .card-title,
        [data-bs-theme="dark"] .font-weight-medium {
            color: #f1f5f9 !important;
        }

        [data-bs-theme="dark"] .dropdown-menu {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .dropdown-item {
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] .dropdown-item:hover {
            background-color: rgba(var(--tblr-primary-rgb), 0.15) !important;
        }

        [data-bs-theme="dark"] .input-group-text.bg-white,
        [data-bs-theme="dark"] .input-group-text.date-filter-addon {
            background-color: #121f31 !important;
            border-color: #2a3a52 !important;
            color: #9db0c8 !important;
        }

        [data-bs-theme="dark"] .input-icon-addon {
            color: #9db0c8 !important;
            background-color: #121f31 !important;
            border-color: #2a3a52 !important;
        }

        [data-bs-theme="dark"] .text-dark,
        [data-bs-theme="dark"] .text-body,
        [data-bs-theme="dark"] .form-control-plaintext,
        [data-bs-theme="dark"] .table,
        [data-bs-theme="dark"] .table th,
        [data-bs-theme="dark"] .table td {
            color: #dbe7f5 !important;
        }

        [data-bs-theme="dark"] .text-muted {
            color: #9fb0c6 !important;
        }

        [data-bs-theme="dark"] .card-header.bg-transparent {
            background-color: #1a2739 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: #121f31 !important;
            color: #dbe7f5 !important;
            border-color: #2a3a52 !important;
        }

        [data-bs-theme="dark"] .form-control[readonly] {
            background-color: #121f31 !important;
            color: #dbe7f5 !important;
        }

        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            border-color: #4d698f !important;
            box-shadow: 0 0 0 0.2rem rgba(77, 105, 143, 0.25) !important;
        }

        [data-bs-theme="dark"] .form-control::placeholder,
        [data-bs-theme="dark"] .form-select::placeholder {
            color: #93a6bf !important;
            opacity: 1;
        }

        [data-bs-theme="dark"] .table-hover>tbody>tr:hover>* {
            background-color: #162335 !important;
            color: #e2edf9 !important;
        }

        [data-bs-theme="dark"] .table> :not(caption)>*>* {
            border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        }
    </style>
</head>

<body>
    <div class="page">
        @include('layouts.admin.sidebar')
        <div class="page-wrapper">
            @include('layouts.admin.header')

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

    <script>
        // Dark Mode Toggle (handle clicks only)
        document.addEventListener("DOMContentLoaded", function() {
            // Handle theme toggle clicks
            document.querySelectorAll('[href="?theme=dark"], [href="?theme=light"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const newTheme = this.getAttribute('href').includes('dark') ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            });

            // Global SweetAlert for Session Success
            @if (Session::get('success'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: "{{ Session::get('success') }}",
                    icon: 'success',
                    confirmButtonText: 'Ok'
                });
            @endif

            // Global SweetAlert for Session Warning/Error
            @if (Session::get('warning'))
                Swal.fire({
                    title: 'Peringatan!',
                    text: "{{ Session::get('warning') }}",
                    icon: 'warning',
                    confirmButtonText: 'Ok'
                });
            @endif

            // Global SweetAlert for Validation Errors
            @if ($errors->any())
                Swal.fire({
                    title: 'Gagal!',
                    html: `
                        <div class="text-start">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            @endif
        });
    </script>

    @stack('myscript')
</body>

</html>
