@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Edit Pengajuan Dinas Luar</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="{{ route('dinasluars.update', $dinasLuar->id) }}" id="frmdinasluar">
            @csrf
            @method('PUT')
            <div class="ios-form-card">

                <div class="ios-form-group">
                    <label for="alasan" class="ios-label">Keperluan</label>
                    <input type="text" id="alasan" name="alasan" class="ios-input validate" placeholder="Contoh: Kunjungan Klien" value="{{ old('alasan', $dinasLuar->alasan) }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="tgl_mulai" class="ios-label">Dari Tanggal</label>
                    <input type="text" id="tgl_mulai" name="tgl_mulai" class="ios-input datepicker validate" placeholder="YYYY-MM-DD" value="{{ old('tgl_mulai', $dinasLuar->tgl_mulai) }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="tgl_selesai" class="ios-label">Sampai Tanggal</label>
                    <input type="text" id="tgl_selesai" name="tgl_selesai" class="ios-input datepicker validate" placeholder="YYYY-MM-DD" value="{{ old('tgl_selesai', $dinasLuar->tgl_selesai) }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="lokasi_tujuan" class="ios-label">Lokasi Tujuan</label>
                    <input type="text" id="lokasi_tujuan" name="lokasi_tujuan" class="ios-input" placeholder="Contoh: Jakarta Pusat" value="{{ old('lokasi_tujuan', $dinasLuar->lokasi_tujuan) }}">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="dasar_perjalanan" class="ios-label">Dasar Perjalanan</label>
                    <textarea id="dasar_perjalanan" name="dasar_perjalanan" class="ios-textarea validate" rows="3" placeholder="Contoh: Undangan rapat, permintaan klien, surat tugas" required>{{ old('dasar_perjalanan', $dinasLuar->dasar_perjalanan) }}</textarea>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label class="ios-label">Transportasi</label>
                    <div class="ios-radio-group">
                        @php
                            $transportOptions = [ 'darat' => 'Darat', 'laut' => 'Laut', 'udara' => 'Udara' ];
                        @endphp
                        @foreach ($transportOptions as $value => $label)
                            <label class="ios-radio-inline">
                                <input type="radio" name="transportasi" value="{{ $value }}" {{ old('transportasi', $dinasLuar->transportasi) === $value ? 'checked' : '' }} required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="dana_diajukan" class="ios-label">Dana yang Diajukan (Rp)</label>
                    <input type="number" id="dana_diajukan" name="dana_diajukan" class="ios-input validate" min="0" step="5000" placeholder="0" value="{{ old('dana_diajukan', $dinasLuar->dana_diajukan) }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan Tambahan</label>
                    <textarea id="keterangan" name="keterangan" class="ios-textarea" rows="4" placeholder="Masukkan keterangan tambahan">{{ old('keterangan', $dinasLuar->keterangan) }}</textarea>
                </div>

            </div>

            <button class="ios-button-primary" type="submit">PERBARUI PENGAJUAN DINAS LUAR</button>
        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
        function initDatepickers() {
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: 'yyyy-mm-dd',
                autoClose: true
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initDatepickers();
        });

        document.querySelector('.back-button').addEventListener('click', function() {
            window.history.back();
        });
    </script>
@endpush
