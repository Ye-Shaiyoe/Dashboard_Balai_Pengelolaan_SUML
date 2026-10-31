# 📚 Dokumentasi Resmi Sistem Informasi Persuratan & Dashboard Tata Usaha
### Balai Pengelolaan Sarana dan Utilitas Metrologi Legal (BP SUML)

Selamat datang di repositori dokumentasi resmi sistem **Dashboard TU BP SUML**. Seluruh dokumentasi teknis dan operasional telah distrukturisasi ke dalam arsitektur **multi-folder dan multi-file** yang modular, terstandar, dan akurat 100% dengan implementasi kode sumber proyek.

---

## 📌 Sekilas Tentang Sistem

Sistem Persuratan BP SUML adalah platform tata kelola naskah dinas kedinasan berbasis web yang mengotomasi dan mendigitalkan seluruh siklus hidup surat di lingkungan BP SUML. Platform ini mengintegrasikan:
- Penyusunan dan pengusulan draf naskah dinas Word (`.docx`) dan berkas lampiran pendukung.
- Verifikasi berjenjang multi-level (Pegawai &rarr; Arsiparis &rarr; Kasubbag TU &rarr; Kepala Balai).
- Penomoran surat resmi dan pembubuhan Tanda Tangan Elektronik (TTE) terproteksi PIN privat.
- Ekspedisi distribusi via TNDe dan platform nasional Srikandi.
- Pengarsipan digital dan kebijakan retensi pembersihan berkas fisik otomatis.
- Pemantauan Service Level Agreement (SLA) 30 jam kerja operasional secara *real-time*.
- Fitur administratif mandiri seperti **Reset Password Pegawai oleh Kasubbag TU** untuk mengatasi pembatasan SMTP pada jaringan Local Area Network (LAN) kantor.

---

## 🗺️ Peta dan Struktur Direktori Dokumentasi

Dokumentasi tersusun rapi di dalam 7 folder kategori utama sebagai berikut:

