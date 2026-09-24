@php
    // Label & nada status presensi (lihat .emp-status--*).
    $statusPresensi = [
        'h' => ['Hadir', 'success'],
        'i' => ['Izin', 'info'],
        's' => ['Sakit', 'info'],
        'c' => ['Cuti', 'info'],
        'r' => ['Roster', 'info'],
        'd' => ['Dinas Luar', 'info'],
        'l' => ['Libur', 'neutral'],
        'n' => ['Belum Absen', 'neutral'],
        'a' => ['Alpha', 'danger'],
        'x' => ['Dianulir', 'danger'],
    ];
    $jam = fn ($t) => $t && $t != '00:00:00' ? date('H:i', strtotime($t)) : null;
@endphp

@forelse ($presensi as $d)
    @php
        $path = 'uploads/absensi/';
        $foto_in = !empty($d->foto_in) && $d->foto_in != '-' ? Storage::url($path . $d->foto_in) : null;
        $foto_out = !empty($d->foto_out) && $d->foto_out != '-' ? Storage::url($path . $d->foto_out) : null;
        $doc_sid = !empty($d->doc_sid) && $d->doc_sid != '-' ? Storage::url('uploads/sid/' . $d->doc_sid) : null;

        [$status_label, $nada] = $statusPresensi[$d->status] ?? ['Alpha', 'danger'];
        if ($d->status == 'h' && !empty($d->jam_masuk) && $d->jam_in != '00:00:00' && $d->jam_in > $d->jam_masuk) {
            [$status_label, $nada] = ['Terlambat', 'warning'];
        }

        $jam_masuk_jadwal = $d->jam_masuk ? date('H:i', strtotime($d->jam_masuk)) : '00:00';
        $jam_pulang_jadwal = $d->jam_pulang ? date('H:i', strtotime($d->jam_pulang)) : '00:00';
        $jam_in_display = $jam($d->jam_in);
        $jam_out_display = $jam($d->jam_out);
        $jadwal_info = ($d->nama_jam_kerja ?? 'Shift') . " ($jam_masuk_jadwal - $jam_pulang_jadwal)";
        $jabatanNama = $d->jabatan_nama ?? '-';
    @endphp

    <tr>
        <td class="cell-person">
            <span class="person-name" title="{{ $d->nama_karyawan }}">{{ $d->nama_karyawan }}</span>
            <span class="person-sub">NIK {{ $d->nik }}</span>
        </td>
        <td data-label="Jabatan">
            <div class="cell-main">{{ $jabatanNama }}</div>
            <div class="cell-sub">{{ $d->nama_dept ?? '-' }} · {{ $d->nama_cabang ?? $d->kode_cabang }}</div>
        </td>
        <td data-label="Jadwal" class="cell-num">
            <div class="cell-main">{{ $jam_masuk_jadwal }}–{{ $jam_pulang_jadwal }}</div>
            <div class="cell-sub">{{ $d->nama_jam_kerja ?? 'Shift' }}</div>
        </td>
        <td data-label="Masuk / pulang" class="cell-num">
            @if ($jam_in_display || $jam_out_display)
                <div class="cell-main">{{ $jam_in_display ?? '–' }} <span class="text-secondary">/</span> {{ $jam_out_display ?? '–' }}</div>
            @else
                <span class="text-secondary">–</span>
            @endif
        </td>
        <td data-label="Status">
            <span class="emp-status emp-status--{{ $nada }}">{{ $status_label }}</span>
            @if ($d->kejanggalan)
                <div class="cell-sub text-danger" title="{{ $d->kejanggalan }}">Lokasi janggal</div>
            @endif
        </td>
        <td data-label="Foto">
            <div class="photo-pair">
                @foreach ([[$foto_in, 'Foto masuk'], [$foto_out, 'Foto pulang']] as [$foto, $alt])
                    @if ($foto)
                        <a href="{{ $foto }}" target="_blank" rel="noopener" class="photo-thumb" title="{{ $alt }}">
                            <img src="{{ $foto }}" alt="{{ $alt }} {{ $d->nama_karyawan }}" loading="lazy">
                        </a>
                    @else
                        <span class="photo-thumb is-empty" title="{{ $alt }}: tidak ada">–</span>
                    @endif
                @endforeach
            </div>
        </td>
        <td class="cell-actions">
            <button type="button" class="btn btn-sm btn-detail" data-id="{{ $d->id }}" data-nik="{{ $d->nik }}"
                data-nama="{{ $d->nama_karyawan }}" data-jabatan="{{ $jabatanNama }}" data-dept="{{ $d->nama_dept }}"
                data-cabang="{{ $d->nama_cabang }}" data-jadwal="{{ $jadwal_info }}"
                data-jamin="{{ $jam_in_display ?? '-' }}" data-jamout="{{ $jam_out_display ?? '-' }}"
                data-status="{{ $status_label }}" data-nada="{{ $nada }}" data-kejanggalan="{{ $d->kejanggalan }}"
                data-fotoin="{{ $foto_in }}" data-fotoout="{{ $foto_out }}" data-sid="{{ $doc_sid }}"
                data-lokasivalid="{{ !empty($d->lokasi_in) && $d->lokasi_in != '999,999' ? 1 : 0 }}">
                Detail
            </button>
        </td>
    </tr>
@empty
    <tr class="row-empty">
        <td colspan="7">
            <div class="list-empty">
                <p class="mb-1 fw-medium">Tidak ada data presensi yang cocok.</p>
                <p class="mb-0 text-secondary small">Ubah tanggal, kata kunci, atau filter.</p>
            </div>
        </td>
    </tr>
@endforelse

@if ($presensi->total() > 0)
    <tr class="row-footer">
        <td colspan="7">
            <div class="list-footer">
                <span class="text-secondary small">
                    Menampilkan <strong>{{ $presensi->firstItem() }}–{{ $presensi->lastItem() }}</strong>
                    dari <strong>{{ $presensi->total() }}</strong> karyawan
                </span>
                {{ $presensi->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </td>
    </tr>
@endif
