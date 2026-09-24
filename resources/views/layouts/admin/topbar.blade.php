{{--
    Topbar tunggal panel admin: tombol menu, konteks halaman (@section('page-header')
    dari tiap view: breadcrumb, judul, aksi), lalu tema, notifikasi, dan akun.
--}}
<header class="app-topbar d-print-none">
    <button type="button" class="topbar-icon-btn" data-sidebar-toggle aria-controls="admin-sidebar"
        aria-expanded="true" aria-label="Tampilkan/sembunyikan menu" title="Tampilkan/sembunyikan menu">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24"
            stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            <path d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
            <path d="M9 4v16" />
        </svg>
    </button>

    <div class="topbar-context">
        @yield('page-header')
    </div>

    <div class="topbar-tools">
        <a href="?theme=dark" class="topbar-icon-btn hide-theme-dark" aria-label="Aktifkan mode gelap"
            title="Mode gelap">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24"
                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
            </svg>
        </a>
        <a href="?theme=light" class="topbar-icon-btn hide-theme-light" aria-label="Aktifkan mode terang"
            title="Mode terang">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24"
                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
            </svg>
        </a>

        <div class="topbar-anchor">
            <button type="button" class="topbar-icon-btn" data-popover-toggle aria-expanded="false"
                aria-controls="topbar-notif" aria-label="Notifikasi ({{ $totalPending }} menunggu)"
                title="Notifikasi">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24"
                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                    <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                </svg>
                @if ($totalPending > 0)
                    <span class="topbar-count">{{ $totalPending > 99 ? '99+' : $totalPending }}</span>
                @endif
            </button>

            <div class="topbar-popover topbar-popover-wide" id="topbar-notif" hidden>
                <div class="topbar-popover-head">
                    <strong>Notifikasi</strong>
                    @if ($totalPending > 0)
                        <span class="text-secondary small">{{ $totalPending }} menunggu</span>
                    @endif
                </div>
                <div class="topbar-popover-body">
                    @forelse ($recentItems as $item)
                        <a href="{{ $item['url'] }}" class="topbar-notif-item">
                            <span class="topbar-notif-dot bg-{{ $item['color'] }}" aria-hidden="true"></span>
                            <span class="min-w-0">
                                <span class="d-block text-truncate fw-medium">{{ $item['type'] }}</span>
                                <span class="d-block text-truncate small text-secondary">{{ $item['desc'] }}</span>
                            </span>
                            <span class="small text-secondary text-nowrap">
                                {{ \Carbon\Carbon::parse($item['time'])->diffForHumans(null, true) }}
                            </span>
                        </a>
                    @empty
                        <p class="text-secondary small text-center m-0 py-3">Tidak ada notifikasi baru.</p>
                    @endforelse
                </div>
                @if ($totalPending > 0)
                    <a href="{{ url('/presensi/izinsakit') }}" class="topbar-popover-foot">Lihat semua pengajuan</a>
                @endif
            </div>
        </div>

        <div class="topbar-anchor">
            <button type="button" class="topbar-account" data-popover-toggle aria-expanded="false"
                aria-controls="topbar-akun" aria-label="Menu akun">
                <span class="avatar avatar-sm" style="background-image: url('{{ $avatarUrl }}')"></span>
                <span class="topbar-account-text min-w-0 text-start">
                    <span class="d-block text-truncate fw-medium">{{ $authUser->name ?? 'Administrator' }}</span>
                    <span class="d-block text-truncate small text-secondary">{{ $userDept ?? 'Divisi' }}</span>
                </span>
            </button>

            <div class="topbar-popover" id="topbar-akun" hidden>
                <a href="{{ route('users.account') }}" class="topbar-popover-link">Profil &amp; pengaturan akun</a>
                @can('users-view-admin')
                    <a href="{{ route('users.index') }}" class="topbar-popover-link">Manajemen user</a>
                @endcan
                <a href="{{ route('proseslogoutadmin') }}" class="topbar-popover-link text-danger">Keluar</a>
            </div>
        </div>
    </div>
</header>
