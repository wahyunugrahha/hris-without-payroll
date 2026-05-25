@forelse ($presensi as $d)
    @php
        $path = 'uploads/absensi/';
        $foto_in = !empty($d->foto_in) && $d->foto_in != '-' ? Storage::url($path . $d->foto_in) : null;
        $foto_out = !empty($d->foto_out) && $d->foto_out != '-' ? Storage::url($path . $d->foto_out) : null;
        $doc_sid = !empty($d->doc_sid) && $d->doc_sid != '-' ? Storage::url('uploads/sid/' . $d->doc_sid) : null;

        // --- Logic Warna Status ---
        $status_label = 'Alpha';
        $status_color = 'bg-danger';

        if ($d->status == 'h') {
            $status_label = 'Hadir';
            $status_color = 'bg-success';
            if (!empty($d->jam_masuk) && $d->jam_in != '00:00:00' && $d->jam_in > $d->jam_masuk) {
                $status_label = 'Terlambat';
                $status_color = 'bg-warning';
            }
        } elseif ($d->status == 'i') {
            $status_label = 'Izin';
            $status_color = 'bg-info';
        } elseif ($d->status == 's') {
            $status_label = 'Sakit';
            $status_color = 'bg-warning';
        } elseif ($d->status == 'c') {
            $status_label = 'Cuti';
            $status_color = 'bg-primary';
        } elseif ($d->status == 'r') {
            $status_label = 'Roster';
            $status_color = 'bg-cyan text-white';
        } elseif ($d->status == 'd') {
            $status_label = 'Dinas Luar';
            $status_color = 'bg-info';
        } elseif ($d->status == 'l') {
            $status_label = 'Libur';
            $status_color = 'bg-secondary';
        } elseif ($d->status == 'n') {
            $status_label = 'Belum Absen';
            $status_color = 'bg-orange text-white';
        } elseif ($d->status == 'a') {
            $status_label = 'Alpha';
            $status_color = 'bg-danger';
        } elseif ($d->status == 'x') {
            $status_label = 'Dianulir';
            $status_color = 'bg-dark';
        }

        // --- Format Jam ---
        $jam_masuk_jadwal = $d->jam_masuk ? date('H:i', strtotime($d->jam_masuk)) : '00:00';
        $jam_pulang_jadwal = $d->jam_pulang ? date('H:i', strtotime($d->jam_pulang)) : '00:00';

        $jam_in_display = $d->jam_in && $d->jam_in != '00:00:00' ? date('H:i', strtotime($d->jam_in)) : null;
        $jam_out_display = $d->jam_out && $d->jam_out != '00:00:00' ? date('H:i', strtotime($d->jam_out)) : null;

        $jadwal_info = ($d->nama_jam_kerja ?? 'Shift') . " ($jam_masuk_jadwal - $jam_pulang_jadwal)";

        // Style kotak foto (Border disesuaikan agar terlihat di darkmode)
        $box_style =
            'width: 32px; height: 32px; border: 1px solid #e6e7e9; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: transparent; color: inherit;';
    @endphp

    <tr class="align-middle">
        {{-- NO --}}
        <td>{{ $loop->iteration + $presensi->firstItem() - 1 }}</td>

        {{-- NAMA / NIK --}}
        <td>
            {{-- PERBAIKAN: Hapus 'text-dark' agar warna mengikuti tema (putih saat darkmode) --}}
            @php
                $jabatanNama = $d->jabatan_nama ?? '-';
                $deptNama = $d->nama_dept ?? '-';
            @endphp
            <div class="fw-bold" style="font-size: 13px;">{{ $d->nama_karyawan }}</div>
            <div class="text-muted small" style="font-size: 12px;">{{ $d->nik }}</div>
        </td>

        {{-- DEPARTEMEN / JABATAN --}}
        <td>
            <div class="text-muted small" style="font-size: 12px;">{{ $deptNama }}</div>
            <div class="text-muted small" style="font-size: 12px;">{{ $jabatanNama }}</div>
        </td>

        {{-- CABANG --}}
        <td style="font-size: 13px;">
            {{ $d->nama_cabang ?? $d->kode_cabang }}
        </td>

        {{-- JADWAL SHIFT --}}
        <td class="text-center" style="font-size: 13px;">
            {{ $jam_masuk_jadwal }} - {{ $jam_pulang_jadwal }}
        </td>

        {{-- JAM (M/S) - BERTUMPUK --}}
        <td class="text-center" style="font-size: 13px;">
            @if ($jam_in_display || $jam_out_display)
                <div class="d-flex flex-column align-items-center" style="line-height: 1.2;">
                    <span class="text-success fw-bold">{{ $jam_in_display ?? '-' }}</span>
                    <span class="text-danger fw-bold">{{ $jam_out_display ?? '-' }}</span>
                </div>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        {{-- STATUS --}}
        <td class="text-center">
            <span class="badge {{ $status_color }} text-white" style="font-size: 11px; padding: 4px 8px;">
                {{ $status_label }}
            </span>
        </td>

        {{-- FOTO --}}
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                {{-- Foto Masuk --}}
                @if ($foto_in)
                    <a href="{{ $foto_in }}" target="_blank"
                        style="{{ $box_style }} overflow: hidden; border-color: #206bc4;">
                        <img src="{{ $foto_in }}" style="width:100%; height:100%; object-fit:cover;">
                    </a>
                @else
                    <div style="{{ $box_style }}" title="Foto Masuk">-</div>
                @endif

                {{-- Foto Pulang --}}
                @if ($foto_out)
                    <a href="{{ $foto_out }}" target="_blank"
                        style="{{ $box_style }} overflow: hidden; border-color: #d63939;">
                        <img src="{{ $foto_out }}" style="width:100%; height:100%; object-fit:cover;">
                    </a>
                @else
                    <div style="{{ $box_style }}" title="Foto Pulang">-</div>
                @endif
            </div>
        </td>

        {{-- AKSI --}}
        <td class="text-center">
            <button class="btn btn-outline-success btn-sm btn-detail" style="padding: 2px 10px; font-size: 12px;"
                data-id="{{ $d->id }}" data-nik="{{ $d->nik }}" data-nama="{{ $d->nama_karyawan }}"
                data-jabatan="{{ $jabatanNama }}" data-dept="{{ $d->nama_dept }}"
                data-cabang="{{ $d->nama_cabang }}" data-jadwal="{{ $jadwal_info }}"
                data-jamin="{{ $jam_in_display ?? '-' }}" data-jamout="{{ $jam_out_display ?? '-' }}"
                data-status="{{ $status_label }}" data-warnastatus="{{ $status_color }}"
                data-fotoin="{{ $foto_in }}" data-fotoout="{{ $foto_out }}" data-sid="{{ $doc_sid }}"
                data-lokasivalid="{{ !empty($d->lokasi_in) && $d->lokasi_in != '999,999' ? 1 : 0 }}">
                Detail
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center py-5">
            <div class="empty">
                <div class="empty-img">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database-off"
                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M12.983 8.978c3.955 -.182 7.017 -1.446 7.017 -2.978c0 -1.657 -3.582 -3 -8 -3c-1.661 0 -3.204 .19 -4.483 .515m-2.783 1.228c-.471 .382 -.734 .808 -.734 1.257c0 1.22 1.944 2.271 4.734 2.74" />
                        <path
                            d="M4 6v6c0 1.657 3.582 3 8 3c.986 0 1.93 -.067 2.802 -.19m3.187 -.82c1.251 -.53 2.011 -1.228 2.011 -1.99v-6" />
                        <path d="M4 12v6c0 1.657 3.582 3 8 3c3.217 0 5.991 -.712 7.261 -1.74m.739 -3.26v-4" />
                        <line x1="3" y1="3" x2="21" y2="21" />
                    </svg>
                </div>
                <p class="empty-title">Data tidak ditemukan</p>
            </div>
        </td>
    </tr>
@endforelse

{{-- PAGINATION --}}
<tr>
    <td colspan="9">
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="text-muted small">
                Menampilkan {{ $presensi->firstItem() ?? 0 }} - {{ $presensi->lastItem() ?? 0 }} dari
                {{ $presensi->total() }} data
            </div>
            <div>
                {{ $presensi->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </td>
</tr>
