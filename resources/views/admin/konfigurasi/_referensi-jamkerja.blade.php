{{-- Kartu referensi master jam kerja (samping form jadwal). --}}
<section class="card" aria-labelledby="judul-referensi-jk">
    <div class="card-header">
        <h3 class="card-title" id="judul-referensi-jk">Referensi jam kerja</h3>
        <span class="card-subtitle ms-auto">{{ $jamkerja->count() }} shift</span>
    </div>
    <div class="table-responsive reference-scroll">
        <table class="table table-sm table-vcenter card-table">
            <thead>
                <tr>
                    <th>Shift</th>
                    <th>Absen masuk</th>
                    <th>Kerja</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jamkerja as $d)
                    <tr>
                        <td>
                            <div class="cell-main">{{ $d->nama_jam_kerja }}</div>
                            <div class="cell-sub">{{ $d->kode_jam_kerja }}</div>
                        </td>
                        <td class="cell-num text-secondary text-nowrap">{{ substr($d->awal_jam_masuk, 0, 5) }}–{{ substr($d->akhir_jam_masuk, 0, 5) }}</td>
                        <td class="cell-num text-nowrap">{{ substr($d->jam_masuk, 0, 5) }}–{{ substr($d->jam_pulang, 0, 5) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
