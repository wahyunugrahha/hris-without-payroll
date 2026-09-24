{{--
    Jadwal mingguan (Senin–Minggu) untuk jam kerja departemen & karyawan.
    $jamkerja   : master jam kerja
    $terpilih   : ['Senin' => 'JK01' | 'LIBUR' | '' , ...]
    $lihat      : true = hanya tampil (tanpa select)
    $opsiKosong : label opsi kosong (mis. "Ikut jadwal departemen"); null = wajib pilih
--}}
@php
    $lihat = $lihat ?? false;
    $opsiKosong = $opsiKosong ?? null;
    $jamPer = $jamkerja->keyBy('kode_jam_kerja');
    $fmt = fn ($jk) => $jk->nama_jam_kerja . ' · ' . substr($jk->jam_masuk, 0, 5) . '–' . substr($jk->jam_pulang, 0, 5);
@endphp
<ul class="schedule-list">
    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)
        @php $kode = $terpilih[$hari] ?? ''; @endphp
        <li class="schedule-row">
            <label class="schedule-day" for="jadwal-{{ $hari }}">{{ $hari }}</label>
            @if ($lihat)
                <div class="schedule-value">
                    @if ($kode === 'LIBUR' || $kode === '')
                        <span class="emp-status emp-status--warning">{{ $kode === '' && $opsiKosong ? $opsiKosong : 'Libur' }}</span>
                    @elseif ($jamPer->has($kode))
                        <span class="cell-num">{{ $fmt($jamPer[$kode]) }}</span>
                    @else
                        <span class="text-secondary">{{ $kode }}</span>
                    @endif
                </div>
            @else
                <input type="hidden" name="hari[]" value="{{ $hari }}">
                <select name="kode_jam_kerja[]" id="jadwal-{{ $hari }}" class="form-select">
                    <option value="" @selected($kode === '')>{{ $opsiKosong ?? 'Pilih jam kerja' }}</option>
                    <option value="LIBUR" @selected($kode === 'LIBUR')>Libur</option>
                    @foreach ($jamkerja as $jk)
                        <option value="{{ $jk->kode_jam_kerja }}" @selected($kode === $jk->kode_jam_kerja)>{{ $fmt($jk) }}</option>
                    @endforeach
                </select>
            @endif
        </li>
    @endforeach
</ul>
