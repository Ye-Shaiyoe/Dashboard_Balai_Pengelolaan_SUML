# 📁 Struktur Direktori & Modul Aplikasi
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini memetakan seluruh susunan direktori, penempatan berkas kode sumber, serta fungsi modul-modul di dalam proyek **Dashboard TU BP SUML**.

---

## 1. Peta Direktori Tingkat Atas (Root Structure)

```
dashboard-tubpsuml/
├── app/                  # Inti logika aplikasi (Controllers, Models, Middleware, Services)
├── bootstrap/            # Inisialisasi awal framework & cache registrasi service provider
├── config/               # Seluruh berkas konfigurasi Laravel (app, auth, database, filesystems, dll)
├── database/             # Migrasi skema database, factory, dan seeders
├── docker/               # Konfigurasi containerization (Nginx, PHP-FPM, script start)
├── docs/                 # Repositori dokumentasi teknis & fungsional (multi-folder)
├── public/               # Root dokumen web publik (index.php, css/js terkompilasi, avatar)
├── resources/            # Template Blade, file sumber CSS/JS, dan aset antarmuka
├── routes/               # Deklarasi rute HTTP web, autentikasi, dan scheduler konsol
├── storage/              # Berkas privat/publik, file sesi, cache, dan log aplikasi
├── template-surat/       # Master template naskah dinas Word baku (.docx)
├── tests/                # Pengujian unit dan fitur (PHPUnit / Pest)
├── composer.json         # Konfigurasi dependensi PHP & autoloading PSR-4
├── package.json          # Konfigurasi dependensi NPM & bundler Vite
└── vite.config.js        # Konfigurasi build Vite untuk Tailwind & JavaScript
```

---

## 2. Struktur Rinci Direktori `app/`

```
app/
├── Console/
│   └── Commands/                        # Perintah artisan kustom terjadwal
│       ├── CleanupExpiredFiles.php      # Menghapus file Word expired pada surat tuntas
│       ├── CleanupExpiredNotifications.php # Membersihkan notifikasi lama > 7 hari
│       ├── CleanupOrphanedDbReferences.php # Sinkronisasi entri DB dengan ketersediaan fisik
│       ├── CleanupRejectedSurats.php    # Arsip naskah ditolak > 5 hari kerja tanpa revisi
│       ├── PerformanceMonitor.php       # Pengecekan performa sistem
│       └── RemindSlaDeadline.php        # Notifikasi pengingat SLA mendekati batas / overdue
├── Exports/                             # Kelas generator ekspor spreadsheet Excel
│   ├── AdminSuratExport.php
│   └── SlaExport.php
├── Helpers/
│   └── ActivityLogHelper.php            # Helper pencatatan audit log terpusat
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                       # Modul kerja Admin, Kasubbag TU, Arsiparis, Kabalai
│   │   │   ├── AdminRoleSelectionController.php
│   │   │   ├── AnalyticsController.php
│   │   │   ├── AspirasiController.php
│   │   │   ├── BugReportController.php
│   │   │   ├── ChartController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── FileSuratController.php
│   │   │   ├── LaporanController.php
│   │   │   ├── LogController.php
│   │   │   ├── NotifikasiController.php
│   │   │   ├── RiwayatController.php
│   │   │   ├── SidebarController.php
│   │   │   ├── SlaController.php
│   │   │   ├── SuratController.php
│   │   │   ├── TemplateSuratController.php
│   │   │   └── UserController.php
│   │   ├── Auth/                        # Modul Breeze autentikasi & kredensial
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   ├── ConfirmablePasswordController.php
│   │   │   ├── NewPasswordController.php
│   │   │   ├── PasswordController.php
│   │   │   ├── PasswordResetLinkController.php
│   │   │   ├── RegisteredUserController.php
│   │   │   └── SwitchAccountController.php
│   │   ├── User/                        # Modul kerja Pegawai Pengusul
│   │   │   ├── ActivityLogController.php
│   │   │   ├── AgendaController.php
│   │   │   ├── AspirasiController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── NotifikasiController.php
│   │   │   ├── PegawaiController.php
│   │   │   ├── SlaMonitoringController.php
│   │   │   ├── StatistikController.php
│   │   │   ├── SuratController.php
│   │   │   └── TemplateController.php
│   │   ├── Controller.php               # Base Controller
│   │   ├── ITSupportController.php      # Modul kelola tiket teknis & blast notifikasi
│   │   ├── NotificationApiController.php # Endpoint polling status & lonceng notif
│   │   ├── NotificationStreamController.php # Endpoint Server-Sent Events (SSE)
│   │   ├── ProfileController.php        # Edit profil, kelola sesi & TTE PIN
│   │   ├── VerifikasiSuratController.php # Landing page publik QR Code naskah resmi
│   │   └── WelcomeController.php        # Halaman depan sistem
│   ├── Middleware/
│   │   ├── AdminMiddleware.php          # Gerbang verifikasi akses pengguna tingkat admin
│   │   ├── EnsureIsITSupport.php        # Filter khusus hak akses IT Support
│   │   ├── RedirectIfAdminRoleNotSelected.php # Memastikan admin memilih perannya
│   │   └── SecurityHeaders.php          # Penyuntikan header proteksi HTTP
│   └── Requests/                        # Validasi request formulir terpisah
├── Models/                              # Eloquent Models & Business Rules
│   ├── ActivityLog.php
│   ├── Aspirasi.php
│   ├── ItSupportTicket.php
│   ├── Surat.php
│   ├── SuratDeleteRequest.php
│   ├── SuratTahapan.php
│   └── User.php
├── Notifications/                       # Notifikasi internal sistem (database/broadcast)
│   ├── FileRevisiNotification.php
│   ├── SuratDiprosesNotification.php
│   └── SuratStatusNotification.php
├── Services/
│   └── HtmlSanitizer.php                # Pembersihan konten input dari script injeksi
└── Traits/
    └── LogsUserActivity.php             # Trait Eloquent untuk auto-recording audit log
```

---

## 3. Struktur Direktori `storage/` dan Pembagian Hak Akses

Direktori `storage/` dibagi menjadi dua partisi utama:

1. **`storage/app/public/` (Dapat diakses web lewat symlink `public/storage`)**:
   - `avatars/`: Foto profil pengguna.
   - `templates/`: Berkas template naskah dinas Word baku yang dapat diunduh oleh pegawai pengusul.
2. **`storage/app/private/` (Terproteksi, TIDAK ada symlink web publik)**:
   - `surat/`: Berkas naskah dinas `.docx` yang diajukan. Hanya dapat diunduh/dilihat melalui controller dengan otorisasi user terkait atau admin berwenang.
   - `lampiran/`: Berkas PDF/ZIP pendukung naskah dinas.
   - `tte/`: Gambar scan tanda tangan basah Kepala Balai untuk pembubuhan dokumen digital. Hanya diakses sistem saat pengesahan naskah.
3. **`storage/logs/`**:
   - `laravel.log`: Berkas log aktivitas dan error sistem harian.

