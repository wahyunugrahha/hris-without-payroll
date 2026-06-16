@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Pengajuan Kenaikan Gaji</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="section mt-2" id="salary-increase-section" style="margin-bottom: 100px;">
        @if(Session::get('success'))
            <div class="alert alert-success" style="border: 1px solid #dcecf9; border-radius: 10px; background: #eef6fd;">
                <ion-icon name="checkmark-circle-outline" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
                {{ Session::get('success') }}
            </div>
        @endif

        @if(Session::get('error'))
            <div class="alert alert-danger" style="border: 1px solid #fca5a5; border-radius: 10px; background: #fef2f2;">
                <ion-icon name="alert-circle-outline" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
                {{ Session::get('error') }}
            </div>
        @endif

        <div class="card"
            style="margin-bottom: 12px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #dbeafe;">
            <div class="card-body" style="padding: 14px;">
                <div style="font-weight: 700; font-size: 14px; color: #1f2937; margin-bottom: 10px;">
                    Persyaratan Kenaikan Gaji
                </div>

                <div class="requirements-checklist" style="max-height: 420px; overflow-y: auto; padding: 2px;">
                    <div class="requirement-card p-3 mb-2"
                        style="border: 1px solid {{ $syaratPoin ? '#bbf7d0' : '#fecaca' }}; background: {{ $syaratPoin ? '#eef6fd' : '#fef2f2' }}; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center">
                            <ion-icon name="stats-chart-outline"
                                style="font-size: 20px; margin-right: 10px; color: {{ $syaratPoin ? '#094b87' : '#dc2626' }};"></ion-icon>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1f2937;">Rata-rata Poin (Min.
                                    {{ $targetPoin }} selama {{ $limitBulan }} Bulan)</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Rata-rata Anda: <strong style="color: #0f172a;">{{ number_format($rataPoin, 2) }}</strong>
                                    (Terdata: <strong>{{ $rekapTerakhir->count() }} / {{ $limitBulan }} Bulan</strong>)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="requirement-card p-3 mb-2"
                        style="border: 1px solid {{ $syaratKpi ? '#bbf7d0' : '#fecaca' }}; background: {{ $syaratKpi ? '#eef6fd' : '#fef2f2' }}; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center">
                            <ion-icon name="flash-outline"
                                style="font-size: 20px; margin-right: 10px; color: {{ $syaratKpi ? '#094b87' : '#dc2626' }};"></ion-icon>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1f2937;">Rata-rata KPI (Min. 80%
                                    selama {{ $limitBulan }} Bulan)</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Rata-rata KPI Anda: <strong
                                        style="color: #0f172a;">{{ number_format($rataKpi, 2) }}%</strong>
                                    (Terdata: <strong>{{ $rekapTerakhir->count() }} / {{ $limitBulan }} Bulan</strong>)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="requirement-card p-3 mb-2"
                        style="border: 1px solid {{ $syaratIzinSakit ? '#bbf7d0' : '#fecaca' }}; background: {{ $syaratIzinSakit ? '#eef6fd' : '#fef2f2' }}; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center">
                            <ion-icon name="medkit-outline"
                                style="font-size: 20px; margin-right: 10px; color: {{ $syaratIzinSakit ? '#094b87' : '#dc2626' }};"></ion-icon>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1f2937;">Izin/Sakit (Maks. 6 hari
                                    per bulan)</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Status:
                                    <strong>{{ $rekapTerakhir->count() < $limitBulan ? 'Masa penilaian belum mencapai ' . $limitBulan . ' bulan' : 'Terpenuhi (Tidak ada bulan melebihi 6 hari)' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="requirement-card p-3 mb-2"
                        style="border: 1px solid {{ $syaratSp ? '#bbf7d0' : '#fecaca' }}; background: {{ $syaratSp ? '#eef6fd' : '#fef2f2' }}; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center">
                            <ion-icon name="shield-checkmark-outline"
                                style="font-size: 20px; margin-right: 10px; color: {{ $syaratSp ? '#094b87' : '#dc2626' }};"></ion-icon>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1f2937;">Surat Peringatan (SP)
                                </div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Status: <strong>{{ $syaratSp ? 'Bersih (Tidak memiliki SP aktif)' : 'Memiliki SP yang masih aktif' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="requirement-card p-3 mb-3"
                        style="border: 1px solid {{ $syaratJeda ? '#bbf7d0' : '#fecaca' }}; background: {{ $syaratJeda ? '#eef6fd' : '#fef2f2' }}; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div class="d-flex align-items-center">
                            <ion-icon name="time-outline"
                                style="font-size: 20px; margin-right: 10px; color: {{ $syaratJeda ? '#094b87' : '#dc2626' }};"></ion-icon>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1f2937;">Jeda Pengajuan (Min.
                                    {{ $limitBulan }} Bulan)</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Status:
                                    <strong>{{ $syaratJeda ? 'Terpenuhi / Belum pernah mengajukan' : 'Belum mencapai jeda ' . $limitBulan . ' bulan dari pengajuan terakhir' }}</strong>
                                    @if($pengajuanTerakhir)
                                        <br><span
                                            style="font-size: 11px; color: #94a3b8; display: inline-block; margin-top: 4px;">Terakhir
                                            diajukan: {{ date('d M Y', strtotime($pengajuanTerakhir->tanggal_pengajuan)) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($pengajuanPending)
                    <div class="alert alert-warning text-center mt-2" style="border: 1px solid #fcd34d; background: #fffbeb; border-radius: 10px;">
                        Anda sudah memiliki pengajuan kenaikan gaji yang sedang diproses oleh HRD.
                    </div>
                @else
                    <form action="{{ route('karyawan.kenaikan_gaji.store') }}" method="POST">
                        @csrf
                        <div class="form-group basic">
                            <div class="input-wrapper">
                                <label class="label" style="font-size: 12px; color: #6b7280; font-weight: 700;">Catatan
                                    Pengajuan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="3" placeholder="Alasan tambahan..." {{ !$isEligible ? 'disabled' : '' }}></textarea>
                            </div>
                        </div>

                        <div class="form-group basic mt-2">
                            @if($isEligible)
                                <div class="alert alert-info"
                                    style="border: 1px solid #93c5fd; background: #eff6ff; border-radius: 10px; font-size: 12px; margin-bottom: 10px;">
                                    Berdasarkan histori Anda, persentase kenaikan gaji yang diusulkan adalah
                                    <b>{{ $persentase }}%</b>.
                                </div>
                                <button type="submit" class="btn btn-block btn-lg"
                                    style="background-color: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; border-radius: 8px; font-weight: 700;">
                                    <ion-icon name="send-outline" style="margin-right: 4px;"></ion-icon>
                                    Ajukan Kenaikan Gaji
                                </button>
                            @else
                                <button type="button" class="btn btn-block btn-lg" disabled
                                    style="background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 700;">
                                    Persyaratan Belum Terpenuhi
                                </button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card"
            style="margin-bottom: 12px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #dbeafe;">
            <div class="card-body" style="padding: 14px;">
                <div style="font-weight: 700; font-size: 14px; color: #1f2937; margin-bottom: 10px;">
                    Histori Pengajuan
                </div>

                <div class="history-list">
                    @forelse($historiPengajuan as $h)
                        <div class="card"
                            style="margin-bottom: 10px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); border: 1px solid @if($h->status == 'approved') #bbf7d0 @elseif($h->status == 'rejected') #fecaca @else #fde68a @endif; background: @if($h->status == 'approved') #eef6fd @elseif($h->status == 'rejected') #fef2f2 @else #fffbeb @endif;">
                            <div class="card-body" style="padding: 12px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span style="font-size: 12px; font-weight: 700; color: #6b7280;">
                                        Diajukan: {{ date('d M Y', strtotime($h->tanggal_pengajuan)) }}
                                    </span>
                                    @if($h->status == 'pending')
                                        <span class="badge bg-warning" style="font-size: 11px;">Pending</span>
                                    @elseif($h->status == 'approved')
                                        <span class="badge bg-success" style="font-size: 11px;">Approved</span>
                                    @elseif($h->status == 'rejected')
                                        <span class="badge bg-danger" style="font-size: 11px;">Rejected</span>
                                    @endif
                                </div>

                                <div style="font-size: 13px; color: #1f2937; font-weight: 700;">
                                    Usulan Kenaikan: <span style="color: #094b87;">{{ $h->persentase }}%</span>
                                </div>

                                @if($h->catatan)
                                    <div
                                        style="font-size: 12px; color: #475569; margin-top: 6px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                        <b>Catatan:</b> {{ $h->catatan }}
                                    </div>
                                @endif

                                @if($h->approved_at)
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 6px; text-align: right;">
                                        Diproses oleh {{ $h->approved_by }} pada
                                        {{ date('d M Y H:i', strtotime($h->approved_at)) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 22px 16px; text-align: center;">
                            <ion-icon name="document-text-outline"
                                style="font-size: 34px; color: #94a3b8; margin-bottom: 8px;"></ion-icon>
                            <div style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                                Belum Ada Pengajuan
                            </div>
                            <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                                Riwayat pengajuan kenaikan gaji Anda akan tampil di sini setelah melakukan pengajuan pertama.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
