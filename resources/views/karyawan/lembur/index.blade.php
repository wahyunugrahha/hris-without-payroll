@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Data Lembur</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    {{-- Alerts dipindah ke SweetAlert --}}

    {{-- Alert untuk pembatalan dari query parameter --}}
    @if (request()->has('cancelled') && request('cancelled') == 1)
        <div class="row">
            <div class="col">
                <div class="alert alert-info" style="border-left: 4px solid #0ea5e9;">
                    <ion-icon name="information-circle-outline" style="font-size: 20px; vertical-align: middle;"></ion-icon>
                    {{ request('msg', 'Pengajuan lembur telah dibatalkan.') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Filter Form --}}
    <div class="row">
        <div class="col">
            <form method="GET" action="{{ route('lembur.index') }}">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <select name="bulan" id="bulan" class="form-control">
                                <option value="">Bulan</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <select name="tahun" id="tahun" class="form-control">
                                <option value="">Tahun</option>
                                @php
                                    $startYear = 2025;
                                    $currentYear = date('Y');
                                @endphp
                                @for ($y = $currentYear; $y >= $startYear; $y--)
                                    <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col">
                        <button class="btn btn-primary btn-block">
                            <ion-icon name="filter-outline"></ion-icon> Filter Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Data List --}}
    <div class="row mt-2">
        <div class="col">
            @forelse ($lemburs as $l)
                @php
                    $statusBadgeClass = 'bg-warning';
                    $statusText = 'Menunggu';
                    if ($l->status_approved == 1) {
                        $statusBadgeClass = 'bg-success';
                        $statusText = 'Disetujui';
                    } elseif ($l->status_approved == 2) {
                        $statusBadgeClass = 'bg-danger';
                        $statusText = 'Ditolak';
                    }
                @endphp

                <div class="card"
                    style="margin-bottom: 12px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid 
                    @if ($l->status_approved == 1) #dcfce7 @elseif ($l->status_approved == 2) #fee2e2 @else #fef3c7 @endif;">
                    <div class="card-body" style="padding: 14px;">
                        {{-- Header Section with Date, Status, Duration and Expand Button --}}
                        <div
                            style="display: grid; grid-template-columns: 2fr 1.2fr 0.8fr 0.4fr; gap: 12px; margin-bottom: 0; align-items: center; justify-items: start;">
                            {{-- Date Column --}}
                            <div style="text-align: left; width: 100%;">
                                <div
                                    style="font-weight: 700; font-size: 13px; color: #1f2937; display: flex; align-items: flex-start; gap: 6px;">
                                    <ion-icon name="calendar-outline"
                                        style="color: #16a34a; font-size: 16px; flex-shrink: 0; margin-top: 1px;"></ion-icon>
                                    <span style="line-height: 1.4;">
                                        {{ \Carbon\Carbon::parse($l->tanggal_lembur)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Status Column --}}
                            <div
                                style="text-align: center; width: 100%; display: flex; justify-content: center; align-items: flex-start;">
                                <span class="badge {{ $statusBadgeClass }}"
                                    style="padding: 6px 12px; font-size: 11px; font-weight: 700; display: inline-block; border-radius: 6px; white-space: nowrap;">
                                    {{ $statusText }}
                                </span>
                            </div>

                            {{-- Total Jam Column --}}
                            <div
                                style="text-align: center; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 2px;">
                                <div style="font-size: 18px; font-weight: 700; color: #1f2937; line-height: 1;">
                                    {{ $l->total_jam ?? '-' }}
                                </div>
                                <small
                                    style="color: #6b7280; font-size: 10px; font-weight: 500; margin-top: 2px;">Jam</small>
                            </div>

                            {{-- Expand/Collapse Button --}}
                            <button class="expand-toggle" data-target="details-{{ $l->id }}"
                                style="background: none; border: none; padding: 6px; cursor: pointer; display: flex; align-items: flex-start; justify-content: center; color: #9ca3af; transition: transform 0.3s ease; width: 100%; justify-self: end; margin-top: 2px;">
                                <ion-icon name="chevron-down-outline" style="font-size: 22px;"></ion-icon>
                            </button>
                        </div>

                        {{-- Details Section (Hidden by default) --}}
                        <div id="details-{{ $l->id }}" class="details-section"
                            style="display: none; overflow: hidden;">
                            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #e5e7eb;">
                                {{-- Details Section with 2 Column Layout --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                                    {{-- Left Column --}}
                                    <div>
                                        {{-- Pekerjaan --}}
                                        <div style="margin-bottom: 12px;">
                                            <label
                                                style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                <ion-icon name="briefcase-outline"
                                                    style="color: #16a34a; margin-right: 4px;"></ion-icon>Pekerjaan
                                            </label>
                                            <div style="font-size: 13px; color: #374151; font-weight: 500;">
                                                {{ $l->pekerjaan }}
                                            </div>
                                        </div>

                                        {{-- Tempat --}}
                                        <div style="margin-bottom: 12px;">
                                            <label
                                                style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                <ion-icon name="location-outline"
                                                    style="color: #dc2626; margin-right: 4px;"></ion-icon>Tempat
                                            </label>
                                            <div style="font-size: 13px; color: #374151;">
                                                {{ $l->tempat }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right Column --}}
                                    <div>
                                        {{-- Jam Mulai --}}
                                        <div style="margin-bottom: 12px;">
                                            <label
                                                style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                <ion-icon name="time-outline"
                                                    style="color: #2563eb; margin-right: 4px;"></ion-icon>Jam Mulai
                                            </label>
                                            <div style="font-size: 13px; color: #374151; font-weight: 600;">
                                                {{ $l->jam_mulai ?? 'Belum absen' }}
                                            </div>
                                        </div>

                                        {{-- Jam Selesai --}}
                                        <div style="margin-bottom: 12px;">
                                            <label
                                                style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                <ion-icon name="time-outline"
                                                    style="color: #ca8a04; margin-right: 4px;"></ion-icon>Jam Selesai
                                            </label>
                                            <div style="font-size: 13px; color: #374151; font-weight: 600;">
                                                {{ $l->jam_selesai ?? 'Belum absen' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Keterangan --}}
                                @if (!empty($l->keterangan))
                                    <div
                                        style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                        <label
                                            style="display: block; font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                            <ion-icon name="information-circle-outline"
                                                style="margin-right: 4px;"></ion-icon>Keterangan
                                        </label>
                                        <div style="font-size: 13px; color: #1e40af; line-height: 1.5;">
                                            {{ $l->keterangan }}
                                        </div>
                                    </div>
                                @endif

                                {{-- Foto Section --}}
                                @if (!empty($l->foto_masuk) || !empty($l->foto_keluar))
                                    <div style="margin-bottom: 12px;">
                                        <label
                                            style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">
                                            <ion-icon name="camera-outline"
                                                style="color: #8b5cf6; margin-right: 4px;"></ion-icon>Foto Dokumentasi
                                        </label>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                            {{-- Foto Masuk --}}
                                            @if (!empty($l->foto_masuk))
                                                <div
                                                    style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background-color: #f9fafb;">
                                                    <div
                                                        style="background-color: #f3f4f6; padding: 8px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                                        <small
                                                            style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Absen
                                                            Masuk</small>
                                                    </div>
                                                    <img src="{{ asset('storage/uploads/absensi/' . $l->foto_masuk) }}"
                                                        alt="Foto Masuk"
                                                        style="width: 100%; aspect-ratio: 4/3; object-fit: cover; cursor: pointer;"
                                                        onclick="viewImage(this)"
                                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2275%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%2275%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%236b7280%22 font-size=%2212%22%3EFoto tidak ditemukan%3C/text%3E%3C/svg%3E'">
                                                </div>
                                            @endif

                                            {{-- Foto Keluar --}}
                                            @if (!empty($l->foto_keluar))
                                                <div
                                                    style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background-color: #f9fafb;">
                                                    <div
                                                        style="background-color: #f3f4f6; padding: 8px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                                        <small
                                                            style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Absen
                                                            Keluar</small>
                                                    </div>
                                                    <img src="{{ asset('storage/uploads/absensi/' . $l->foto_keluar) }}"
                                                        alt="Foto Keluar"
                                                        style="width: 100%; aspect-ratio: 4/3; object-fit: cover; cursor: pointer;"
                                                        onclick="viewImage(this)"
                                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2275%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%2275%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%236b7280%22 font-size=%2212%22%3EFoto tidak ditemukan%3C/text%3E%3C/svg%3E'">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Action Buttons (Edit/Delete) --}}
                                @if ($l->status_approved == 0)
                                    <div
                                        style="display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        <a href="{{ route('lembur.edit', $l->id) }}" class="btn btn-sm"
                                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                            <ion-icon name="create-outline" style="font-size: 16px;"></ion-icon> Edit
                                        </a>

                                        <form action="{{ route('lembur.destroy', $l->id) }}" method="POST"
                                            style="display: contents;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm delete-button"
                                                style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                                <ion-icon name="trash-outline" style="font-size: 16px;"></ion-icon> Hapus
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                {{-- Action Buttons --}}
                                @if (empty($l->jam_mulai))
                                    <div
                                        style="display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        <a href="{{ route('lembur.absenMasuk', $l->id) }}" class="btn btn-sm"
                                            style="background-color: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                            <ion-icon name="time-outline" style="font-size: 16px;"></ion-icon> Absen Masuk
                                        </a>
                                    </div>
                                @elseif (empty($l->jam_selesai))
                                    <div
                                        style="display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        <a href="{{ route('lembur.absenKeluar', $l->id) }}" class="btn btn-sm"
                                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                            <ion-icon name="time-outline" style="font-size: 16px;"></ion-icon> Absen
                                            Keluar
                                        </a>
                                    </div>
                                @elseif ($l->status_approved == 0)
                                    <div
                                        style="display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        <a href="{{ route('lembur.absenKeluar', $l->id) }}" class="btn btn-sm"
                                            style="background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                            <ion-icon name="sync-outline" style="font-size: 16px;"></ion-icon> Update
                                            Lembur
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">Belum ada pengajuan lembur pada periode yang dipilih.</div>
            @endforelse
        </div>
    </div>

    {{-- FAB Button --}}
    <div class="fab-button animate bottom-right dropdown" style="margin-bottom:24px">
        <a href="{{ route('lembur.create') }}" class="fab fab-primary" aria-label="add outline">
            <ion-icon name="add-outline" role="img" class="md hydrated"></ion-icon>
        </a>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function viewImage(img) {
            var modal = document.createElement('div');
            modal.style.cssText =
                'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;';
            modal.onclick = function() {
                document.body.removeChild(modal);
            };

            var imgElement = document.createElement('img');
            imgElement.src = img.src;
            imgElement.style.cssText = 'max-width:90%;max-height:90%;border-radius:8px;';
            imgElement.onclick = function(e) {
                e.stopPropagation();
            };

            modal.appendChild(imgElement);
            document.body.appendChild(modal);
        }

        function checkPendingCheckout() {
            @forelse ($lemburs as $l)
                @if (!empty($l->jam_mulai) && empty($l->jam_selesai))
                    Swal.fire({
                        title: 'Jangan Lupa!',
                        html: '<div style="text-align: left; margin: 16px 0;"><p style="font-size: 14px; margin-bottom: 12px;"><strong>Anda masih memiliki lembur yang belum selesai:</strong></p><div style="background-color: #fef3c7; border-left: 4px solid #ca8a04; padding: 12px; border-radius: 6px;"><p style="margin: 0; font-size: 13px; color: #92400e;"><strong>{{ \Carbon\Carbon::parse($l->tanggal_lembur)->format('d M Y') }}</strong></p><p style="margin: 4px 0 0 0; font-size: 12px; color: #92400e;">Jam Masuk: <strong>{{ $l->jam_mulai }}</strong></p><p style="margin: 4px 0 0 0; font-size: 12px; color: #92400e;">Jangan lupa untuk melakukan absen keluar!</p></div></div>',
                        icon: 'warning',
                        confirmButtonColor: '#ca8a04',
                        confirmButtonText: 'Lanjut ke Absen Keluar',
                        showCancelButton: true,
                        cancelButtonText: 'Nanti Dulu'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route('lembur.absenKeluar', $l->id) }}';
                        }
                    });
                    return; // Hanya tampilkan satu notifikasi terbaru
                @endif
            @empty
            @endforelse
        }

        $(function() {
            @if (session('success'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: '{{ session('success') }}',
                    icon: 'success'
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    icon: 'error'
                });
            @endif
            @if (session('info'))
                Swal.fire({
                    title: 'Info',
                    text: '{{ session('info') }}',
                    icon: 'info'
                });
            @endif

            // Auto-expand detail setelah absen masuk/keluar
            @if (session('show_detail'))
                const detailId = {{ session('show_detail') }};
                const $targetDetail = $("#details-" + detailId);
                const $targetButton = $('[data-target="details-' + detailId + '"]');

                if ($targetDetail.length) {
                    // Scroll ke card
                    const $card = $targetDetail.closest('.card');
                    if ($card.length) {
                        setTimeout(function() {
                            $card[0].scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }, 100);
                    }

                    // Expand detail
                    setTimeout(function() {
                        $targetDetail.stop(true, true).slideDown(300, function() {
                            $(this).css("display", "block");
                        });
                        $targetButton.css("transform", "rotate(180deg)");
                        $targetButton.find("ion-icon").attr("name", "chevron-up-outline");
                    }, 200);
                }
            @endif

            // Cek dan tampilkan notifikasi absen keluar jika ada yang pending
            checkPendingCheckout();
            // Expand/Collapse Details
            $(document).on("click", ".expand-toggle", function(e) {
                e.preventDefault();
                const targetId = $(this).data("target");
                const $details = $("#" + targetId);
                const $button = $(this);
                const $icon = $button.find("ion-icon");

                // Toggle state
                const isHidden = $details.is(":hidden");

                if (isHidden) {
                    // Expand
                    $details.stop(true, true).slideDown(300, function() {
                        $(this).css("display", "block");
                    });
                    $button.css("transform", "rotate(180deg)");
                    $icon.attr("name", "chevron-up-outline");
                } else {
                    // Collapse
                    $details.stop(true, true).slideUp(300, function() {
                        $(this).css("display", "none");
                    });
                    $button.css("transform", "rotate(0deg)");
                    $icon.attr("name", "chevron-down-outline");
                }
            });

            // Delete Button confirmation
            $(document).on("click", ".delete-button", function(e) {
                var form = $(this).closest('form');
                e.preventDefault();
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Pengajuan ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                })
            });

            // Auto-submit on month/year change
            $('#bulan, #tahun').change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush
