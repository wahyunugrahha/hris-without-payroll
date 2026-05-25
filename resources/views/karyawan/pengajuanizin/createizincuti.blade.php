@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    @php
        $submissionType = $submissionType ?? 'cuti';
    @endphp

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">{{ $submissionType === 'roster' ? 'Form Pengajuan Roster' : 'Form Pengajuan Cuti' }}</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    @php
        $isRoster = $submissionType === 'roster';
        $formAction = $isRoster ? route('pengajuanizin.storeizinroster') : route('pengajuanizin.storeizincuti');
    @endphp

    <div class="ios-form-container">
        <form method="POST" action="{{ $formAction }}" id="frmizincuti"
            enctype="multipart/form-data">
            @csrf
            <div class="ios-form-card">

                <input type="hidden" name="status" value="{{ $isRoster ? 'r' : 'c' }}">

                @unless ($isRoster)
                    <div class="ios-form-group" style="padding: 10px; background-color: #e0f7fa; border-radius: 8px;">
                        <label class="ios-label">Sisa Cuti Terpilih</label>
                        <div id="sisa_cuti_info" style="font-size: 1.2rem; font-weight: bold; color: #00796b;">
                            {{ $sisa_cuti ?? 'N/A' }} Hari
                        </div>
                    </div>

                    <div class="ios-divider"></div>

                    <div class="ios-form-group">
                        <label for="kode_cuti" class="ios-label">Jenis Cuti</label>
                        <select name="kode_cuti" id="kode_cuti" class="validate browser-default" required>
                            <option value="" disabled {{ !old('kode_cuti') ? 'selected' : '' }}>Pilih Jenis Cuti</option>
                            @isset($mastercuti)
                                @foreach ($mastercuti as $d)
                                    <option value="{{ $d->kode_cuti }}" {{ old('kode_cuti') == $d->kode_cuti ? 'selected' : '' }}>
                                        {{ $d->nama_cuti }}
                                        @if (isset($sisa_cuti_map[$d->kode_cuti]))
                                            ({{ $sisa_cuti_map[$d->kode_cuti] === null ? 'Tidak Terbatas' : $sisa_cuti_map[$d->kode_cuti] . ' Hari Tersisa' }})
                                        @endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled selected>Data jenis cuti tidak tersedia</option>
                            @endisset
                        </select>
                    </div>

                    <div class="ios-divider"></div>
                @endunless

                <div class="ios-form-group">
                    <label class="ios-label">{{ $isRoster ? 'Pilih Tanggal Roster' : 'Pilih Tanggal Cuti' }}</label>
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <small class="text-muted">Klik tanggal untuk pilih/batalkan</small>
                        <span class="badge bg-primary-lt" id="jml_hari_info">0 Hari</span>
                    </div>
                    <div id="inline_datepicker"></div>
                    <input type="hidden" id="selected_dates" name="selected_dates" value="{{ old('selected_dates') }}">
                    <input type="hidden" id="dari" name="dari" value="{{ old('dari') }}">
                    <input type="hidden" id="sampai" name="sampai" value="{{ old('sampai') }}">
                    <div id="selected_dates_preview" class="mt-2 text-muted" style="font-size: 0.8rem;">
                        Belum ada tanggal dipilih.
                    </div>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="jmlhari" class="ios-label">{{ $isRoster ? 'Jumlah Hari Roster' : 'Jumlah Hari Cuti' }}</label>
                    <input type="text" id="jmlhari" name="jmlhari" class="ios-input" placeholder="Akan terisi otomatis"
                        readonly value="{{ old('jmlhari') }}">
                    <input type="hidden" id="jmlhari_int" name="jmlhari_int" value="{{ old('jmlhari_int') }}">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">{{ $isRoster ? 'Keterangan / Alasan Roster' : 'Keterangan / Alasan Cuti' }}</label>
                    <textarea name="keterangan" id="keterangan" class="ios-textarea validate"
                        placeholder="{{ $isRoster ? 'Tulis detail alasan pengajuan roster' : 'Tulis detail alasan pengajuan cuti' }}" rows="4" required>{{ old('keterangan') }}</textarea>
                </div>

            </div>

            <div class="ios-button-group">
                <button class="ios-button-primary" type="submit" name="action">
                    {{ $isRoster ? 'KIRIM PENGAJUAN ROSTER' : 'KIRIM PENGAJUAN CUTI' }}
                </button>
            </div>

            @if ($errors->any())
                <div class="ios-form-card mt-2">
                    <div class="alert alert-danger" style="color: #9c0000; font-size: 0.9rem;">
                        <ul style="padding-left: 15px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <style>
        .datepicker-inline {
            width: 100% !important;
            border: 1px solid #e6e7e9;
            border-radius: 12px;
            padding: 8px;
            background: #fff;
        }

        .datepicker table {
            width: 100%;
        }

        .datepicker tbody td.day {
            height: 38px;
            width: 38px;
            border: 8px solid #fff !important;
            border-radius: 50% !important;
            line-height: 22px;
            font-weight: 600;
        }

        .datepicker tbody td.active,
        .datepicker tbody td.active:hover {
            background: #206bc4 !important;
            color: #fff !important;
            border-color: #fff !important;
        }

        .datepicker tbody td.day.day-holiday,
        .datepicker tbody td.day.day-holiday:hover {
            background: #ffe3e3 !important;
            color: #b42318 !important;
            border-color: #fff !important;
            text-decoration: line-through;
            opacity: 0.95;
        }
    </style>

    <script>
        const isRoster = @json($isRoster);
        const sisa_cuti_map = @json($sisa_cuti_map ?? []);
        var BLACKLIST_DATES = [];
        var HOLIDAY_DATES = [];

        function fetchBlacklistDates(callback) {
            $.ajax({
                type: 'POST',
                url: '{{ route('pengajuanizin.getblacklistdates') }}',
                data: {
                    _token: '{{ csrf_token() }}',
                    include_holidays: 1
                },
                cache: false,
                success: function(dates) {
                    if (Array.isArray(dates)) {
                        BLACKLIST_DATES = dates;
                        HOLIDAY_DATES = [];
                    } else {
                        BLACKLIST_DATES = dates.blacklist_dates || [];
                        HOLIDAY_DATES = dates.holiday_dates || [];
                    }
                    if (callback) callback();
                }
            });
        }

        function toDateOnlyString(dateObj) {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function toSortableDate(dateStr) {
            return new Date(`${dateStr}T00:00:00`);
        }

        function getSisaCutiLimit() {
            if (isRoster) {
                return null;
            }

            const kode = $('#kode_cuti').val();
            const sisa = sisa_cuti_map[kode];
            if (typeof sisa === 'undefined' || sisa === null) {
                return null;
            }
            return parseInt(sisa, 10);
        }

        function getUniqueSortedSelectedDates() {
            const selectedDateObjs = $('#inline_datepicker').datepicker('getDates') || [];
            const selectedDates = selectedDateObjs
                .map((d) => toDateOnlyString(d))
                .sort((a, b) => toSortableDate(a) - toSortableDate(b));
            return [...new Set(selectedDates)];
        }

        function enforceQuotaOnDateChange(e) {
            const limit = getSisaCutiLimit();
            if (limit === null || Number.isNaN(limit)) {
                return false;
            }

            const pickedDates = (e && Array.isArray(e.dates)) ? e.dates : ($('#inline_datepicker').datepicker('getDates') || []);
            if (pickedDates.length <= limit) {
                return false;
            }

            let nextDates = pickedDates.map((d) => toDateOnlyString(d));
            const changedDate = (e && e.date) ? toDateOnlyString(e.date) : null;

            if (changedDate) {
                let removed = false;
                nextDates = nextDates.filter((d) => {
                    if (!removed && d === changedDate) {
                        removed = true;
                        return false;
                    }
                    return true;
                });
            }

            nextDates = [...new Set(nextDates)]
                .sort((a, b) => toSortableDate(a) - toSortableDate(b))
                .slice(0, limit);

            $('#inline_datepicker').datepicker('setDates', nextDates);

            Swal.fire('Oopss!', `Sisa jatah cuti Anda hanya ${limit} hari.`, 'warning');
            return true;
        }

        function trimSelectionToQuotaIfNeeded(showAlert = false) {
            const limit = getSisaCutiLimit();
            if (limit === null || Number.isNaN(limit)) {
                return false;
            }

            const currentDates = getUniqueSortedSelectedDates();
            if (currentDates.length <= limit) {
                return false;
            }

            $('#inline_datepicker').datepicker('setDates', currentDates.slice(0, limit));
            if (showAlert) {
                Swal.fire('Info', `Tanggal dipotong ke ${limit} hari sesuai sisa cuti.`, 'info');
            }
            return true;
        }

        function initializeInlineDatepicker() {
            $('#inline_datepicker').datepicker('destroy');
            const batasMundur = new Date();
            batasMundur.setDate(batasMundur.getDate() - 30);
            $('#inline_datepicker').datepicker({
                format: "yyyy-mm-dd",
                multidate: true,
                todayHighlight: true,
                startDate: batasMundur,
                beforeShowDay: function(date) {
                    const dateStr = toDateOnlyString(date);
                    const isHoliday = HOLIDAY_DATES.includes(dateStr);
                    const isBlocked = BLACKLIST_DATES.includes(dateStr);

                    if (isHoliday) {
                        return {
                            enabled: false,
                            classes: 'day-holiday',
                            tooltip: 'Hari Libur'
                        };
                    }

                    if (isBlocked) {
                        return false;
                    }

                    return true;
                }
            });

            const oldSelectedDatesRaw = $('#selected_dates').val();
            if (oldSelectedDatesRaw) {
                const dates = oldSelectedDatesRaw.split(',').map((s) => s.trim()).filter(Boolean);
                if (dates.length > 0) {
                    $('#inline_datepicker').datepicker('setDates', dates);
                }
            }

            $('#inline_datepicker').off('changeDate').on('changeDate', function(e) {
                enforceQuotaOnDateChange(e);
                updateSelectedDatesAndCounts();
            });

            trimSelectionToQuotaIfNeeded(false);
            updateSelectedDatesAndCounts();
        }

        function updateSelectedDatesAndCounts() {
            const uniqueDates = getUniqueSortedSelectedDates();
            const count = uniqueDates.length;

            $('#selected_dates').val(uniqueDates.join(','));
            $('#jmlhari_int').val(count);
            $('#jmlhari').val(count > 0 ? `${count} Hari` : '');
            $('#jml_hari_info').text(`${count} Hari`);

            if (count > 0) {
                $('#dari').val(uniqueDates[0]);
                $('#sampai').val(uniqueDates[count - 1]);
                $('#selected_dates_preview').text(`Tanggal dipilih: ${uniqueDates.join(', ')}`);
            } else {
                $('#dari').val('');
                $('#sampai').val('');
                $('#selected_dates_preview').text('Belum ada tanggal dipilih.');
            }

            return count;
        }

        function updateSisaInfoFor(selectedKode) {
            if (isRoster) {
                return;
            }

            let sisa = sisa_cuti_map[selectedKode];
            let text;
            if (typeof sisa === 'undefined' || sisa === null) {
                text = 'Tidak Terbatas';
            } else {
                text = sisa + ' Hari Tersisa';
            }
            $('#sisa_cuti_info').text(text);
        }

        $(document).ready(function() {
            fetchBlacklistDates(initializeInlineDatepicker);
            if (!isRoster) {
                $('select').formSelect();
            }
            updateSelectedDatesAndCounts();

            if (!isRoster) {
                $('#kode_cuti').change(function() {
                    var selected = $(this).val();
                    updateSisaInfoFor(selected);
                    trimSelectionToQuotaIfNeeded(true);
                    updateSelectedDatesAndCounts();
                });

                var initialKode = $('#kode_cuti').val();
                if (initialKode) {
                    updateSisaInfoFor(initialKode);
                } else {
                    updateSisaInfoFor('{{ $kodeCutiTahunan }}');
                }
            }

            $("#frmizincuti").submit(function(e) {
                var selectedDates = $("#selected_dates").val();
                var kode_cuti = $("#kode_cuti").val();
                var jmlhari_val = updateSelectedDatesAndCounts();
                var keterangan = $("#keterangan").val();

                if (selectedDates == "") {
                    Swal.fire('Oopss!', 'Pilih minimal satu tanggal cuti', 'warning');
                    e.preventDefault();
                    return false;
                }

                if (jmlhari_val <= 0) {
                    Swal.fire('Oopss!', 'Perhitungan Jumlah Hari Cuti Tidak Valid', 'warning');
                    e.preventDefault();
                    return false;
                }

                if (!isRoster && (kode_cuti == null || kode_cuti == "")) {
                    Swal.fire('Oopss!', 'Jenis Cuti Harus Dipilih', 'warning');
                    e.preventDefault();
                    return false;
                }

                if (keterangan == "") {
                    Swal.fire('Oopss!', 'Keterangan Harus Diisi', 'warning');
                    e.preventDefault();
                    return false;
                }

                let sisa = sisa_cuti_map[kode_cuti];
                if (!isRoster && typeof sisa !== 'undefined' && sisa !== null && jmlhari_val > sisa) {
                    Swal.fire('Oopss!',
                        `Sisa jatah cuti Anda (${sisa} hari) tidak mencukupi untuk ${jmlhari_val} hari.`,
                        'error');
                    e.preventDefault();
                    return false;
                }

            });
        });
    </script>
@endpush
