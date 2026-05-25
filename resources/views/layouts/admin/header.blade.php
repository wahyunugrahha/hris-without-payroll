<header class="navbar navbar-expand-md d-print-none d-none d-xl-block app-header">
    <div class="container-xl">
        <div class="navbar-nav flex-row ms-auto align-items-center">

            {{-- 1. THEME TOGGLE (Dark/Light Mode) --}}
            <div class="nav-item d-none d-md-flex me-3">
                <a href="?theme=dark" class="nav-link px-2 hide-theme-dark" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" data-bs-title="Enable dark mode" aria-label="Enable dark mode">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-1">
                        <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                    </svg>
                </a>
                <a href="?theme=light" class="nav-link px-2 hide-theme-light" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" data-bs-title="Enable light mode" aria-label="Enable light mode">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon icon-1">
                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                        <path
                            d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                    </svg>
                </a>
            </div>

            {{-- 2. NOTIFICATIONS --}}
            <div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" tabindex="-1"
                    aria-label="Show notifications">
                    {{-- Bell Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                        <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                    </svg>

                    {{-- Badge Merah (Hanya muncul jika ada pending) --}}
                    @if ($totalPending > 0)
                        <span class="badge bg-danger text-white badge-pill"
                            style="position: absolute; top: 0; right: 0; transform: translate(25%, -25%);">
                            {{ $totalPending }}
                        </span>
                        {{-- Titik indikator berkedip (opsional, untuk efek visual) --}}
                        <span class="status-indicator status-red status-indicator-animated"
                            style="position: absolute; top: 8px; right: 8px; width: 6px; height: 6px;"></span>
                    @endif
                </a>

                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Notifications</h3>
                            @if ($totalPending > 0)
                                <span class="badge bg-red ms-auto">{{ $totalPending }} Pending</span>
                            @endif
                        </div>

                        {{-- List Notifikasi --}}
                        <div class="list-group list-group-flush list-group-hoverable">
                            @forelse($recentItems as $item)
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            {{-- Icon berdasarkan tipe (warna & gambar) --}}
                                            <a href="{{ $item['url'] }}">
                                                <span class="avatar bg-{{ $item['color'] }}-lt">
                                                    @if ($item['icon'] == 'ambulance')
                                                        {{-- Icon Sakit/Izin --}}
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 8v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                                            <path d="M10 14h4" />
                                                            <path d="M12 12v4" />
                                                            <path
                                                                d="M6 8h12a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2z" />
                                                        </svg>
                                                    @elseif($item['icon'] == 'map-pin')
                                                        {{-- Icon Dinas --}}
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                                            <path
                                                                d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" />
                                                        </svg>
                                                    @else
                                                        {{-- Icon Lembur (Clock) --}}
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                            <path d="M12 7v5l3 3" />
                                                        </svg>
                                                    @endif
                                                </span>
                                            </a>
                                        </div>
                                        <div class="col text-truncate">
                                            <a href="{{ $item['url'] }}"
                                                class="text-body d-block">{{ $item['type'] }}</a>
                                            <div class="d-block text-muted text-truncate mt-n1 small">
                                                {{ $item['desc'] }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="list-group-item-actions text-muted small">
                                                {{ \Carbon\Carbon::parse($item['time'])->diffForHumans(null, true) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center p-4">
                                    <p class="text-muted mb-0">Tidak ada notifikasi baru.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Footer Link --}}
                        @if ($totalPending > 0)
                            <div class="card-footer text-center p-2">
                                <a href="{{ url('/presensi/izinsakit') }}" class="btn btn-ghost-primary btn-sm w-100">
                                    Lihat Semua Pengajuan
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. USER MENU --}}
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex align-items-center lh-1 p-0 px-2" data-bs-toggle="dropdown"
                    aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url('{{ $avatarUrl }}')"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div class="fw-semibold">{{ $authUser->name ?? 'Administrator' }}</div>
                        <div class="mt-1 small text-secondary">{{ $userDept ?? 'Divisi' }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('users.account') }}" class="dropdown-item">Profile & Account Settings</a>
                    @if (auth()->guard('user')->user() && auth()->guard('user')->user()->can('view-karyawan'))
                        <a href="{{ route('users.index') }}" class="dropdown-item">Manage Users</a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('proseslogoutadmin') }}" class="dropdown-item text-danger">Logout</a>
                </div>
            </div>

        </div>
    </div>
</header>
