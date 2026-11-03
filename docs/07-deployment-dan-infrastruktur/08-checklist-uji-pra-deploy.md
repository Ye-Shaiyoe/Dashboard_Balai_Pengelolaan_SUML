# ✅ Checklist Uji Pra-Deploy (Manual + Otomatis)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini adalah daftar uji yang harus dijalankan **sebelum** dan **sesaat setelah** deploy, dengan fokus pada fitur unggah berkas (foto profil, TTE, template, berkas surat), halaman **Admin → Settings**, serta konfigurasi server yang menentukan aman/tidaknya hasil deploy.

**Cara pakai:** kerjakan dari atas ke bawah, centang setiap item, dan catat hasilnya di bagian *Sign-off* di akhir. Perkiraan total waktu: **45–60 menit**.

> Gunakan **data uji**, bukan data produksi. Untuk uji hapus massal / hapus file, lakukan di staging atau setelah backup.

---

## 0. Persiapan (10 menit)

### 0.1 Perintah wajib setelah `git pull`

```bash
cd /var/www/dashboard-tubpsuml

php artisan optimize:clear
php artisan migrate --force
php artisan signatures:privatize      # WAJIB: pindahkan TTE lama ke disk private
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

- [ ] `signatures:privatize` selesai tanpa error (output: `0 file dipindahkan` bila sudah pernah dijalankan).
- [ ] `public/storage` ada dan menunjuk ke `storage/app/public` (`ls -la public/storage`).
- [ ] `storage/app/public/.htaccess` dan `storage/app/private/.htaccess` ikut ter-deploy (`ls -la storage/app/public/.htaccess`).
- [ ] Folder `storage/app/public/signatures/` **kosong / tidak ada**.
- [ ] `storage/` dan `bootstrap/cache/` dapat ditulis `www-data`.

### 0.2 Akun uji

Siapkan minimal 4 akun (buat dari **Admin → Settings → Users → Buat Akun**):

| Peran | Kegunaan |
|---|---|
| `user` (pegawai) | upload foto, TTE, ajukan & revisi surat |
| `admin_aspirasi` (Arsiparis) | verifikasi tahap 2, upload file admin, template |
| `admin_kasubbag_tu` | halaman Settings, reset password |
| `admin_kepala_balai` | approval tahap 4 (opsional) |

- [ ] Semua akun bisa login dan dashboard tampil tanpa error 500.

### 0.3 Alat bantu

- [ ] Browser dengan DevTools (tab **Network** & **Console**).
- [ ] `curl` (untuk uji header & akses langsung).
- [ ] Terminal server (untuk cek file & log).

---

## 1. Uji Fungsional Cepat — P0 (15 menit)

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1.1 | Login sebagai `user` → buka `/profile` | Halaman profil tampil; bagian foto & TTE terlihat |
| 1.2 | Klik avatar → pilih foto → **Crop** → Simpan | Avatar berubah; tidak ada error; nama file di `storage/app/public/profile_photos/` berakhiran **`.png`** |
| 1.3 | Refresh halaman & buka dashboard | Foto baru tetap tampil (navbar + halaman profil) |
| 1.4 | Di `/profile`, gambar tanda tangan → isi PIN → Simpan | TTE tampil; preview memakai URL `/profile/signature...` (bukan `/storage/...`) |
| 1.5 | Refresh halaman profil | TTE tetap tampil dari rute `/profile/signature` |
| 1.6 | Login sebagai admin → **Settings → Users** → klik 👁️ pada user | Detail user tampil, termasuk gambar TTE (dimuat lewat `/profile/signature/{uuid}`) |
| 1.7 | Sebagai admin, buka **Settings → Template** → upload `.docx` | Template muncul di daftar; tombol unduh menghasilkan file `.docx` |
| 1.8 | Hapus template yang baru diunggah | Template hilang; tidak ada error |
| 1.9 | Login `user` → **Surat → Ajukan** → upload `.docx` + lampiran PDF → kirim | Surat masuk daftar; preview PDF/lampiran bekerja |
| 1.10 | Sebagai Arsiparis, buka surat tahap 2 → **Perbarui File** → upload `.docx` | Muncul pesan sukses; user menerima notifikasi “File Surat Diperbarui Admin” |
| 1.11 | Sebagai user, buka surat yang ditolak → upload ulang Word/lampiran | Status berubah menjadi `revisi`; tidak ada error validasi yang salah |
| 1.12 | **Settings → Logs** → pilih file log → klik **Unduh File** | File `*.log` terunduh |
| 1.13 | **Settings → Logs** → **Kosongkan** pada file log selain `laravel.log` | Pesan sukses; isi file kosong |
| 1.14 | **Settings → File-Surat** → hapus file pada surat `selesai`/`ditolak` | File terhapus, metadata surat tetap ada |
| 1.15 | Ganti nama/email/NIP di `/profile` lalu simpan | Data tersimpan; tidak ada 429 (kecuali >20 simpan/menit) |

---

## 2. Uji Keamanan — P0 (20 menit)

> Ini bagian yang membuktikan perbaikan benar-benar bekerja. Jika satu saja gagal, **jangan buka aplikasi ke pengguna**.

### 2.1 RCE lewat ekstensi gambar

**Uji A — tipe data URI palsu harus ditolak**

1. Login sebagai `user`, buka `/profile`.
2. Buka DevTools → **Console**, tempel:

```js
const png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=';
document.getElementById('cropped_photo').value = 'data:image/php;base64,' + png;
document.querySelector('form[action$="/profile"]').submit();
```

- [ ] Halaman kembali ke `/profile` dengan pesan error **“Format gambar tidak valid…”**.
- [ ] Avatar **tidak berubah**.
- [ ] Tidak ada file `.php` baru di `storage/app/public/profile_photos/`:
  ```powershell
  Get-ChildItem storage\app\public\profile_photos\*.php -ErrorAction SilentlyContinue
  ```
  ```bash
  ls storage/app/public/profile_photos/*.php 2>/dev/null
  ```

**Uji B — PNG polyglot harus dibersihkan**

1. Masih di Console, tempel:

```js
const b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=';
const bin = atob(b64) + '<?php echo "pwned"; ?>';
document.getElementById('cropped_photo').value = 'data:image/png;base64,' + btoa(bin);
document.querySelector('form[action$="/profile"]').submit();
```

- [ ] Avatar berubah (gambar valid diterima).
- [ ] File tersimpan tetap **`.png`**.
- [ ] Tidak ada teks `<?php` di file hasil unggahan:
  ```powershell
  $f = Get-ChildItem storage\app\public\profile_photos\*.png | Sort-Object LastWriteTime -Descending | Select-Object -First 1
  Select-String -Path $f.FullName -Pattern '<?php' -SimpleMatch   # harus kosong
  ```
  ```bash
  grep -a '<?php' storage/app/public/profile_photos/*.png    # harus kosong
  ```

> Jika Uji A/B tidak bisa dijalankan di browser, jalankan otomatis:
> `php artisan test --filter=ImageUploadService`

### 2.2 Path traversal di Settings → Logs

Login sebagai admin, lalu buka URL berikut:

| URL | Hasil yang diharapkan |
|---|---|
| `/Admin/Settings/Logs?file=../../.env` | Tetap menampilkan log biasa; **tidak** menampilkan isi `.env` |
| `/Admin/Settings/Logs/download/..%5C..%5C.env` | **404** atau redirect dengan pesan “File not found” |
| `/Admin/Settings/Logs/download/..%2F..%2F.env` | **404** (tidak cocok constraint route) |
| `/Admin/Settings/Logs/download/laravel.log` | File log terunduh (fungsi normal tetap jalan) |
| `/Admin/Settings/Logs/delete/laravel.log` (DELETE + CSRF) | Ditolak, `laravel.log` tidak terhapus |

- [ ] Semua baris di atas sesuai harapan.
- [ ] Halaman **Settings → Logs** tetap bisa memilih & mengunduh log normal.

### 2.3 TTE tidak lagi publik

- [ ] Klik kanan gambar TTE di `/profile` → **Copy image address** → URL harus berbentuk `.../profile/signature...`, **bukan** `.../storage/signatures/...`.
- [ ] Buka `/storage/signatures/<nama-file-lama>.png` → **403/404** (bukan gambar).
- [ ] Login sebagai user lain (bukan admin), buka `/profile/signature/<uuid-user-lain>` → **403**.
- [ ] Tanpa login (incognito), buka `/profile/signature/<uuid>` → redirect ke `/login`.

### 2.4 Directory listing & file sensitif

| URL | Hasil yang diharapkan |
|---|---|
| `/storage/profile_photos/` | **403** (bukan daftar file) |
| `/storage/app/private/` | **403/404** |
| `/.env` | **404** (DocumentRoot harus `public/`) |
| `/.git/config` | **403/404** |

- [ ] Semua sesuai harapan. Jika `.env` tampil → **STOP**, perbaiki DocumentRoot/vhost.

### 2.5 Rate limit unggah

Login sebagai `user`, buka `/profile`, jalankan di Console:

```js
const token = document.querySelector('meta[name="csrf-token"]').content;
for (let i = 1; i <= 21; i++) {
  const r = await fetch('/profile', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token },
    body: new URLSearchParams({ _method: 'PATCH', _token: token })
  });
  console.log(i, r.status);
}
```

- [ ] Permintaan ke-21 mengembalikan **429**.
- [ ] Tunggu ±1 menit, lalu simpan profil normal kembali berhasil (302).

### 2.6 Upload berbahaya / tidak sesuai tipe

| Berkas uji | Lokasi upload | Hasil yang diharapkan |
|---|---|---|
| `shell.php` di-rename `template.pdf` | Settings → Template | Ditolak (bukan PDF valid) |
| `shell.php` di-rename `.docx` (bukan ZIP valid) | Form ajukan surat | Ditolak (bukan DOCX valid) |
| `.svg` sebagai foto profil | `/profile` | Ditolak |
| Lampiran `.exe` / `.php` | Form ajukan surat | Ditolak |
| File 6 MB sebagai `file_word` | Form ajukan surat | Ditolak (maks 5 MB) |
| Lampiran 11 MB | Form ajukan surat | Ditolak (maks 10 MB) |

- [ ] Semua ditolak dengan pesan validasi yang jelas (bukan halaman 500).

### 2.7 Header keamanan

```bash
curl -I https://domain-anda/login
```

- [ ] Ada `X-Frame-Options`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy`.
- [ ] Jika HTTPS: ada `Strict-Transport-Security`.
- [ ] `Server` tidak membocorkan versi PHP yang detail (opsional).

---

## 3. Uji Regresi Alur Surat — P1 (10 menit)

Fokus: perubahan validasi upload tidak merusak alur yang sudah ada.

- [ ] Ajukan surat baru (dengan Word saja) → berhasil.
- [ ] Ajukan surat baru (Word + lampiran PDF) → berhasil.
- [ ] Simpan sebagai **draft** tanpa file → berhasil; lanjutkan draft dengan upload file → berhasil.
- [ ] Edit draft & ganti file Word → file lama terhapus, file baru dipakai.
- [ ] Tolak surat → user upload ulang → status `revisi` → admin verifikasi ulang.
- [ ] Approve tahap 2 → 3 → 4 → 5 → … → selesai; semua notifikasi masuk.
- [ ] Preview `.pdf` inline, `.docx` tampil sebagai HTML, `.xls/.xlsx` terunduh.
- [ ] Export Excel laporan & daftar surat berhasil dibuka.
- [ ] Hapus surat via permintaan user → admin approve → file ikut terhapus.

---

## 4. Uji Ukuran & Performa — P2 (5 menit)

- [ ] Upload Word 5 MB + lampiran 10 MB: selesai < 10 detik, tidak 500/504.
- [ ] Cek `storage/logs/laravel.log`: tidak ada `ERROR` baru setelah semua uji.
- [ ] Cek sisa disk (`df -h`): unggahan uji tidak memenuhi disk.
- [ ] Jalankan `php artisan queue:work --once` (bila `QUEUE_CONNECTION=database/redis`) → tidak ada job gagal.

---

## 5. Uji Infrastruktur Deploy — P0

- [ ] `APP_ENV=production`, `APP_DEBUG=false` — buka URL yang memicu error (mis. `/Admin/Settings/Logs/download/tidak-ada.log`) → tampil halaman error rapi, **tanpa stack trace**.
- [ ] DocumentRoot/vhost menunjuk ke **`public/`**, bukan root proyek.
- [ ] Nginx: blok `location ^~ /storage/` + deny `\.php` sudah ada (lihat `03-konfigurasi-web-server-nginx.md`), atau Apache: `AllowOverride All` aktif agar `.htaccess` berlaku.
- [ ] `php -m` memuat `gd`, `fileinfo`, `zip`, `pdo_pgsql`.
- [ ] **Queue worker** berjalan (WAJIB bila `QUEUE_CONNECTION` bukan `sync`):
  ```bash
  php artisan queue:work --tries=3 --timeout=120
  # produksi: gunakan supervisor/systemd
  ```
- [ ] **Scheduler** cron aktif:
  ```bash
  * * * * * cd /var/www/dashboard-tubpsuml && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Uji email: buat akun baru dari **Settings → Users** → email info login masuk (atau cek `failed_jobs`).
- [ ] Backup database + `storage/app/private` sebelum uji hapus data.
- [ ] `php artisan about` menampilkan environment & cache yang benar.

---

## 6. Uji Otomatis (CLI)

```bash
# Keamanan gambar (5 test)
php artisan test --filter=ImageUploadService

# Profil (5 test)
php artisan test tests/Feature/ProfileTest.php

# Seluruh suite
php artisan test
```

- [ ] `ImageUploadService`: **5 passed**.
- [ ] `ProfileTest`: **5 passed**.
- [ ] Suite penuh: **34 passed**. (Catatan: 5 test lama `ExampleTest` & `SuratFilterDateTest` gagal karena query PostgreSQL `EXTRACT(...)` tidak didukung SQLite in-memory — bukan akibat perubahan ini. Jika ingin suite hijau, jalankan dengan koneksi PostgreSQL atau perbaiki test tersebut.)

---

## 7. Sign-off

| Area | Diuji oleh | Tanggal | Hasil | Catatan |
|---|---|---|---|---|
| Upload foto profil | | | ☐ Lulus ☐ Gagal | |
| Upload & tampilan TTE | | | ☐ Lulus ☐ Gagal | |
| Settings → Logs | | | ☐ Lulus ☐ Gagal | |
| Settings → Template | | | ☐ Lulus ☐ Gagal | |
| Upload berkas surat | | | ☐ Lulus ☐ Gagal | |
| Uji keamanan (RCE/traversal/TTE) | | | ☐ Lulus ☐ Gagal | |
| Infrastruktur & queue | | | ☐ Lulus ☐ Gagal | |

**Keputusan:** ☐ Siap produksi ☐ Perlu perbaikan (jelaskan: ______________________)

---

## 8. Jika Ada yang Gagal

| Gejala | Kemungkinan penyebab | Tindakan |
|---|---|---|
| Semua upload gambar gagal “tidak valid” | Ekstensi `gd` tidak aktif / versi GD tanpa WebP | `php -m`, install `php8.2-gd`, restart PHP-FPM |
| Foto/TTE tidak tampil | `public/storage` symlink hilang | `php artisan storage:link` |
| TTE 404 padahal ada | `signatures:privatize` belum dijalankan / file tidak pindah | jalankan ulang command, cek `storage/app/private/signatures/` |
| TTE masih bisa diakses `/storage/...` | file lama belum dipindah / cache | jalankan command, hapus cache browser, cek folder publik kosong |
| `/storage/...` masih directory listing | `.htaccess` tidak terbaca (Apache `AllowOverride None`) atau nginx `autoindex on` | perbaiki config web server |
| Upload >2 MB gagal | `upload_max_filesize` / `post_max_size` PHP | sesuaikan `php.ini` (lihat dok. Nginx) |
| 429 saat testing | rate limit `throttle:upload` (20/menit) | tunggu 1 menit; ini perilaku yang diharapkan |
| Email akun baru tidak terkirim | queue worker tidak jalan / SMTP diblokir | jalankan worker; cek `failed_jobs`; gunakan fitur reset password Kasubbag TU sebagai fallback |
| Error 500 setelah deploy | cache lama / permission | `php artisan optimize:clear`, cek `storage/logs/laravel.log`, `chown -R www-data:www-data storage bootstrap/cache` |
