@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Data Dinas Luar</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    {{-- Alerts --}}
    <div class="row" style="margin-top: 24px;">
        <div class="col">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning">{{ session('error') }}</div>
            @endif
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="row">
        <div class="col">
            <form method="GET" action="{{ route('dinasluars.index') }}">
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
            @forelse ($dinasluars as $d)
                @php
                    $tglMulai = new DateTime($d->tgl_mulai ?? date('Y-m-d'));
                    $tglSelesai = new DateTime($d->tgl_selesai ?? $d->tgl_mulai ?? date('Y-m-d'));
                    $totalHari = $tglMulai->diff($tglSelesai)->days + 1;

                    $statusBadgeClass = 'bg-warning';
                    $statusText = 'Menunggu';
                    if ($d->status_acc === 'acc') {
                        $statusBadgeClass = 'bg-success';
                        $statusText = 'Disetujui';
                    } elseif ($d->status_acc === 'tolak') {
                        $statusBadgeClass = 'bg-danger';
                        $statusText = 'Ditolak';
                    }
                @endphp

                <div class="card" style="margin-bottom: 12px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid 
                    @if ($d->status_acc === 'acc') #e8f2fb @elseif ($d->status_acc === 'tolak') #fee2e2 @else #fef3c7 @endif;">
                    <div class="card-body" style="padding: 14px;">
                        {{-- Header Section with Date, Status, Duration and Expand Button --}}
                        <div style="display: grid; grid-template-columns: 2fr 1.2fr 0.8fr 0.4fr; gap: 12px; margin-bottom: 0; align-items: center; justify-items: start;">
                            {{-- Date Column --}}
                            <div style="text-align: left; width: 100%;">
                                <div style="font-weight: 700; font-size: 13px; color: #1f2937; display: flex; align-items: flex-start; gap: 6px;">
                                    <ion-icon name="calendar-outline" style="color: #094b87; font-size: 16px; flex-shrink: 0; margin-top: 1px;"></ion-icon>
                                    <span style="line-height: 1.4;">
                                        @if ($d->tgl_mulai == $d->tgl_selesai)
                                            {{ date('d M Y', strtotime($d->tgl_mulai)) }}
                                        @else
                                            <span>{{ date('d M Y', strtotime($d->tgl_mulai)) }}</span><br>
                                            <span style="font-size: 12px; color: #6b7280; font-weight: 500;">—</span> <span>{{ date('d M Y', strtotime($d->tgl_selesai)) }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Status Column --}}
                            <div style="text-align: center; width: 100%; display: flex; justify-content: center; align-items: flex-start;">
                                <span class="badge {{ $statusBadgeClass }}" style="padding: 6px 12px; font-size: 11px; font-weight: 700; display: inline-block; border-radius: 6px; white-space: nowrap;">
                                    {{ $statusText }}
                                </span>
                            </div>
                            
                            {{-- Duration Column --}}
                            <div style="text-align: center; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 2px;">
                                <div style="font-size: 18px; font-weight: 700; color: #1f2937; line-height: 1;">
                                    {{ $totalHari }}
                                </div>
                                <small style="color: #6b7280; font-size: 10px; font-weight: 500; margin-top: 2px;">Hari</small>
                            </div>

                            {{-- Expand/Collapse Button --}}
                            <button class="expand-toggle" data-target="details-{{ $d->id }}" style="background: none; border: none; padding: 6px; cursor: pointer; display: flex; align-items: flex-start; justify-content: center; color: #9ca3af; transition: transform 0.3s ease; width: 100%; justify-self: end; margin-top: 2px;">
                                <ion-icon name="chevron-down-outline" style="font-size: 22px;"></ion-icon>
                            </button>
                        </div>

                        {{-- Details Section (Hidden by default) --}}
                        <div id="details-{{ $d->id }}" class="details-section" style="display: none; overflow: hidden;">
                            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #e5e7eb;">
                                {{-- Details Section with 2 Column Layout --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                                    {{-- Left Column --}}
                                    <div>
                                        {{-- Alasan --}}
                                        <div style="margin-bottom: 12px;">
                                            <label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                <ion-icon name="checkmark-circle-outline" style="color: #094b87; margin-right: 4px;"></ion-icon>Alasan
                                            </label>
                                            <div style="font-size: 13px; color: #374151; font-weight: 500;">
                                                {{ $d->alasan }}
                                            </div>
                                        </div>

                                        {{-- Lokasi Tujuan --}}
                                        @if (!empty($d->lokasi_tujuan))
                                            <div style="margin-bottom: 12px;">
                                                <label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                    <ion-icon name="location-outline" style="color: #dc2626; margin-right: 4px;"></ion-icon>Lokasi Tujuan
                                                </label>
                                                <div style="font-size: 13px; color: #374151;">
                                                    {{ $d->lokasi_tujuan }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Dasar Perjalanan --}}
                                        @if (!empty($d->dasar_perjalanan))
                                            <div style="margin-bottom: 12px;">
                                                <label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                    <ion-icon name="document-outline" style="color: #7c3aed; margin-right: 4px;"></ion-icon>Dasar Perjalanan
                                                </label>
                                                <div style="font-size: 13px; color: #374151; line-height: 1.5;">
                                                    {{ $d->dasar_perjalanan }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Right Column --}}
                                    <div>
                                        {{-- Transportasi --}}
                                        @if (!empty($d->transportasi))
                                            <div style="margin-bottom: 12px;">
                                                <label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                    <ion-icon name="bus-outline" style="color: #2563eb; margin-right: 4px;"></ion-icon>Transportasi
                                                </label>
                                                <div style="font-size: 13px; color: #374151;">
                                                    {{ ucfirst($d->transportasi) }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Dana Diajukan --}}
                                        @if (!empty($d->dana_diajukan))
                                            <div style="margin-bottom: 12px;">
                                                <label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                    <ion-icon name="wallet-outline" style="color: #ca8a04; margin-right: 4px;"></ion-icon>Dana Diajukan
                                                </label>
                                                <div style="font-size: 13px; color: #374151; font-weight: 600;">
                                                    Rp {{ number_format($d->dana_diajukan, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Keterangan Tambahan --}}
                                @if (!empty($d->keterangan))
                                    <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                        <label style="display: block; font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                            <ion-icon name="information-circle-outline" style="margin-right: 4px;"></ion-icon>Keterangan
                                        </label>
                                        <div style="font-size: 13px; color: #1e40af; line-height: 1.5;">
                                            {{ $d->keterangan }}
                                        </div>
                                    </div>
                                @endif

                                {{-- Catatan Approval --}}
                                @if (!empty($d->catatan_approval))
                                    <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                        <label style="display: block; font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                            <ion-icon name="alert-circle-outline" style="margin-right: 4px;"></ion-icon>Catatan Persetujuan
                                        </label>
                                        <div style="font-size: 13px; color: #92400e; line-height: 1.5;">
                                            {{ $d->catatan_approval }}
                                        </div>
                                    </div>
                                @endif

                                {{-- Action Buttons --}}
                                @if ($d->status_acc !== 'acc')
                                    <div style="display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        {{-- Edit Button --}}
                                        <a href="{{ route('dinasluars.edit', $d->id) }}" 
                                            class="btn btn-sm"
                                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                            <ion-icon name="create-outline" style="font-size: 16px;"></ion-icon> Edit
                                        </a>

                                        {{-- Delete Button --}}
                                        <form action="/dinasluars/{{ $d->id }}/delete"
                                            method="POST" style="display: contents;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm delete-button"
                                                style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; font-size: 12px; padding: 8px 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-grow: 1; justify-content: center;">
                                                <ion-icon name="trash-outline" style="font-size: 16px;"></ion-icon> Hapus
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">Belum ada pengajuan Dinas Luar pada periode yang dipilih.</div>
            @endforelse
        </div>
    </div>

    {{-- FAB Button --}}
    <div class="fab-button animate bottom-right dropdown" style="margin-bottom:24px">
        <a href="{{ route('dinasluars.create') }}" class="fab fab-primary" aria-label="add outline">
            <ion-icon name="add-outline" role="img" class="md hydrated"></ion-icon>
        </a>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
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

            // Delete Button
            $(document).on("click", ".delete-button", function(e) {
                var form = $(this).closest('form');
                e.preventDefault();
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Pengajuan ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-accent)',
                    cancelButtonColor: 'var(--color-muted)',
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
