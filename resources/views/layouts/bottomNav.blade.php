<div class="bottom-nav">
    <a href="/dashboard" class="nav-item{{ request()->is('dashboard') ? ' active' : '' }}">
        <div class="nav-icon">
            <ion-icon name="home-outline"></ion-icon>
        </div>
        <span>Home</span>
    </a>

    <a href="/presensi/histori" class="nav-item{{ request()->is('presensi/histori*') ? ' active' : '' }}">
        <div class="nav-icon">
            <ion-icon name="time-outline"></ion-icon>
        </div>
        <span>Histori</span>
    </a>

    <a href="/presensi/create" class="nav-item action-center{{ request()->is('presensi/create') ? ' active' : '' }}">
        <div class="nav-icon">
            <div class="action-button large">
                <ion-icon name="camera" aria-label="camera"></ion-icon>
            </div>
        </div>
    </a>

    <a href="/presensi/izin" class="nav-item{{ request()->is('presensi/izin*') ? ' active' : '' }}">
        <div class="nav-icon">
            <ion-icon name="calendar-outline"></ion-icon>
        </div>
        <span>Izin</span>
    </a>

    <a href="/presensi/profile" class="nav-item{{ request()->is('presensi/profile*') ? ' active' : '' }}">
        <div class="nav-icon">
            <ion-icon name="person-outline"></ion-icon>
        </div>
        <span>Profil</span>
    </a>
</div>
