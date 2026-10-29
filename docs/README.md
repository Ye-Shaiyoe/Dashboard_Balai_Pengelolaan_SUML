# 📚 Dokumentasi Resmi Sistem Informasi Persuratan & Dashboard Tata Usaha
### Balai Pengelolaan Sarana dan Utilitas Metrologi Legal (BP SUML)

Selamat datang di repositori dokumentasi resmi sistem **Dashboard TU BP SUML**. Dokumen ini disusun sebagai acuan komprehensif bagi pengembang (developer), administrator sistem (DevOps/Sysadmin), tim IT Support, serta pengelola operasional dalam memahami rancang bangun, alur bisnis, arsitektur data, dan tata kelola sistem.

---

## 📌 Sekilas Tentang Sistem

Sistem Persuratan BP SUML adalah platform tata kelola naskah dinas kedinasan berbasis web yang mengotomasi dan mendigitalkan seluruh siklus hidup surat masuk/keluar di lingkungan BP SUML. Platform ini mengintegrasikan pengusulan berkas oleh pegawai, verifikasi berjenjang multi-level (Arsiparis, Kasubbag TU, Kepala Balai), penomoran resmi, penandatanganan elektronik (TTE/DS), tracking pengiriman (TNDe dan Srikandi), hingga pengarsipan digital serta retensi file.

Sistem juga dilengkapi dengan pemantauan **SLA (Service Level Agreement)** jam kerja secara realtime, monitoring log aktivitas audit, dan modul penanganan kendala internal jaringan kantor (LAN).

---

## 🗺️ Peta dan Struktur Dokumentasi

Folder `docs/` ini tersusun atas modul-modul dokumentasi teknis dan fungsional berikut:

| No | Berkas Dokumentasi | Fokus & Deskripsi Utama |
|---|---|---|
| 1 | [01 - Arsitektur & Teknologi](01-arsitektur-dan-teknologi.md) | Tech stack, struktur modul Laravel 12, arsitektur keamanan, blind index NIP, dan sistem event/SSE realtime. |
| 2 | [02 - Alur & Proses Bisnis](02-alur-dan-proses-bisnis.md) | Alur 10 tahap persuratan, mekanisme revisi & penolakan, kalkulasi SLA jam kerja, dan verifikasi QR publik. |
| 3 | [03 - Peran & Hak Akses (RBAC)](03-peran-dan-hak-akses.md) | Matriks perizinan 6 role pengguna, otorisasi Kasubbag TU, fungsi khusus IT Support, dan alur switch account. |
| 4 | [04 - Basis Data & Skema Model](04-basis-data-dan-skema.md) | Diagram relasi entitas (ERD), skema tabel database, status enum, dan relasi Eloquent ORM. |
| 5 | [05 - Panduan Pengguna & Admin](05-panduan-pengguna.md) | Manual operasional langkah demi langkah untuk Pegawai Pengusul, Administrator Verifikator, dan Kasubbag TU. |
| 6 | [06 - Automasi & Scheduler (Cron)](06-automasi-dan-cron.md) | Konfigurasi Laravel Schedule, pembersihan naskah kedaluwarsa, pembersihan berkas yatim, dan notifikasi SLA. |
| 7 | [07 - Panduan Deployment Server](07-panduan-deployment-server.md) | Setup produksi di Ubuntu Server Linux, Apache/Nginx, perizinan file storage, dan panduan mitigasi LAN tanpa SMTP. |

---

## 💻 Ringkasan Spesifikasi Teknis

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL / MariaDB (Mendukung SQLite untuk pengujian)
- **Frontend**: Blade Templating, Tailwind CSS, Vanilla JS / Alpine JS, Bootstrap Icons, Chart.js
- **Pengolah Berkas**:
  - `phpoffice/phpword`: Pemrosesan dan manipulasi dokumen Word (.docx)
  - `maatwebsite/excel`: Ekspor rekapitulasi data surat & SLA ke format Excel
- **Keamanan**:
  - Blind Index Hashing (HMAC-SHA256) untuk NIP agar tetap searchable namun terenkripsi
  - Enkripsi PIN Tanda Tangan Elektronik (TTE)
  - Proteksi Brute-force via Route Throttling (Rate Limiter)
  - Session Revocation lintas perangkat
  - Otorisasi Berjenjang berbasis Role & Middleware
- **Environment Produksi**: Ubuntu Server Linux (On-Premise / LAN Kantor)

---

## 🚀 Quick Navigation

- Ingin memahami bagaimana alur 10 tahap persuratan berjalan? 👉 Baca [Alur & Proses Bisnis](02-alur-dan-proses-bisnis.md)
- Ingin melihat matriks role dan siapa yang berhak memproses surat? 👉 Baca [Peran & Hak Akses](03-peran-dan-hak-akses.md)
- Ingin deploy atau update di server produksi Ubuntu? 👉 Baca [Panduan Deployment Server](07-panduan-deployment-server.md)
- Butuh panduan reset password jika email kantor mati? 👉 Baca bagian *Reset Password by Kasubbag TU* di [Panduan Pengguna](05-panduan-pengguna.md) atau [Panduan Deployment](07-panduan-deployment-server.md)

---
*Dibuat untuk Tim Pengembang dan Pengelola Sistem Balai Pengelolaan Sarana dan Utilitas Metrologi Legal (BP SUML).*
