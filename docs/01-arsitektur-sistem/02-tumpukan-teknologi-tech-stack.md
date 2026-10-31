# 💻 Tumpukan Teknologi (Technology Stack)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini memaparkan seluruh tumpukan teknologi, pustaka pihak ketiga (*third-party libraries*), ekstensi PHP yang dibutuhkan, serta dependensi antarmuka pengguna yang digunakan dalam pengembangan sistem.

---

## 1. Ringkasan Ekosistem Teknologi

| Lapisan Sistem | Teknologi / Pustaka | Versi Minimum | Deskripsi / Peran Utama |
|---|---|---|---|
| **Bahasa Pemrograman** | PHP | 8.2+ / 8.3 | Fitur Modern (Typed Properties, Readonly, Match expressions, JIT) |
| **Framework Web** | Laravel | 12.x | Routing, Eloquent ORM, Blade, Middleware, Task Scheduling |
| **Autentikasi** | Laravel Breeze | 2.4 | Scaffold otentikasi kustom untuk NIP, Email, dan Switch Account |
| **Basis Data Relasional** | PostgreSQL | 14+ / 16+ | Driver `pgsql`, integritas relasional, JSONB auditing, indexing cepat |
| **Web Server** | Nginx / Apache | 1.18+ / 2.4+ | Reverse proxy, penanganan SSL, buffering upload hingga 30 MB |
| **Frontend Styling** | Tailwind CSS | 3.x | Styling utility-first, responsif lintas resolusi desktop & ponsel |
| **Interaktivitas UI** | Alpine.js / Vanilla JS | Modern ES6 | Manajemen interaksi modal, AJAX table reload, event reactivity |
| **Visualisasi Data** | Chart.js | 4.x | Grafik distribusi jenis surat, grafik tren pengajuan, statistik SLA |
| **Pengolah Dokumen** | `phpoffice/phpword` | ^1.1 | Parsing, manipulasi metadata dan inspeksi dokumen Microsoft Word (.docx) |
| **Pengolah Spreadsheet** | `maatwebsite/excel` | ^3.1 | Pembuatan laporan rekapitulasi data surat & log SLA (.xlsx) |
| **Icon Library** | Bootstrap Icons & Lucide | Terbaru | Set ikon antarmuka grafis di navigasi dan tabel |

---

## 2. Kebutuhan Ekstensi PHP Server

Untuk memastikan framework Laravel 12 dan pustaka pemrosesan dokumen dapat bekerja optimal di Ubuntu Linux, ekstensi berikut wajib aktif:

```bash
# Daftar ekstensi PHP yang wajib terpasang:
php8.2-fpm        # FastCGI Process Manager untuk Nginx
php8.2-cli        # Antarmuka CLI untuk Artisan dan Crontab
php8.2-pgsql      # Driver PostgreSQL PDO
php8.2-mbstring   # Manipulasi string multi-byte (karakter naskah dinas)
php8.2-xml        # Parsing file XML untuk format .docx Word
php8.2-zip        # Kompresi & dekompresi file Word (.docx adalah arsip zip)
php8.2-gd         # Pemrosesan gambar avatar dan pembubuhan TTE
php8.2-curl       # Komunikasi HTTP internal / API
php8.2-bcmath     # Perhitungan matematika presisi tinggi
```

---

## 3. Komponen Frontend & Dependency Assets

### A. Kompilasi Aset via Vite
Aplikasi menggunakan bundler **Vite** untuk mengompilasi lembar gaya Tailwind CSS dan file JavaScript:
```bash
# Menjalankan build aset produksi
npm install
npm run build
```
File hasil kompilasi ditempatkan di direktori `public/build/` dengan *cache busting* berbasis hash.

### B. Grafik & Pelaporan Statistik
Modul grafik persuratan di `/Admin/Chart` dan `/statistik` menggunakan **Chart.js** yang dirender secara fleksibel di sisi klien:
- Diagram Donat (*Doughnut Chart*): Sebaran jenis surat dinas (Nota Dinas, SK, Surat Undangan, dll).
- Diagram Batang (*Bar Chart*): Volume pemrosesan naskah per bulan.
- Kartu Indikator SLA: Visualisasi performa kecepatan penyelesaian naskah terhadap target 30 jam kerja.

### C. Manipulasi File `.docx` & Excel
- Naskah persuratan wajib berupa file `.docx` agar kompatibel dengan template baku kedinasan. `phpoffice/phpword` digunakan untuk membaca struktur naskah dan memvalidasi keutuhan format berkas.
- Modul ekspor Excel menggunakan `maatwebsite/excel` dengan antarmuka streaming memory yang efisien, sehingga ekspor ratusan baris data tidak menyebabkan *memory limit exhaustion*.

