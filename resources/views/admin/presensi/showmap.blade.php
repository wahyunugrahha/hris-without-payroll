<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<style>
    #map {
        height: 350px;
        width: 100%;
    }
</style>

<div id="map"></div>

<script>
    var lokasi_in = "{{ $presensi->lokasi_in ?? '0,0' }}"; 
    var lok = lokasi_in.split(",");

    var latitude = lok.length === 2 && !isNaN(parseFloat(lok[0])) ? parseFloat(lok[0]) : 0;
    var longitude = lok.length === 2 && !isNaN(parseFloat(lok[1])) ? parseFloat(lok[1]) : 0;

    var map = L.map('map').setView([latitude, longitude], 18);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([latitude, longitude]).addTo(map);

    var circle = L.circle([latitude, longitude], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.3,
        radius: {{ $radius ?? 50 }} 
    }).addTo(map);

    var popup = L.popup()
        .setLatLng([latitude, longitude])
        .setContent("{{ $presensi->nama_lengkap ?? 'Lokasi Presensi' }}")
        .openOn(map);

    $('#modal-tampilmap').on('shown.bs.modal', function() {
        map.invalidateSize();
    });
</script>
