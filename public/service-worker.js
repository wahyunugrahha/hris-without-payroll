/*
 * Aplikasi ini tidak memakai service worker. File ini hanya "pemutus" service worker lama yang
 * pernah terpasang di alamat yang sama (aplikasi sebelumnya): saat browser memeriksa pembaruan,
 * versi ini menghapus semua cache-nya, melepas registrasinya, lalu memuat ulang tab yang terbuka.
 * Tanpa ini, browser terus menampilkan gambar/halaman lama dan meminta ulang halaman cetak via GET.
 */
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil((async function () {
        const kunci = await caches.keys();
        await Promise.all(kunci.map(function (k) { return caches.delete(k); }));
        await self.registration.unregister();
        const klien = await self.clients.matchAll({ type: 'window' });
        klien.forEach(function (c) { c.navigate(c.url); });
    })());
});