```
docs/
├── README.md                                             # Indeks Utama & Navigasi Dokumentasi Lengkap
│
├── 01-arsitektur-sistem/                                 # Modul 01: Arsitektur Perangkat Lunak & Desain
│   ├── 01-overview-dan-arsitektur-mvc.md                 # Arsitektur MVC Laravel 12 & Diagram Alir Sistem
│   ├── 02-tumpukan-teknologi-tech-stack.md               # Spesifikasi PHP 8.2+, PostgreSQL, Breeze, Chart.js, PhpWord
│   ├── 03-struktur-direktori-dan-modul.md                # Peta direktori app, storage, routes, dan public
│   ├── 04-mekanisme-keamanan-dan-privasi.md              # Blind index HMAC-SHA256 NIP, private storage, TTE PIN, throttle
│   ├── 05-komunikasi-realtime-dan-event.md               # Server-Sent Events (SSE), polling API cadangan, sistem notifikasi
│   └── 06-audit-trail-dan-activity-log.md                # Trait LogsUserActivity, ActivityLogHelper, diff JSON changes
│
├── 02-proses-bisnis-dan-alur-persuratan/                 # Modul 02: Alur & Proses Bisnis Persuratan
│   ├── 01-siklus-hidup-dan-10-tahap-surat.md             # State diagram & rincian lengkap 10 tahapan naskah dinas
│   ├── 02-verifikasi-berjenjang-dan-persetujuan.md      # Detail peran Arsiparis, Kasubbag TU, Kepala Balai
│   ├── 03-mekanisme-revisi-dan-penolakan.md              # Alur penolakan dokumen, revisi_count, catatan koreksi, re-upload Word
│   ├── 04-service-level-agreement-sla.md                 # Standar 30 jam kerja (07:00-17:00), indikator warna, overtime
│   ├── 05-retensi-dan-pembersihan-berkas.md              # Masa aktif berkas (file_expires_at), purge fisik mandiri, metadata permanen
│   └── 06-verifikasi-publik-qr-code.md                   # Rute publik /v/{uuid}, QR code otentisitas tanpa login
│
├── 03-peran-dan-hak-akses/                              # Modul 03: Peran & Hak Akses (RBAC)
│   ├── 01-definisi-dan-hierarki-peran.md                 # 6 role: user, admin_aspirasi, admin_kasubbag_tu, admin_kepala_balai, it_support, admin
│   ├── 02-matriks-kewenangan-rbac.md                     # Matriks lengkap hak akses fitur dan aksi per peran
│   ├── 03-otoritas-khusus-kasubbag-tu.md                 # Fitur reset password pegawai tanpa email SMTP, user & delete request
│   ├── 04-otoritas-khusus-it-support.md                  # Tiket kendala teknis, broadcast pengumuman sistem, log Laravel
│   └── 05-role-selection-dan-switch-account.md           # Gate admin.role.check, alih akun instan berbasis token
│
├── 04-basis-data-dan-skema-model/                       # Modul 04: Basis Data & Skema Model (ERD)
│   ├── 01-entity-relationship-diagram-erd.md             # ERD visual lengkap antar entitas PostgreSQL
│   ├── 02-tabel-users-dan-autentikasi.md                 # Kamus data tabel users, nip_hash, signature_pin, index unik
│   ├── 03-tabel-surats-dan-tahapan.md                    # Kamus data surats, surat_tahapans, surat_delete_requests
│   ├── 04-tabel-pendukung-dan-audit.md                   # Kamus data aspirasis, it_support_tickets, activity_logs, notifications
│   └── 05-model-eloquent-dan-relasi.md                   # Model Eloquent, casts, boot methods, accessor/mutator, heatmap
│
├── 05-panduan-operasional/                              # Modul 05: Panduan Operasional Pengguna & Admin
│   ├── 01-panduan-pegawai-pengusul.md                    # Manual login, ajukan draf/surat .docx, tracking live, revisi, rating
│   ├── 02-panduan-arsiparis.md                           # Manual verifikasi tahap 2, penomoran tahap 5, pengarsipan tahap 9, template
│   ├── 03-panduan-kasubbag-tu.md                         # Manual verifikasi tahap 3, reset password modal/clipboard, kelola user
│   ├── 04-panduan-kepala-balai.md                        # Manual persetujuan tahap 4, input PIN TTE tahap 6, ringkasan eksekutif
│   └── 05-panduan-tim-it-support.md                      # Manual penanganan tiket kendala, kirim blast siaran, cek log error
│
├── 06-automasi-dan-scheduler/                           # Modul 06: Automasi & Background Scheduler (Cron)
│   ├── 01-daftar-artisan-command.md                      # Rincian 5 perintah: files:cleanup-expired, surat:remind-sla, notifications:cleanup, dll
│   ├── 02-jadwal-dan-frekuensi-eksekusi.md               # Pemetaan jadwal konsol di routes/console.php (dini hari & jam kerja)
│   ├── 03-konfigurasi-crontab-linux.md                   # Konfigurasi Linux crontab (* * * * * schedule:run) di Ubuntu Server
│   └── 04-verifikasi-dan-uji-coba-manual.md              # Verifikasi schedule:list dan simulasi eksekusi perintah di CLI
│
└── 07-deployment-dan-infrastruktur/                     # Modul 07: Panduan Deployment & Infrastruktur Server
    ├── 01-prasyarat-server-dan-dependensi.md             # Ubuntu 22.04/24.04 LTS, PHP 8.2+ ekstensi, PostgreSQL 14+, Composer, Node
    ├── 02-instalasi-awal-dan-konfigurasi-env.md          # Setup git clone, .env produksi, database config, migrate, npm build
    ├── 03-konfigurasi-web-server-nginx.md                # Konfigurasi vhost Nginx, PHP-FPM socket, upload limit 30M
    ├── 04-manajemen-hak-akses-storage.md                 # Kepemilikan www-data, chmod 775 storage & bootstrap/cache, proteksi TTE
    ├── 05-prosedur-update-produksi-git-pull.md           # Git pull checklist produksi: optimize:clear, cache rebuild, OPcache restart
    └── 06-troubleshooting-lingkungan-lan.md              # Solusi error 500, port SMTP terblokir, broken storage link, cache browser
```

---

## 🎯 Panduan Cepat Berdasarkan Peran Anda (Role-Based Quick Links)

- 👤 **Saya seorang Pegawai Pengusul**:
  - Pelajari cara mengajukan surat dan memantau SLA: [Panduan Pegawai Pengusul](05-panduan-operasional/01-panduan-pegawai-pengusul.md)
  - Pahami siklus 10 tahapan naskah dinas: [Siklus Hidup & 10 Tahapan Surat](02-proses-bisnis-dan-alur-persuratan/01-siklus-hidup-dan-10-tahap-surat.md)
  - Alur jika dokumen ditolak verifikator: [Mekanisme Revisi & Penolakan](02-proses-bisnis-dan-alur-persuratan/03-mekanisme-revisi-dan-penolakan.md)

