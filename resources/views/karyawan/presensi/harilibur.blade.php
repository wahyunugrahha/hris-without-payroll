@extends('layouts.presensi')
@section('content')
    @php
        $user = Auth::guard('karyawan')->user();
    @endphp

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <div id="appCapsule">
        <div class="section" style="margin-top: 100px;">
            <div class="card"
                style="border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
                {{ isset($jenisLibur) && $jenisLibur == 'jam_kerja' ? 'border-left: 5px solid #f59e0b;' : 'border-left: 5px solid #0066cc;' }}">
                <div class="card-body text-center" style="padding: 40px 20px;">
                    @if (isset($jenisLibur) && $jenisLibur == 'jam_kerja')
                        <div style="font-size: 80px; color: #f59e0b; margin-bottom: 20px;">
                            <ion-icon name="sunny-outline"></ion-icon>
                        </div>
                        <h2 style="color: #f59e0b; margin-bottom: 15px;"> Hari Libur</h2>
                        <h4 style="margin-bottom: 10px; color: #333;">Libur Sesuai Jadwal Kerja</h4>
                        <p style="color: #666; margin-top: 20px; line-height: 1.6;">
                            Hari ini merupakan hari libur sesuai dengan<br>
                            <strong>pengaturan jam kerja Anda</strong>.<br>
                            <br>
                            <ion-icon name="checkmark-circle" style="color: #2d6ea6; font-size: 24px;"></ion-icon><br>
                            Anda tidak perlu melakukan presensi hari ini.<br>
                            Selamat menikmati hari libur! ☀️
                        </p>
                    @else
                        @php
                            $jenisLiburLabel = $hariLiburInfo && $hariLiburInfo->jenis_libur
                                ? $hariLiburInfo->jenis_libur
                                : 'nasional';
                            $judulLibur = $jenisLiburLabel === 'lokal' ? 'Hari Libur Lokal' : 'Hari Libur Nasional';
                        @endphp
                        <div style="font-size: 80px; color: #0066cc; margin-bottom: 20px;">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <h2 style="color: #0066cc; margin-bottom: 15px;">{{ $judulLibur }}</h2>
                        <h4 style="margin-bottom: 10px;">{{ $hariLiburInfo->keterangan ?? 'Hari Libur' }}</h4>
                        @if ($hariLiburInfo)
                            <span class="badge badge-primary"
                                style="font-size: 14px; padding: 8px 15px; margin-bottom: 20px;">
                                <ion-icon name="flag" style="font-size: 16px;"></ion-icon>
                                {{ ucfirst($jenisLiburLabel) }}
                            </span>
                        @endif
                        <p style="color: #666; margin-top: 20px; line-height: 1.6;">
                            <ion-icon name="checkmark-circle" style="color: #2d6ea6; font-size: 24px;"></ion-icon><br>
                            Karyawan tidak perlu melakukan presensi hari ini.<br>
                            Selamat menikmati hari libur! 🎉
                        </p>
                    @endif
                    <a href="/dashboard" class="btn btn-primary"
                        style="margin-top: 30px; padding: 12px 40px; border-radius: 25px;">
                        <ion-icon name="arrow-back-outline"></ion-icon>
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
