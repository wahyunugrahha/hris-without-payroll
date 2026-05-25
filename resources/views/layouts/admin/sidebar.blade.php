@php
    $authUser = Auth::guard('user')->user();
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg navbar-light bg-white border-end" data-bs-theme="light">
    <div class="container-fluid">
        <button class="navbar-toggler d-xl-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark text-center px-0 mx-auto">
            <a href="{{ route('dashboard.admin') }}" class="d-inline-flex align-items-center justify-content-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="DevHRIS"
                    class="navbar-brand-image admin-sidebar-logo">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item dropdown mt-3 d-xl-none">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center"
                        data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm me-2"
                            style="background-image: url({{ asset('assets/img/nophoto.png') }})">
                        </span>
                        <span>{{ $authUser->name ?? 'Administrator' }}</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('users.account') }}" class="dropdown-item">Profile & Account Settings</a>
                        @can('users-view-admin')
                            <a href="{{ route('users.index') }}" class="dropdown-item">Manage Users</a>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('proseslogoutadmin') }}" class="dropdown-item text-danger">Logout</a>
                    </div>
                </li>

                {{-- Dashboard --}}
                <li class="nav-item mt-2">
                    <a class="nav-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}"
                        href="{{ route('dashboard.admin') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12h-2l9 -9l9 9h-2" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                {{-- Data Master --}}
                @if (
                    $authUser->hasAnyPermission([
                        'karyawan-view-admin',
                        'jabatan-view-admin',
                        'departemen-view-admin',
                        'cabang-view-admin',
                        'cuti-view-admin',
                        'jam-kerja-view-admin',
                        'jam-kerja-dept-view-admin',
                        'hari-libur-view-admin',
                        'pengumuman-view-admin',
                    ]))
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isMasterActive =
                                request()->routeIs(
                                    'karyawan.*',
                                    'jabatan.*',
                                    'departemen.*',
                                    'cabang.*',
                                    'cuti.*',
                                    'konfigurasi.jamkerja*',
                                    'konfigurasi.jamkerjadept*',
                                    'harilibur.*',
                                    'pengumuman.*',
                                ) && !request()->routeIs('karyawan.monitoring.*');
                        @endphp
                        <a class="nav-link dropdown-toggle {{ $isMasterActive ? 'show' : '' }}" href="#navbar-master"
                            data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                            aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9z" />
                                    <path d="M12 12l8 -4.5" />
                                    <path d="M12 12v9" />
                                    <path d="M12 12l-8 -4.5" />
                                    <path d="M16 5.25l-8 4.5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Data Master</span>
                        </a>
                        <div class="dropdown-menu {{ $isMasterActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    @can('karyawan-view-admin')
                                        <a href="{{ route('karyawan.index') }}"
                                            class="dropdown-item {{ request()->routeIs('karyawan.*') && !request()->routeIs('karyawan.monitoring.*') ? 'active' : '' }}">Data
                                            Karyawan</a>
                                    @endcan

                                    @can('jabatan-view-admin')
                                        <a href="{{ route('jabatan.index') }}"
                                            class="dropdown-item {{ request()->routeIs('jabatan.*') ? 'active' : '' }}">Data
                                            Jabatan</a>
                                    @endcan

                                    @can('departemen-view-admin')
                                        <a href="{{ route('departemen.index') }}"
                                            class="dropdown-item {{ request()->routeIs('departemen.*') ? 'active' : '' }}">Data
                                            Departemen</a>
                                    @endcan

                                    @can('cabang-view-admin')
                                        <a href="{{ route('cabang.index') }}"
                                            class="dropdown-item {{ request()->routeIs('cabang.*') ? 'active' : '' }}">Data
                                            Kantor Cabang</a>
                                    @endcan

                                    @can('cuti-view-admin')
                                        <a href="{{ route('cuti.index') }}"
                                            class="dropdown-item {{ request()->routeIs('cuti.*') ? 'active' : '' }}">Data
                                            Master Cuti</a>
                                    @endcan

                                    @can('jam-kerja-view-admin')
                                        <a href="{{ route('konfigurasi.jamkerja') }}"
                                            class="dropdown-item {{ request()->routeIs('konfigurasi.jamkerja', 'konfigurasi.editjamkerja', 'konfigurasi.updatejamkerja', 'konfigurasi.destroyjamkerja') ? 'active' : '' }}">Jam
                                            Kerja</a>
                                    @endcan
                                    @can('jam-kerja-dept-view-admin')
                                        <a href="{{ route('konfigurasi.jamkerjadept') }}"
                                            class="dropdown-item {{ request()->routeIs('konfigurasi.jamkerjadept', 'konfigurasi.createjamkerjadept', 'konfigurasi.editjamkerjadept', 'konfigurasi.showjamkerjadept', 'konfigurasi.updatejamkerjadept', 'konfigurasi.deletejamkerjadept') ? 'active' : '' }}">Jam
                                            Kerja Departemen</a>
                                    @endcan
                                    @can('hari-libur-view-admin')
                                        <a href="{{ route('harilibur.index') }}"
                                            class="dropdown-item {{ request()->routeIs('harilibur.*') ? 'active' : '' }}">Hari
                                            Libur</a>
                                    @endcan

                                    @can('pengumuman-view-admin')
                                        <a href="{{ route('pengumuman.index') }}"
                                            class="dropdown-item {{ request()->routeIs('pengumuman.*') ? 'active' : '' }}">Pengumuman</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </li>
                @endif

                {{-- Monitoring Karyawan --}}
                @if (
                    $authUser->hasAnyPermission([
                        'presensi-monitoring-view-admin',
                        'pengajuan-izin-view-admin',
                        'dinas-luar-view-admin',
                        'lembur-view-admin',
                        'surat-peringatan-view-admin',
                        'karyawan-view-admin',
                    ]))
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isMonitoringActive =
                                request()->routeIs(
                                    'presensi.monitoring',
                                    'presensi.izinsakit',
                                    'dinasluars.*',
                                    'suratperingatan.*',
                                    'karyawan.monitoring.turnover',
                                ) ||
                                (request()->routeIs('admin.lembur.*') && !request()->routeIs('admin.lembur.rekap'));
                        @endphp
                        <a class="nav-link dropdown-toggle {{ $isMonitoringActive ? 'show' : '' }}"
                            href="#navbar-monitoring" data-bs-toggle="dropdown" data-bs-auto-close="false"
                            role="button" aria-expanded="{{ $isMonitoringActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-device-desktop-analytics">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M3 5a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1l0 -10" />
                                    <path d="M7 20h10" />
                                    <path d="M9 16v4" />
                                    <path d="M15 16v4" />
                                    <path d="M9 12v-4" />
                                    <path d="M12 12v-1" />
                                    <path d="M15 12v-2" />
                                    <path d="M12 12v-1" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Monitoring Karyawan</span>
                        </a>
                        <div class="dropdown-menu {{ $isMonitoringActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    @can('presensi-monitoring-view-admin')
                                        <a href="{{ route('presensi.monitoring') }}"
                                            class="dropdown-item {{ request()->routeIs('presensi.monitoring') ? 'active' : '' }}">Data
                                            Presensi</a>
                                    @endcan
                                    @can('pengajuan-izin-view-admin')
                                        <a href="{{ route('presensi.izinsakit') }}"
                                            class="dropdown-item {{ request()->routeIs('presensi.izinsakit') ? 'active' : '' }}">Data
                                            Izin / Sakit / Cuti</a>
                                    @endcan
                                    @can('dinas-luar-view-admin')
                                        <a href="{{ route('dinasluars.approval') }}"
                                            class="dropdown-item {{ request()->routeIs('dinasluars.*') ? 'active' : '' }}">Data
                                            Dinas Luar</a>
                                    @endcan
                                    @can('lembur-view-admin')
                                        <a href="{{ route('admin.lembur.approval') }}"
                                            class="dropdown-item {{ request()->routeIs('admin.lembur.*') && !request()->routeIs('admin.lembur.rekap') ? 'active' : '' }}">Data
                                            Lembur</a>
                                    @endcan
                                    @can('surat-peringatan-view-admin')
                                        <a href="{{ route('suratperingatan.index') }}"
                                            class="dropdown-item {{ request()->routeIs('suratperingatan.*') ? 'active' : '' }}">Surat
                                            Peringatan</a>
                                    @endcan

                                    @can('karyawan-view-admin')
                                        <a href="{{ route('karyawan.monitoring.turnover') }}"
                                            class="dropdown-item {{ request()->routeIs('karyawan.monitoring.turnover') ? 'active' : '' }}">Karyawan
                                            Turnover</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </li>
                @endif

                {{-- Permintaan Karyawan --}}
                @if ($authUser->hasAnyPermission(['kenaikan-gaji-view-admin', 'bpjs-view-admin', 'bpjs-tk-view-admin']))
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isPermintaanActive =
                                request()->routeIs('admin.kenaikan_gaji.*') || request()->routeIs('admin.bpjs.*');
                        @endphp
                        <a class="nav-link dropdown-toggle {{ $isPermintaanActive ? 'show' : '' }}"
                            href="#navbar-permintaan" data-bs-toggle="dropdown" data-bs-auto-close="false"
                            role="button" aria-expanded="{{ $isPermintaanActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-file-arrow-right">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" />
                                    <path d="M9 15h6" />
                                    <path d="M12.5 17.5l2.5 -2.5l-2.5 -2.5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Permintaan Karyawan</span>
                        </a>
                        <div class="dropdown-menu {{ $isPermintaanActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    @can('kenaikan-gaji-view-admin')
                                        <a href="{{ route('admin.kenaikan_gaji.index') }}"
                                            class="dropdown-item {{ request()->routeIs('admin.kenaikan_gaji.*') ? 'active' : '' }}">Kenaikan
                                            Gaji Karyawan</a>
                                    @endcan
                                    @canany(['bpjs-view-admin', 'bpjs-tk-view-admin'])
                                        <a href="{{ route('admin.bpjs.index') }}"
                                            class="dropdown-item {{ request()->routeIs('admin.bpjs.*') ? 'active' : '' }}">BPJS
                                            Karyawan</a>
                                    @endcanany
                                </div>
                            </div>
                        </div>
                    </li>
                @endif

                {{-- Key Performance Indicator --}}
                @can('kpi-view-admin')
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isKPIActive = request()->routeIs(
                                'kpi.dashboard',
                                'kpi.master.*',
                                'kpi.indikator.*',
                                'kpi.rekap.*',
                                'kpi.report',
                                'kpi.report.*',
                            );
                        @endphp

                        <a class="nav-link dropdown-toggle {{ $isKPIActive ? 'show' : '' }}" href="#navbar-kpi"
                            data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                            aria-expanded="{{ $isKPIActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icon-tabler-chart-histogram">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 3v18h18" />
                                    <path d="M20 18v3" />
                                    <path d="M16 16v5" />
                                    <path d="M12 13v8" />
                                    <path d="M8 16v5" />
                                    <path d="M3 11c6 0 5 -5 9 -5s3 5 9 5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">KPI Karyawan</span>
                        </a>
                        <div class="dropdown-menu {{ $isKPIActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    {{-- <a href="{{ route('kpi.dashboard') }}"
                                        class="dropdown-item {{ request()->routeIs('kpi.dashboard') ? 'active' : '' }}">Dashboard
                                        KPI</a> --}}
                                    <a href="{{ route('kpi.master.index') }}"
                                        class="dropdown-item {{ request()->routeIs('kpi.master.*') ? 'active' : '' }}">
                                        Data Master KPI</a>
                                    <a href="{{ route('kpi.rekap.karyawan') }}"
                                        class="dropdown-item {{ request()->routeIs(['kpi.rekap.*', 'kpi.indikator.*']) ? 'active' : '' }}">
                                        Rekap KPI Karyawan
                                    </a>
                                    <a href="{{ route('kpi.report') }}"
                                        class="dropdown-item {{ request()->routeIs(['kpi.report', 'kpi.report.*']) ? 'active' : '' }}">
                                        Rekap PAK
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                @endcan

                {{-- Laporan --}}
                @can('laporan-view-admin')
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isLaporanActive = request()->routeIs(
                                'presensi.laporan',
                                'presensi.rekap',
                                'admin.lembur.rekap',
                            );
                        @endphp
                        <a class="nav-link dropdown-toggle {{ $isLaporanActive ? 'show' : '' }}" href="#navbar-laporan"
                            data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                            aria-expanded="{{ $isLaporanActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icon-tabler-file-description">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                    <path d="M9 17h6" />
                                    <path d="M9 13h6" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Laporan</span>
                        </a>
                        <div class="dropdown-menu {{ $isLaporanActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a href="{{ route('presensi.laporan') }}"
                                        class="dropdown-item {{ request()->routeIs('presensi.laporan') ? 'active' : '' }}">Presensi</a>
                                    <a href="{{ route('presensi.rekap') }}"
                                        class="dropdown-item {{ request()->routeIs('presensi.rekap') ? 'active' : '' }}">Rekap
                                        Presensi</a>
                                    <a href="{{ route('admin.lembur.rekap') }}"
                                        class="dropdown-item {{ request()->routeIs('admin.lembur.rekap') ? 'active' : '' }}">Rekap
                                        Lembur</a>
                                </div>
                            </div>
                        </div>
                    </li>
                @endcan

                {{-- Konfigurasi --}}
                @if (
                    $authUser->hasAnyPermission([
                        'users-view-admin',
                        'roles-view-admin',
                        'permissions-view-admin',
                        'konfigurasi-umum-view-admin',
                    ]))
                    <li class="nav-item dropdown mt-2">
                        @php
                            $isKonfigActive = request()->routeIs(
                                'users.index',
                                'users.roles*',
                                'users.permissions*',
                                'konfigurasi.umum.*',
                            );
                        @endphp
                        <a class="nav-link dropdown-toggle {{ $isKonfigActive ? 'show' : '' }}"
                            href="#navbar-konfigurasi" data-bs-toggle="dropdown" data-bs-auto-close="false"
                            role="button" aria-expanded="{{ $isKonfigActive ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icon-tabler-settings">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M10.3 4.3c.4 -1.8 3 -1.8 3.4 0a1.7 1.7 0 0 0 2.6 1.1c1.5 -.9 3.3 .8 2.3 2.3a1.7 1.7 0 0 0 1.1 2.6c1.8 .4 1.8 3 0 3.4a1.7 1.7 0 0 0 -1.1 2.6c1 1.5 -.8 3.3 -2.3 2.3a1.7 1.7 0 0 0 -2.6 1.1c-.4 1.8 -3 1.8 -3.4 0a1.7 1.7 0 0 0 -2.6 -1.1c-1.5 1 -3.3 -.8 -2.3 -2.3a1.7 1.7 0 0 0 -1.1 -2.6c-1.8 -.4 -1.8 -3 0 -3.4a1.7 1.7 0 0 0 1.1 -2.6c-1 -1.5 .8 -3.3 2.3 -2.3c1 .6 2.3 .1 2.6 -1.1z" />
                                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Konfigurasi</span>
                        </a>
                        <div class="dropdown-menu {{ $isKonfigActive ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    @can('konfigurasi-umum-view-admin')
                                        <a href="{{ route('konfigurasi.umum.index') }}"
                                            class="dropdown-item {{ request()->routeIs('konfigurasi.umum.*') ? 'active' : '' }}">Konfigurasi
                                            Umum</a>
                                    @endcan
                                    @can('users-view-admin')
                                        <a href="{{ route('users.index') }}"
                                            class="dropdown-item {{ request()->routeIs('users.index') ? 'active' : '' }}">Manajemen
                                            HR & SPV</a>
                                    @endcan
                                    @can('roles-view-admin')
                                        <a href="{{ route('users.roles') }}"
                                            class="dropdown-item {{ request()->routeIs('users.roles*') ? 'active' : '' }}">Manajemen
                                            Role</a>
                                    @endcan
                                    @can('permissions-view-admin')
                                        <a href="{{ route('users.permissions') }}"
                                            class="dropdown-item {{ request()->routeIs('users.permissions*') ? 'active' : '' }}">Manajemen
                                            Permission</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </li>
                @endif

                {{-- Audit Log (Administrator Only) --}}
                @can('audit-log-view-admin')
                    <li class="nav-item mt-2">
                        <a class="nav-link {{ request()->routeIs('audit-log.index') ? 'active' : '' }}"
                            href="{{ route('audit-log.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-list">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                    <path d="M9 12l.01 0" />
                                    <path d="M13 12l2 0" />
                                    <path d="M9 16l.01 0" />
                                    <path d="M13 16l2 0" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Audit Log</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
    </div>
</aside>
