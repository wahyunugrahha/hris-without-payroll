@if ($histori->isEmpty())
    <div class="d-flex flex-column align-items-center justify-content-center mt-4">
        <div style="font-size: 50px; color: #cbd5e0;">
            <ion-icon name="file-tray-outline"></ion-icon>
        </div>
        <p class="text-muted text-center mt-2">Tidak ada data presensi<br>untuk periode ini.</p>
    </div>
@else
    <div class="row">
        @foreach ($histori as $d)
            @php
                // Logic Penentuan Data (sama seperti sebelumnya, dirapikan)
                if ($d->status == 'h') {
                    $path = Storage::url('uploads/absensi/' . $d->foto_in);
                    $foto_in_url = url($path);
                    $foto_out_url = !empty($d->foto_out) ? url(Storage::url('uploads/absensi/' . $d->foto_out)) : '';

                    $jam_in_display = $d->jam_in;
                    $jam_out_display = $d->jam_out != null ? $d->jam_out : '--:--';

                    $jam_masuk_jadwal = $d->jam_masuk_jadwal ?? null;
                    $is_terlambat = is_terlambat($jam_masuk_jadwal, $d->jam_in);

                    // Warna badge jam
                    $color_in = $is_terlambat ? 'text-danger' : 'text-success';
                    $color_out = 'text-primary';

                    $nama_status = 'Hadir';
                    $status_class = 'badge-success'; // Untuk modal

                    if (!empty($d->is_pulang_cepat)) {
                        $nama_status = 'Pulang Cepat';
                        $status_class = 'badge-primary';
                    } elseif ($d->foto_in === '-') {
                        $nama_status = 'Izin Terlambat (Auto)';
                        $status_class = 'badge-warning';
                    }

                    $icon_display = '<img src="' . $foto_in_url . '" class="avatar-img rounded-circle" alt="user">';
                    if ($d->foto_in === '-') {
                        $icon_display =
                            '<div class="avatar-icon bg-warning-soft text-warning"><ion-icon name="alarm-outline"></ion-icon></div>';
                    }
                } else {
                    $foto_in_url = '';
                    $foto_out_url = '';
                    $jam_in_display = '-';
                    $jam_out_display = '-';
                    $color_in = 'text-muted';
                    $color_out = 'text-muted';

                    // Logic Status Izin/Sakit/Cuti
                    if ($d->status == 'i') {
                        $nama_status = 'Izin';
                        $status_class = 'badge-primary';
                        $icon_display =
                            '<div class="avatar-icon bg-primary-soft text-primary"><ion-icon name="document-text-outline"></ion-icon></div>';
                        $ket_display = 'Izin: ' . Str::limit($d->keterangan, 15);
                    } elseif ($d->status == 's') {
                        $nama_status = 'Sakit';
                        $status_class = 'badge-warning';
                        $icon_display =
                            '<div class="avatar-icon bg-warning-soft text-warning"><ion-icon name="medkit-outline"></ion-icon></div>';
                        $ket_display = 'Sakit: ' . Str::limit($d->keterangan, 15);
                    } elseif ($d->status == 'c') {
                        $nama_status = 'Cuti';
                        $status_class = 'badge-info';
                        $icon_display =
                            '<div class="avatar-icon bg-info-soft text-info"><ion-icon name="calendar-outline"></ion-icon></div>';
                        $ket_display = $d->nama_cuti;
                    } elseif ($d->status == 'r') {
                        $nama_status = 'Roster';
                        $status_class = 'badge-primary';
                        $icon_display =
                            '<div class="avatar-icon bg-primary-soft text-primary"><ion-icon name="calendar-clear-outline"></ion-icon></div>';
                        $ket_display = 'Roster: ' . Str::limit($d->keterangan, 15);
                    } elseif ($d->status == 'd') {
                        $nama_status = 'Dinas Luar';
                        $status_class = 'badge-success';
                        $icon_display =
                            '<div class="avatar-icon bg-success-soft text-success"><ion-icon name="briefcase-outline"></ion-icon></div>';
                        $ket_display = 'DL: ' . Str::limit($d->keterangan, 15);
                    } elseif ($d->status == 't') {
                        $nama_status = 'Izin Terlambat / TL';
                        $status_class = 'badge-warning';
                        $icon_display =
                            '<div class="avatar-icon bg-warning-soft text-warning"><ion-icon name="alarm-outline"></ion-icon></div>';
                        $ket_display = 'DL/TL: ' . Str::limit($d->keterangan, 15);
                    } elseif ($d->status == 'x') {
                        $nama_status = 'Dianulir HR';
                        $status_class = 'badge-danger';
                        $icon_display =
                            '<div class="avatar-icon bg-danger-soft text-danger"><ion-icon name="close-circle-outline"></ion-icon></div>';
                        $ket_display = 'Silakan Absen Masuk Ulang';
                    } elseif ($d->status == 'a') {
                        $nama_status = 'Tidak Presensi';
                        $status_class = 'badge-danger';
                        $icon_display =
                            '<div class="avatar-icon bg-danger-soft text-danger"><ion-icon name="close-circle-outline"></ion-icon></div>';
                        $ket_display = 'Alpha / Mangkir';
                    } elseif ($d->status == 'l') {
                        $nama_status = 'Libur';
                        $status_class = 'badge-secondary';
                        $icon_display =
                            '<div class="avatar-icon bg-secondary-soft text-secondary"><ion-icon name="home-outline"></ion-icon></div>';
                        $ket_display = 'Tidak Wajib Absen';
                    } else {
                        $nama_status = strtoupper($d->status);
                        $status_class = 'badge-secondary';
                        $icon_display =
                            '<div class="avatar-icon bg-secondary-soft"><ion-icon name="help-outline"></ion-icon></div>';
                        $ket_display = '-';
                    }
                }
            @endphp

            <div class="col-12 mb-2">
                {{-- Card dibuat clickable dengan class .card-histori --}}
                <div class="card card-histori shadow-sm border-0"
                    style="border-radius: 12px; cursor: pointer; transition: transform 0.1s;"
                    onclick="this.style.transform='scale(0.98)'" onmouseout="this.style.transform='scale(1)'"
                    data-tanggal="{{ date('d-M-Y', strtotime($d->tgl_presensi)) }}" data-status="{{ $nama_status }}"
                    data-status-class="{{ $status_class }}"
                    data-keterangan="{{ $d->status == 'h' ? 'Tepat Waktu/Terlambat' : $d->keterangan ?? '-' }}"
                    data-jam-masuk="{{ $d->status == 'h' ? $d->jam_in : '-' }}"
                    data-jam-keluar="{{ $d->status == 'h' ? $d->jam_out ?? '-' : '-' }}"
                    data-foto-masuk="{{ $foto_in_url }}" data-foto-keluar="{{ $foto_out_url }}">

                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            {{-- Kolom Kiri: Icon/Foto & Tanggal --}}
                            <div class="mr-3 text-center" style="width: 50px;">
                                <div class="mb-1" style="height: 45px; width: 45px; margin: 0 auto;">
                                    {!! $icon_display !!}
                                </div>
                            </div>

                            {{-- Kolom Tengah: Tanggal & Keterangan --}}
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 15px;">
                                    {{ date('d-M-Y', strtotime($d->tgl_presensi)) }}
                                </h6>
                                <p class="text-muted mb-0 text-truncate small">
                                    @if ($d->status == 'h')
                                        @if (!empty($d->is_pulang_cepat))
                                            <span class="badge badge-primary-soft text-primary">Pulang Cepat</span>
                                        @elseif ($d->foto_in === '-')
                                            <span class="badge badge-warning-soft text-warning">Izin Terlambat</span>
                                        @else
                                            <span
                                                class="badge {{ $is_terlambat ? 'badge-danger-soft text-danger' : 'badge-success-soft text-success' }}">
                                                {{ $is_terlambat ? 'Terlambat' : 'Tepat Waktu' }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ $nama_status }}</span>
                                    @endif

                                    @if (isset($d->daily_points) && !empty($d->daily_points->point_details))
                                        @foreach ($d->daily_points->point_details as $pts)
                                            @if ($pts != 0)
                                                <span class="badge"
                                                    style="font-size: 11px; font-weight: bold; color: {{ $pts > 0 ? '#059669' : '#dc2626' }}; background: {{ $pts > 0 ? '#e8f5e9' : '#ffebee' }}; margin-left: 4px;">
                                                    {{ $pts > 0 ? '+' : '' }}{{ number_format($pts) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </p>
                            </div>

                            {{-- Kolom Kanan: Jam Masuk/Pulang --}}
                            <div class="text-right pl-2" style="min-width: 75px;">
                                @if ($d->status == 'h')
                                    <div class="d-block font-weight-bold {{ $color_in }}" style="font-size: 13px;">
                                        IN: {{ date('H:i', strtotime($d->jam_in)) }}
                                    </div>
                                    <div class="d-block font-weight-bold {{ $color_out }}" style="font-size: 13px;">
                                        OUT: {{ $d->jam_out != null ? date('H:i', strtotime($d->jam_out)) : '--:--' }}
                                    </div>
                                @else
                                    <div class="text-muted small">Not Available</div>
                                @endif
                            </div>

                            {{-- Chevron Icon --}}
                            <div class="pl-2 text-muted">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tambahan CSS Inline khusus untuk komponen list ini agar rapi --}}
    <style>
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .avatar-icon {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Soft Colors for Badges & Icons */
        .bg-primary-soft {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }

        .bg-success-soft {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        .bg-info-soft {
            background-color: rgba(23, 162, 184, 0.1) !important;
        }

        .bg-secondary-soft {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }

        .badge-success-soft {
            background-color: rgba(40, 167, 69, 0.15);
        }

        .badge-danger-soft {
            background-color: rgba(220, 53, 69, 0.15);
        }

        .badge-primary-soft {
            background-color: rgba(0, 123, 255, 0.15);
        }
    </style>
@endif
