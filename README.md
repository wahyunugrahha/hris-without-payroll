<p align="center">
    <img src="public/assets/img/sjp_horizontal.png" width="400" alt="SJP Logo">
</p>

<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.2+-8993be.svg?style=flat-square&logo=php" alt="PHP Version">
    <img src="https://img.shields.io/badge/Laravel-11+-FF2D20.svg?style=flat-square&logo=laravel" alt="Laravel Version">
    <img src="https://img.shields.io/badge/PostgreSQL-16+-336791.svg?style=flat-square&logo=postgresql" alt="PostgreSQL Version">
    <img src="https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg?style=flat-square&logo=bootstrap" alt="Bootstrap Version">
    <img src="https://img.shields.io/badge/Tailwind-4.0-06B6D4.svg?style=flat-square&logo=tailwindcss" alt="Tailwind Version">
</p>

# Absen SJP Laravel

**Absen SJP Laravel** adalah sistem manajemen presensi, KPI, dan administrasi karyawan yang komprehensif. Dibangun dengan fokus pada efisiensi operasional dan monitoring kinerja tim secara real-time.

---

## 🛠 Tech Stack

| Layer             | Technology                                    |
| ----------------- | --------------------------------------------- |
| **Backend**       | Laravel 11 (PHP 8.2+)                         |
| **Database**      | PostgreSQL 16                                 |
| **Frontend UI**   | Tabler Core (Bootstrap 5.3) & TailwindCSS 4.0 |
| **Asset Manager** | Vite                                          |
| **Auth & ACL**    | Spatie Laravel-Permission                     |

---

## 🚀 Key Modules & Features

### 👤 User & Role Management

- **Multi-Role Support**: Admin IT, HRD, Admin Cabang, dan Karyawan.
- **Granular Permissions**: Pengaturan akses modul berdasarkan permission yang detail.
- **Employee Lifecycle**: Manajemen data karyawan dari penempatan hingga status aktif.

### 🕒 Attendance & Presence

- **Daily Attendance**: Pencatatan kehadiran harian karyawan.
- **Monitoring**: Dashboard admin untuk memantau kehadiran secara real-time.
- **Location Based**: Integrasi dengan data cabang dan lokasi kerja.

### 📈 Performance Tracking (KPI)

- **KPI Master**: Pengaturan parameter penilaian kinerja.
- **Self Assessment & Approval**: Input KPI oleh karyawan dan verifikasi oleh atasan/admin.
- **Reporting**: Rekapitulasi nilai KPI untuk evaluasi periodik.

### 📅 Absence & Leave Management

- **Permit/Izin**: Pengajuan izin sakit, keperluan mendesak, dll.
- **Leave/Cuti**: Manajemen jatah cuti dan persetujuan cuti karyawan.
- **Overtime/Lembur**: Alur pengajuan dan persetujuan lembur yang sistematis.
- **Holiday Management**: Pengaturan hari libur nasional dan kebijakan perusahaan.

### 📄 Administrative Tools

- **Warning Letters (SP)**: Digitalisasi pembuatan dan pengarsipan surat peringatan.
- **Reports**: Ekspor data presensi dan aktivitas untuk kebutuhan HR.

---

## 📦 Requirements

- **PHP**: 8.3 or higher
- **Database**: PostgreSQL 16 or higher
- **Dependency Manager**: Composer & NPM/PNPM
- **Web Server**: Nginx or `php artisan serve`

---

## 🛠 Setup Guide

### 1. Clone & Install

```bash
git clone https://github.com/your-repo/absen-sjp-laravel.git
cd absen-sjp-laravel
composer install
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Pastikan `DB_CONNECTION=pgsql` dan sesuaikan kredensial database Anda.

### 3. Database & Seeding

```bash
php artisan migrate --seed
```

### 4. Storage Link

Penting untuk menjalankan perintah ini agar file yang diunggah (foto profil, bukti izin) dapat diakses melalui browser:

```bash
php artisan storage:link
```

### 5. Admin Credentials

Setelah menjalankan seeder, Anda dapat login menggunakan akun berikut:

- **Email**: `admin@sjp.com`
- **Password**: `Admin12345`

### 6. Running

```bash
npm run dev
php artisan serve
```

---

## 🌐 Production / VPS Deployment

### Basic Update Commands

```bash
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
sudo systemctl reload nginx
```

### Database Maintenance

**Backup Database:**

```bash
pg_dump -U postgres -h 127.0.0.1 -p 5432 absen_sjp > backup_database.sql
```

**Restore Database:**

```bash
dropdb absen_sjp && createdb absen_sjp
psql -U postgres -d absen_sjp -f backup.sql
```

---

## 📝 License

This project is for internal use. All rights reserved.
