@extends('layouts.admin.tabler')

@php
    $jumlahAktif = $lokasis->where('aktif', 1)->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master / Kantor Cabang</div>
                    <h2 class="page-title">{{ $cabang->nama_cabang }}</h2>
                    <p class="page-subtitle">Titik lokasi absen tambahan · Kode {{ $cabang->kode_cabang }}</p>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('cabang.index') }}" class="btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                            stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12l14 0" />
                            <path d="M5 12l6 6" />
                            <path d="M5 12l6 -6" />
                        </svg>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-3">
                <div class="col-lg-7">
                    <section class="card h-100" aria-labelledby="judul-peta">
                        <div class="card-header">
                            <h3 class="card-title" id="judul-peta">Peta lokasi</h3>
                            <span class="card-subtitle ms-auto">Klik peta untuk memilih titik baru</span>
                        </div>
                        <div class="card-body p-2">
                            <div id="map" class="lokasi-map"></div>
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card h-100" aria-labelledby="judul-tambah-lokasi">
                        <div class="card-header">
                            <h3 class="card-title" id="judul-tambah-lokasi">Tambah titik lokasi</h3>
                        </div>
                        <form action="{{ route('cabanglokasi.store', $cabang->kode_cabang) }}" method="POST" id="formTambah"
                            class="card-body">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="nama_lokasi">Nama lokasi</label>
                                <input type="text" name="nama_lokasi" id="nama_lokasi" class="form-control"
                                    placeholder="Contoh: Pintu Utama / Gudang">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="coords_source">Koordinat</label>
                                <div class="input-group">
                                    <input type="text" id="coords_source" class="form-control"
                                        placeholder="Tempel “lat, lon” dari Google Maps">
                                    <button type="button" class="btn" id="btnParseCoords">Terapkan</button>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <span class="form-hint m-0">atau klik peta, atau</span>
                                    <button type="button" class="btn btn-sm" id="btnUseMyLocation">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16"
                                            viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                            <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0" />
                                            <path d="M12 2l0 2" />
                                            <path d="M12 20l0 2" />
                                            <path d="M20 12l2 0" />
                                            <path d="M2 12l2 0" />
                                        </svg>Pakai GPS saya
                                    </button>
                                </div>
                            </div>

                            <div class="form-grid mb-3">
                                <div>
                                    <label class="form-label required" for="latitude">Latitude</label>
                                    <input type="text" name="latitude" id="latitude" class="form-control cell-num"
                                        placeholder="-6.xxx" required readonly>
                                </div>
                                <div>
                                    <label class="form-label required" for="longitude">Longitude</label>
                                    <input type="text" name="longitude" id="longitude" class="form-control cell-num"
                                        placeholder="106.xxx" required readonly>
                                </div>
                                <div>
                                    <label class="form-label" for="radius">Radius</label>
                                    <div class="input-group">
                                        <input type="number" name="radius" id="radius" class="form-control" value="100" min="1">
                                        <span class="input-group-text">meter</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end">
                                    <label class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="aktif" value="1" checked>
                                        <span class="form-check-label">Aktif</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Simpan titik lokasi</button>
                        </form>
                    </section>
                </div>

                <div class="col-12">
                    <section class="card list-card" aria-label="Daftar titik lokasi">
                        <div class="summary-strip" role="group" aria-label="Ringkasan titik lokasi">
                            <div class="summary-item">
                                <span class="summary-label">Total titik</span>
                                <span class="summary-value">{{ $lokasis->count() }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Aktif</span>
                                <span class="summary-value">{{ $jumlahAktif }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Nonaktif</span>
                                <span class="summary-value">{{ $lokasis->count() - $jumlahAktif }}</span>
                            </div>
                        </div>

                        @if ($lokasis->isEmpty())
                            <div class="list-empty">
                                <p class="mb-1 fw-medium">Belum ada titik lokasi tambahan.</p>
                                <p class="mb-0 text-secondary small">Karyawan cabang ini absen di lokasi kantor utama. Tambahkan titik lain lewat form di atas.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-vcenter list-table">
                                    <thead>
                                        <tr>
                                            <th>Lokasi</th>
                                            <th>Koordinat</th>
                                            <th class="text-end">Radius</th>
                                            <th>Status</th>
                                            <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lokasis as $lok)
                                            <tr>
                                                <td class="cell-person">
                                                    <span class="person-name">{{ $lok->nama_lokasi ?: 'Tanpa nama' }}</span>
                                                </td>
                                                <td data-label="Koordinat" class="cell-num text-secondary">{{ $lok->latitude }}, {{ $lok->longitude }}</td>
                                                <td data-label="Radius" class="cell-num text-lg-end">{{ $lok->radius }} m</td>
                                                <td data-label="Status">
                                                    <span class="emp-status emp-status--{{ $lok->aktif ? 'success' : 'neutral' }}">{{ $lok->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                                </td>
                                                <td class="cell-actions">
                                                    <x-admin.row-menu :label="$lok->nama_lokasi ?: 'lokasi'">
                                                        <button type="button" class="dropdown-item btn-focus" data-lat="{{ $lok->latitude }}"
                                                            data-lon="{{ $lok->longitude }}" data-radius="{{ $lok->radius }}">Lihat di peta</button>
                                                        <button type="button" class="dropdown-item btn-edit-lokasi" data-id="{{ $lok->id }}"
                                                            data-nama="{{ $lok->nama_lokasi }}" data-lat="{{ $lok->latitude }}"
                                                            data-lon="{{ $lok->longitude }}" data-radius="{{ $lok->radius }}"
                                                            data-aktif="{{ $lok->aktif }}">Edit</button>
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('cabanglokasi.destroy', [$cabang->kode_cabang, $lok->id]) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                data-confirm="Titik “{{ $lok->nama_lokasi ?: 'tanpa nama' }}” akan dihapus permanen."
                                                                data-confirm-title="Hapus titik lokasi?">Hapus</button>
                                                        </form>
                                                    </x-admin.row-menu>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-editlokasi" tabindex="-1" aria-labelledby="judulEditLokasi" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditLokasi">Edit titik lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="formEditLokasi" class="modal-form-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id">
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-grid-full">
                                <label class="form-label" for="edit_nama_lokasi">Nama lokasi</label>
                                <input type="text" class="form-control" id="edit_nama_lokasi" name="nama_lokasi"
                                    placeholder="Contoh: Pintu Utama / Gudang">
                            </div>
                            <div>
                                <label class="form-label required" for="edit_latitude">Latitude</label>
                                <input type="text" class="form-control" id="edit_latitude" name="latitude" required>
                            </div>
                            <div>
                                <label class="form-label required" for="edit_longitude">Longitude</label>
                                <input type="text" class="form-control" id="edit_longitude" name="longitude" required>
                            </div>
                            <div>
                                <label class="form-label required" for="edit_radius">Radius</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="edit_radius" name="radius" min="1" required>
                                    <span class="input-group-text">meter</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-end">
                                <label class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="edit_aktif" name="aktif" value="1">
                                    <span class="form-check-label">Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="btnSimpanEdit">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        $(function() {
            const token = (nama) => getComputedStyle(document.documentElement).getPropertyValue(nama).trim();
            const existingData = @json($lokasiPayload);
            const defaultCenter = existingData.length > 0 ? [existingData[0].lat, existingData[0].lon] : [-6.2000, 106.8167];

            const map = L.map('map').setView(defaultCenter, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let tempMarker, tempCircle;

            // Titik tersimpan.
            existingData.forEach(l => {
                L.circle([l.lat, l.lon], {
                    radius: l.radius,
                    color: token('--chart-primary') || '#1e3a8a',
                    fillOpacity: 0.2
                }).addTo(map).bindPopup($('<div>').append($('<b>').text(l.nama || 'Lokasi'), '<br>Radius: ' + l.radius + ' m')[0]);
            });

            // Pratinjau titik yang sedang dipilih.
            function updatePreview(lat, lon, rad) {
                if (tempMarker) map.removeLayer(tempMarker);
                if (tempCircle) map.removeLayer(tempCircle);
                tempMarker = L.marker([lat, lon]).addTo(map);
                tempCircle = L.circle([lat, lon], {
                    radius: rad,
                    color: token('--chart-danger') || '#dc2626',
                    fillOpacity: 0.3
                }).addTo(map);
                map.flyTo([lat, lon], 17);
            }

            function pilihTitik(lat, lon) {
                $('#latitude').val(Number(lat).toFixed(7));
                $('#longitude').val(Number(lon).toFixed(7));
                updatePreview(lat, lon, $('#radius').val());
            }

            map.on('click', (e) => pilihTitik(e.latlng.lat, e.latlng.lng));

            $('#btnUseMyLocation').on('click', function() {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(
                    (pos) => pilihTitik(pos.coords.latitude, pos.coords.longitude),
                    () => Swal.fire('Gagal', 'Izin lokasi ditolak atau GPS tidak aktif.', 'error')
                );
            });

            $('#btnParseCoords').on('click', function() {
                const cocok = $('#coords_source').val().match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
                if (cocok) {
                    pilihTitik(cocok[1], cocok[2]);
                } else {
                    Swal.fire('Format tidak dikenali', 'Tempel koordinat dengan format “lat, lon”.', 'info');
                }
            });

            $('#radius').on('input', function() {
                if ($('#latitude').val()) updatePreview($('#latitude').val(), $('#longitude').val(), $(this).val());
            });

            $(document).on('click', '.btn-edit-lokasi', function() {
                const d = $(this).data();
                $('#edit_id').val(d.id);
                $('#edit_nama_lokasi').val(d.nama);
                $('#edit_latitude').val(d.lat);
                $('#edit_longitude').val(d.lon);
                $('#edit_radius').val(d.radius);
                $('#edit_aktif').prop('checked', d.aktif == 1);
                $('#modal-editlokasi').modal('show');
            });

            $('#btnSimpanEdit').on('click', function() {
                const id = $('#edit_id').val();
                $.ajax({
                    url: `/cabang/{{ $cabang->kode_cabang }}/lokasi/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT',
                        nama_lokasi: $('#edit_nama_lokasi').val(),
                        latitude: $('#edit_latitude').val(),
                        longitude: $('#edit_longitude').val(),
                        radius: $('#edit_radius').val(),
                        aktif: $('#edit_aktif').is(':checked') ? 1 : 0
                    },
                    success: function() {
                        $('#modal-editlokasi').modal('hide');
                        Swal.fire({
                            title: 'Tersimpan',
                            text: 'Titik lokasi berhasil diperbarui.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    },
                    error: (xhr) => {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                    }
                });
            });

            $('.btn-focus').on('click', function() {
                updatePreview($(this).data('lat'), $(this).data('lon'), $(this).data('radius'));
                document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
@endpush
