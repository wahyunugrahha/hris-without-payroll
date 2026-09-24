{{--
    Form filter laporan (cetak / export Excel) bersama.
    $action, $periodeList [nilai => label], $defaultBulan, $defaultTahun, $cabang, $departemen
    Opsional: $forcedKodeCabang, $karyawan (koleksi → pilihan karyawan), $karyawanWajib, $deptWajib, $isi (penjelasan isi laporan)
--}}
@php
    $forcedKodeCabang = $forcedKodeCabang ?? null;
    $karyawan = $karyawan ?? null;
    $karyawanWajib = $karyawanWajib ?? false;
    $deptWajib = $deptWajib ?? false;
@endphp

<div class="container-narrow-lg">
    {{-- GET: hasil cetak punya URL sendiri sehingga aman di-refresh / dibuka ulang. --}}
    <form action="{{ $action }}" method="GET" class="card" id="frmLaporan">
        <div class="card-header">
            <div>
                <h3 class="card-title">Pilih data laporan</h3>
                @isset($isi)
                    <p class="card-subtitle mb-0">{{ $isi }}</p>
                @endisset
            </div>
        </div>
        <div class="card-body">
            @if ($forcedKodeCabang)
                <p class="text-secondary small mb-3">Data otomatis dibatasi untuk cabang Anda.</p>
            @endif
            <div class="form-grid">
                <div>
                    <label class="form-label required" for="bulan">Periode</label>
                    <select name="bulan" id="bulan" class="form-select" required>
                        @foreach ($periodeList as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($defaultBulan == $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label required" for="tahun">Tahun</label>
                    <select name="tahun" id="tahun" class="form-select" required>
                        @for ($t = date('Y'); $t >= date('Y') - 5; $t--)
                            <option value="{{ $t }}" @selected($defaultTahun == $t)>{{ $t }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="form-label" for="kode_cabang">Cabang</label>
                    <select name="kode_cabang" id="kode_cabang" class="form-select" @disabled($forcedKodeCabang)>
                        <option value="">Semua cabang</option>
                        @foreach ($cabang as $c)
                            <option value="{{ $c->kode_cabang }}" @selected($forcedKodeCabang == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                        @endforeach
                    </select>
                    @if ($forcedKodeCabang)
                        <input type="hidden" name="kode_cabang" value="{{ $forcedKodeCabang }}">
                    @endif
                </div>
                <div>
                    <label class="form-label {{ $deptWajib ? 'required' : '' }}" for="kode_dept">Departemen</label>
                    <select name="kode_dept" id="kode_dept" class="form-select" @required($deptWajib)>
                        <option value="">{{ $deptWajib ? 'Pilih departemen' : 'Semua departemen' }}</option>
                        @foreach ($departemen as $d)
                            <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($karyawan)
                    <div class="form-grid-full">
                        <label class="form-label {{ $karyawanWajib ? 'required' : '' }}" for="nik">Karyawan</label>
                        <select name="nik" id="nik" class="form-select" @required($karyawanWajib)>
                            <option value="">{{ $karyawanWajib ? 'Pilih karyawan' : 'Semua karyawan' }}</option>
                            @foreach ($karyawan as $k)
                                <option value="{{ $k->nik }}" data-cabang="{{ $k->kode_cabang }}" data-dept="{{ $k->kode_dept }}">{{ $k->nik }} — {{ $k->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Daftar mengikuti cabang & departemen yang dipilih.</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="submit" name="cetak" value="1" class="btn" formtarget="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                    <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                    <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                </svg>Cetak
            </button>
            <button type="submit" name="exportexcel" value="1" class="btn btn-primary" formtarget="_self">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                    <path d="M7 11l5 5l5 -5" />
                    <path d="M12 4l0 12" />
                </svg>Export Excel
            </button>
        </div>
    </form>
</div>

@if ($karyawan)
    @push('myscript')
        <script>
            $(function() {
                // Pilihan karyawan disaring menurut cabang & departemen.
                const semua = $('#nik option').filter((_, o) => o.value !== '').map((_, o) => ({
                    id: o.value, text: o.text, cabang: String($(o).data('cabang')), dept: String($(o).data('dept'))
                })).get();
                const labelKosong = $('#nik option').first().text();

                function saring() {
                    const cabang = $('#kode_cabang').val();
                    const dept = $('#kode_dept').val();
                    const nilai = $('#nik').val();
                    $('#nik').empty().append(new Option(labelKosong, '', false, false));
                    semua.filter((k) => (!cabang || k.cabang === cabang) && (!dept || k.dept === dept))
                        .forEach((k) => $('#nik').append(new Option(k.text, k.id, false, k.id === nilai)));
                    $('#nik').trigger('change');
                }

                $('#nik').select2({ placeholder: labelKosong, allowClear: true, width: '100%' });
                $('#kode_cabang, #kode_dept').on('change', saring);
                saring();
            });
        </script>
    @endpush
@endif