- 🏛️ **Saya Tim Arsiparis**:
  - Panduan verifikasi naskah, penomoran resmi, dan pengarsipan: [Panduan Arsiparis](05-panduan-operasional/02-panduan-arsiparis.md)
  - Manajemen retensi penyimpanan berkas: [Retensi & Pembersihan Berkas](02-proses-bisnis-dan-alur-persuratan/05-retensi-dan-pembersihan-berkas.md)

- 📋 **Saya Kasubbag Tata Usaha**:
  - Panduan verifikasi administrasi dan personalia: [Panduan Kasubbag TU](05-panduan-operasional/03-panduan-kasubbag-tu.md)
  - Panduan mereset kata sandi pegawai yang lupa sandi: [Otoritas Khusus Kasubbag TU](03-peran-dan-hak-akses/03-otoritas-khusus-kasubbag-tu.md)
  - Ketentuan penghitungan dan justifikasi SLA: [Service Level Agreement (SLA)](02-proses-bisnis-dan-alur-persuratan/04-service-level-agreement-sla.md)

- 👑 **Saya Kepala Balai (Pimpinan)**:
  - Panduan persetujuan dan pembubuhan TTE ber-PIN: [Panduan Kepala Balai](05-panduan-operasional/04-panduan-kepala-balai.md)
  - Validasi otentisitas dokumen publik: [Verifikasi Publik QR Code](02-proses-bisnis-dan-alur-persuratan/06-verifikasi-publik-qr-code.md)

- 💻 **Saya Tim IT Support & DevOps / Sysadmin**:
  - Penanganan tiket bug & broadcast: [Panduan Tim IT Support](05-panduan-operasional/05-panduan-tim-it-support.md)
  - Pengaturan automasi cron server: [Konfigurasi Crontab Linux](06-automasi-dan-scheduler/03-konfigurasi-crontab-linux.md)
  - Setup instalasi dan update server produksi: [Panduan Deployment & Infrastruktur](07-deployment-dan-infrastruktur/01-prasyarat-server-dan-dependensi.md)
  - Pemecahan masalah error produksi: [Troubleshooting Lingkungan LAN](07-deployment-dan-infrastruktur/06-troubleshooting-lingkungan-lan.md)

- 👨‍💻 **Saya Pengembang (Software Engineer / Backend Developer)**:
  - Arsitektur MVC & alur request: [Overview & Arsitektur MVC](01-arsitektur-sistem/01-overview-dan-arsitektur-mvc.md)
  - Kamus data & relasi model: [Diagram Relasi Entitas (ERD)](04-basis-data-dan-skema-model/01-entity-relationship-diagram-erd.md)
  - Keamanan blind indexing NIP & enkripsi: [Mekanisme Keamanan & Privasi](01-arsitektur-sistem/04-mekanisme-keamanan-dan-privasi.md)

---

## 💻 Ringkasan Spesifikasi Teknis Sistem

- **Framework**: Laravel 12.x (PHP 8.2+)
- **Basis Data**: PostgreSQL 14+ / 16+ (Driver PDO `pgsql`)
- **Autentikasi**: Laravel Breeze 2.4 (Disesuaikan untuk NIP Blind Indexing & Switch Account)
- **Frontend Engine**: Blade Templating, Tailwind CSS, Alpine.js, Chart.js, Bootstrap Icons
- **Pengolah Berkas**:
  - `phpoffice/phpword` (^1.1): Pemrosesan dokumen Microsoft Word (.docx)
  - `maatwebsite/excel` (^3.1): Ekspor spreadsheet data rekapitulasi surat & log SLA (.xlsx)
- **Keamanan Informasi**:
  - Blind Index Hashing (HMAC-SHA256) untuk NIP pegawai
  - Enkripsi Hash PIN untuk Tanda Tangan Elektronik (TTE)
  - Rate Limiting / Route Throttling untuk mitigasi brute force
  - Multi-session tracking & pembatalan sesi lintas perangkat (*Session Revocation*)
- **Target SLA**: 30 Jam Kerja Operasional (Senin s/d Jumat, 07:00–17:00 WIB)
- **Lingkungan Produksi**: Ubuntu Server Linux (On-Premise / LAN Intranet Kantor BP SUML)

---
*Dibuat secara komprehensif dan terstruktur untuk Tim Pengembang dan Pengelola Sistem Balai Pengelolaan Sarana dan Utilitas Metrologi Legal (BP SUML).*
