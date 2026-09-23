@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-2 me-2" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5"></path>
                            <path d="M9 4v13"></path>
                            <path d="M15 7v5.5"></path>
                            <path
                                d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z">
                            </path>
                            <path d="M19 18v.01"></path>
                        </svg>
                        Lokasi Cabang: {{ $cabang->nama_cabang }} ({{ $cabang->kode_cabang }})
                    </h2>
                    <div class="text-muted">Aktif: {{ $lokasis->where('aktif', 1)->count() }} • Total:
                        {{ $lokasis->count() }}</div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('cabang.index') }}" class="btn btn-secondary btn-pill">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M5 12l14 0"></path>
                            <path d="M5 12l6 6"></path>
                            <path d="M5 12l6 -6"></path>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <div id="map"
                        style="height: 380px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--color-rule);"></div>

                    {{-- Form Tambah Lokasi --}}
                    <form action="{{ route('cabanglokasi.store', $cabang->kode_cabang) }}" method="POST" id="formTambah">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Nama Lokasi</label>
                                <input type="text" name="nama_lokasi" class="form-control"
                                    placeholder="Pintu Utama / Gudang">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control"
                                    placeholder="-6.xxx" required readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control"
                                    placeholder="106.xxx" required readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Radius</label>
                                <div class="input-group">
                                    <input type="number" name="radius" id="radius" class="form-control" value="100">
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <label class="form-label">Aktif</label>
                                <div class="form-check form-switch pt-2 d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="aktif" value="1" checked>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus"
                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 5l0 14"></path>
                                        <path d="M5 12l14 0"></path>
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <div class="row mt-3 g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group input-group-flat">
                                    <input type="text" id="coords_source" class="form-control"
                                        placeholder="Tempel 'Lat, Lon' atau URL Google Maps di sini">
                                    <span class="input-group-text">
                                        <a href="javascript:void(0)" class="link-secondary" id="btnParseCoords"
                                            title="Parse Koordinat">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-analyze" width="20"
                                                height="20" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M20 11a8.1 8.1 0 0 0 -6.986 -6.918a8.095 8.095 0 0 0 -8.019 3.918">
                                                </path>
                                                <path d="M4 13a8.1 8.1 0 0 0 15 3"></path>
                                                <path d="M19 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                <path d="M5 8m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                            </svg>
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-info w-100" id="btnUseMyLocation">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-current-location" width="20"
                                        height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                        <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0"></path>
                                        <path d="M12 12l0 .01"></path>
                                    </svg>
                                    Gunakan GPS Saya
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Tabel Daftar Lokasi --}}
                    <div class="table-responsive mt-4">
                        <table class="table table-vcenter card-table table-nowrap">
                            <thead>
                                <tr>
                                    <th class="w-1">No</th>
                                    <th>Nama Lokasi</th>
                                    <th style="width: 30%">Koordinat</th>
                                    <th class="w-1 text-center">Radius</th>
                                    <th class="w-1 text-center">Status</th>
                                    <th class="w-1 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lokasis as $lok)
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $lok->nama_lokasi ?: '-' }}</strong>
                                        </td>
                                        <td class="text-muted">
                                            <small>{{ $lok->latitude }}, {{ $lok->longitude }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-azure-lt">{{ $lok->radius }} m</span>
                                        </td>
                                        <td class="text-center">
                                            @if($lok->aktif)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="text-primary btn-edit-lokasi" 
                                                   data-id="{{ $lok->id }}"
                                                   data-nama="{{ $lok->nama_lokasi }}"
                                                   data-lat="{{ $lok->latitude }}"
                                                   data-lon="{{ $lok->longitude }}"
                                                   data-radius="{{ $lok->radius }}"
                                                   data-aktif="{{ $lok->aktif }}"
                                                   title="Edit" aria-label="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.415v3h3l8.415 -8.415z"></path>
                                                        <path d="M16 5l3 3"></path>
                                                    </svg>
                                                </a>
                                                <a href="#" class="text-info btn-focus"
                                                    data-lat="{{ $lok->latitude }}" data-lon="{{ $lok->longitude }}"
                                                    data-radius="{{ $lok->radius }}" title="Fokus Peta" aria-label="Fokus Peta">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-target" width="20"
                                                        height="20" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="12" cy="12" r="5"></circle>
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                    </svg>
                                                </a>
                                                <a href="#" class="text-danger btn-delete"
                                                    data-id="{{ $lok->id }}" data-nama="{{ $lok->nama_lokasi }}"
                                                    title="Hapus" aria-label="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-trash" width="20"
                                                        height="20" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M4 7l16 0"></path>
                                                        <path d="M10 11l0 6"></path>
                                                        <path d="M14 11l0 6"></path>
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                    </svg>
                                                </a>
                                                <form
                                                    action="{{ route('cabanglokasi.destroy', [$cabang->kode_cabang, $lok->id]) }}"
                                                    method="POST" id="form-del-{{ $lok->id }}" class="d-none">
                                                    @csrf @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center p-4 text-muted">Belum ada titik lokasi
                                            tambahan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit Lokasi --}}
    <div class="modal modal-blur fade" id="modal-editlokasi" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditLokasi">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Lokasi</label>
                            <input type="text" class="form-control" id="edit_nama_lokasi" name="nama_lokasi" placeholder="Pintu Utama / Gudang">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="edit_latitude" name="latitude" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="edit_longitude" name="longitude" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Radius (meter)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_radius" name="radius" required>
                                <span class="input-group-text">m</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_aktif" name="aktif" value="1">
                                <span class="form-check-label">Aktif</span>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSimpanEdit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"></path>
                            <circle cx="12" cy="14" r="2"></circle>
                            <polyline points="14 4 14 8 8 8 8 4"></polyline>
                        </svg>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            // --- 1. SETTING MAP ---
            const existingData = @json($lokasiPayload);
            const defaultCenter = existingData.length > 0 ? [existingData[0].lat, existingData[0].lon] : [-6.2000,
                106.8167
            ];

            const map = L.map('map').setView(defaultCenter, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let tempMarker, tempCircle;

            // Tampilkan marker lokasi yang sudah tersimpan
            existingData.forEach(l => {
                L.circle([l.lat, l.lon], {
                        radius: l.radius,
                        color: '#206bc4',
                        fillOpacity: 0.2
                    }).addTo(map)
                    .bindPopup(`<b>${l.nama || 'Lokasi'}</b><br>Radius: ${l.radius}m`);
            });

            function updatePreview(lat, lon, rad) {
                if (tempMarker) map.removeLayer(tempMarker);
                if (tempCircle) map.removeLayer(tempCircle);
                tempMarker = L.marker([lat, lon]).addTo(map);
                tempCircle = L.circle([lat, lon], {
                    radius: rad,
                    color: '#d63939',
                    fillOpacity: 0.3
                }).addTo(map);
                map.flyTo([lat, lon], 17);
            }

            // --- 2. EVENT LISTENER ---
            map.on('click', function(e) {
                $('#latitude').val(e.latlng.lat.toFixed(7));
                $('#longitude').val(e.latlng.lng.toFixed(7));
                updatePreview(e.latlng.lat, e.latlng.lng, $('#radius').val());
            });

            $('#btnUseMyLocation').on('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        const lat = pos.coords.latitude;
                        const lon = pos.coords.longitude;
                        $('#latitude').val(lat.toFixed(7));
                        $('#longitude').val(lon.toFixed(7));
                        updatePreview(lat, lon, $('#radius').val());
                    }, () => {
                        Swal.fire('Error', 'Izin lokasi ditolak atau GPS tidak aktif.', 'error');
                    });
                }
            });

            $('#btnParseCoords').on('click', function() {
                const val = $('#coords_source').val();
                const latLonMatch = val.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
                if (latLonMatch) {
                    $('#latitude').val(latLonMatch[1]);
                    $('#longitude').val(latLonMatch[2]);
                    updatePreview(latLonMatch[1], latLonMatch[2], $('#radius').val());
                } else {
                    Swal.fire('Info', 'Format Lat, Lon tidak ditemukan.', 'info');
                }
            });

            // Buka modal edit
            $(document).on('click', '.btn-edit-lokasi', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const nama = $(this).data('nama');
                const lat = $(this).data('lat');
                const lon = $(this).data('lon');
                const radius = $(this).data('radius');
                const aktif = $(this).data('aktif');

                $('#edit_id').val(id);
                $('#edit_nama_lokasi').val(nama);
                $('#edit_latitude').val(lat);
                $('#edit_longitude').val(lon);
                $('#edit_radius').val(radius);
                $('#edit_aktif').prop('checked', aktif == 1);

                $('#modal-editlokasi').modal('show');
            });

            // Simpan edit via AJAX
            $('#btnSimpanEdit').on('click', function() {
                const id = $('#edit_id').val();
                const data = {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    nama_lokasi: $('#edit_nama_lokasi').val(),
                    latitude: $('#edit_latitude').val(),
                    longitude: $('#edit_longitude').val(),
                    radius: $('#edit_radius').val(),
                    aktif: $('#edit_aktif').is(':checked') ? 1 : 0
                };

                $.ajax({
                    url: `/cabang/{{ $cabang->kode_cabang }}/lokasi/${id}`,
                    type: 'POST',
                    data: data,
                    success: function() {
                        $('#modal-editlokasi').modal('hide');
                        Swal.fire({
                            title: 'Tersimpan!',
                            text: 'Lokasi berhasil diperbarui.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: (xhr) => {
                        Swal.fire('Gagal', xhr.responseJSON.message || 'Error saat menyimpan.', 'error');
                    }
                });
            });

            $('.btn-focus').on('click', function() {
                updatePreview($(this).data('lat'), $(this).data('lon'), $(this).data('radius'));
            });

            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama') || 'ini';
                Swal.fire({
                    title: 'Hapus Lokasi?',
                    text: `Lokasi "${nama}" akan dihapus permanen.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) $('#form-del-' + id).submit();
                });
            });
        });
    </script>
@endpush
