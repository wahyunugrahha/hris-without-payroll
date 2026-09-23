# Design — HRIS Admin Panel

Sistem desain terkunci untuk semua halaman admin (`layouts.admin.tabler`).
Halaman baru atau redesign halaman admin membaca file ini dulu. Ubah file ini
bila sistem perlu berkembang, jangan override per halaman.

Implementasi:
- `public/assets/css/admin-tokens.css` — token warna/spasi/radius (+ pemetaan ke variabel Tabler).
- `public/assets/css/admin-theme.css` — gaya layout & komponen admin.
- `public/assets/js/admin.js` — perilaku global (tema, flash, command palette).

Cakupan: semua view `resources/views/admin/**`, layout `layouts/admin/*`, login admin,
Dashboard Overview & TV (HTML mandiri, memuat `admin-tokens.css`; TV dikunci mode gelap).
Dikecualikan: template cetak/Excel (`cetak*`, `*excel*`, `print`) — dokumen, bukan layar.

## Genre
modern-minimal (SaaS / dashboard). Tenang, rapi, satu aksen.

## Macrostructure family
- Halaman aplikasi: **Workbench** — judul halaman (pretitle + title) kiri, aksi kanan;
  kartu filter; tabel/data di bawah. Tanpa hero, tanpa ilustrasi.

## Theme
Light:
- `--color-paper`     oklch(98.4% 0.004 250) — latar halaman
- `--color-surface`   oklch(100% 0 0) — kartu, sidebar, header
- `--color-surface-2` oklch(97% 0.006 252) — header tabel, hover
- `--color-ink`       oklch(24% 0.02 258) — judul
- `--color-ink-2`     oklch(34% 0.018 257) — teks isi
- `--color-muted`     oklch(52% 0.018 257) — teks sekunder
- `--color-rule`      oklch(92% 0.008 255) — garis 1px
- `--color-accent`    oklch(41% 0.12 254) — navy brand `#094b87`
- `--color-focus`     oklch(58% 0.16 254)

Dark: token yang sama didefinisikan ulang di `[data-bs-theme=dark]`
(paper 17 %, surface 21 %, accent 58 %).

## Typography
Font **tidak diubah**: Inter (rsms.me/inter) bawaan Tabler. Angka di tabel memakai
`tabular-nums`.

## Warna status (semantik, jangan dipaksa jadi navy)
- Navy (`btn-primary`) — aksi utama: Simpan, Cari Data, Tambah.
- Hijau (`success`) — setuju/hadir/aktif/ekspor.
- Kuning (`warning`) — terlambat/perlu perhatian. Merah (`danger`) — tolak/hapus/alpha.

## Kerangka (layout admin)
- **Sidebar**: latar `--color-sidebar` (sedikit lebih gelap dari konten), item 36px,
  submenu dengan garis panduan vertikal; item aktif = garis navy 2px + tint.
- **Header**: sticky 56px, blur tipis; kiri tombol "Cari menu… Ctrl K" (command
  palette dari link sidebar, `public/assets/js/admin.js`), kanan tema/notifikasi/akun.
- **Judul halaman** (`.page-header`): pita putih penuh + garis bawah; pretitle kecil,
  judul 1.375rem/600, ikon judul navy.
- **Toolbar judul**: hanya `btn-primary` yang berwarna; tombol lain otomatis netral
  (outline), makna warnanya pindah ke ikon.
- **Badge status** di konten: tint lembut dari warna status, bukan blok penuh.

## JavaScript
- Perilaku global admin di `public/assets/js/admin.js` (tema, flash, palette).
- Data server ke JS lewat `<script type="application/json">` + `@json`, bukan
  interpolasi Blade di dalam string JS.

## Aturan komponen
- Satu tombol utama navy per area aksi; sisanya outline/ghost.
- Radius: 6px kontrol (tombol, input, badge), 10px kartu/menu/modal.
- Kedalaman dari garis 1px, bukan bayangan. Tanpa gradient.
- Focus ring 2px `--color-focus`, offset 2px, tampil instan.
- Gerak: hanya warna latar/teks 150ms; `prefers-reduced-motion` mematikan transisi.

## Warna di view
Pakai kelas Tabler (`btn-primary`, `bg-success-lt`, dst.) atau `var(--color-*)`.
Jangan menulis hex/rgb baru di view. SweetAlert: `confirmButtonColor: 'var(--color-accent)'`
(hapus/tolak: `var(--color-danger)`, batal: `var(--color-muted)`).
Pengecualian yang sengaja: palet grafik ApexCharts dan warna lingkaran peta Leaflet
(butuh warna literal di atribut SVG), warna medali di Overview (variabel bernama).
