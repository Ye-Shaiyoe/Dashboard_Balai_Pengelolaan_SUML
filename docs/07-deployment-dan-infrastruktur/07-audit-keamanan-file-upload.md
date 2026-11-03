# 🔐 Audit Keamanan File Upload — Admin Settings, Users & Profil

**Proyek:** Dashboard TU BP SUML (Laravel 12)
**Tanggal audit:** 3 November 2026
**Lingkup:** seluruh titik unggah berkas (*file upload*) yang dapat diakses dari area **Admin → Settings** dan **halaman Settings/Profil pengguna**, beserta validasi, penyimpanan, penyajian, dan pembatasan aksesnya.
**Metode:** tinjauan kode statis (controller, form request, route, konfigurasi disk/web server), pemindaian seluruh pemanggilan `hasFile()`, `store()`, `Storage::`, dan pengujian otomatis.

---

## 1. Ringkasan Eksekutif

Ditemukan **1 kerentanan eksekusi kode jarak jauh (RCE)**, **1 kerentanan path traversal pada Settings → Logs**, dan **1 kebocoran data biometrik TTE**. Seluruh temuan kritis/tinggi sudah diperbaiki pada commit ini, ditambah pengerasan (*defense-in-depth*) pada penyimpanan dan pembatasan laju unggah.

| ID | Tingkat | Temuan | Status |
|---|---|---|---|
| V-01 | 🔴 KRITIS | RCE lewat ekstensi palsu pada upload foto profil & TTE (`data:image/php;base64,...`) | ✅ Diperbaiki |
| V-02 | 🔴 KRITIS | Path traversal di **Settings → Logs** (baca/unduh/hapus file arbitrer, mis. `.env`) | ✅ Diperbaiki |
| V-03 | 🟠 TINGGI | File tanda tangan TTE tersimpan di disk **public** dan dapat diakses tanpa login | ✅ Diperbaiki + migrasi data |
| V-04 | 🟡 SEDANG | `Settings → Template`: hapus file memakai path arbitrer; unggah memakai ekstensi dari client | ✅ Diperbaiki |
| V-05 | 🟡 SEDANG | Unggah ulang berkas admin/Arsiparis hanya memvalidasi `mimes`, tanpa cek MIME konten | ✅ Diperbaiki |
| V-06 | 🟡 SEDANG | Rate limiter `upload` sudah didefinisikan tetapi tidak dipakai di endpoint unggah | ✅ Diperbaiki |
| V-07 | 🟡 SEDANG | Directory listing & akses langsung `storage/app/private`, `.env` bila DocumentRoot menunjuk root proyek | 🛡️ Mitigasi (perlu konfigurasi server) |
| V-08 | 🟢 RENDAH | Payload base64 tanpa batas ukuran (potensi disk/memory exhaustion) | ✅ Diperbaiki |
| V-09 | ℹ️ INFO | **Settings → Users & File-Surat tidak memiliki form upload**; area admin yang mengunggah adalah Profil (foto & TTE), Template, dan berkas surat | — |
| V-10 | 🟡 SEDANG | *Zip bomb*/XXE pada preview DOCX unggahan user (dibuka admin) | ✅ Diperbaiki |

> Catatan penting: pada halaman **Admin → Settings → Users** tidak ada input berkas apa pun. Yang ditampilkan hanya foto profil dan tanda tangan milik pegawai. Karena itu, permukaan serangan *upload* di area ini adalah **foto profil dan TTE** (dikelola dari `/profile`), **template surat**, serta **unggah ulang berkas surat oleh Arsiparis**.

---

## 2. Detail Temuan

### V-01 — RCE via ekstensi palsu pada upload gambar (KRITIS)

**Lokasi sebelum perbaikan:**
- `app/Http/Controllers/ProfileController.php` (upload `cropped_photo`, baris ± 64–100)
- `app/Http/Controllers/ProfileController.php` (upload `cropped_signature`, baris ± 308–350)

**Akar masalah.** Untuk unggahan hasil *crop* (base64), kode lama mengambil ekstensi dari data URI yang dikirim client:

```php
$imageTypeAux = explode('image/', $imageParts[0]);
$imageType    = explode(';', $imageTypeAux[1])[0];   // ← dikendalikan penyerang
$fileName     = 'profile_photos/' . uniqid() . '.' . $imageType;
Storage::disk('public')->put($fileName, base64_decode(...));
```

Validasi yang ada hanya memeriksa *magic bytes* (`isValidImageMagicBytes`), sehingga file **polyglot** (header PNG + kode PHP di belakangnya) tetap lolos. Dengan mengirim:

```
cropped_photo = data:image/php;base64,<PNG_1x1 + "<?php system($_GET['c']); ?>">
```

server menulis `storage/app/public/profile_photos/<uniqid>.php`. Karena `public/storage` adalah *junction*/symlink ke `storage/app/public` dan Apache XAMPP mengeksekusi **semua** file `*.php` di bawah DocumentRoot, permintaan `GET /storage/profile_photos/<uniqid>.php` akan **menjalankan kode PHP** → eksekusi perintah di server. Jalur TTE (`cropped_signature`) memiliki cacat yang sama.

**Dampak:** kompromi total server (baca DB, `.env`, kredensial, pivot ke jaringan kantor), cukup dengan akun pengguna biasa.

**Perbaikan.**
1. Service baru `app/Services/ImageUploadService.php` yang:
   - hanya menerima data URI `png|jpeg|gif|webp`,
   - membatasi ukuran 2 MB dan dimensi 4096 px,
   - **men-decode ulang gambar dengan GD menjadi PNG murni** (`imagecreatefromstring` → `imagepng`), sehingga metadata/payload tambahan (polyglot) terbuang,
   - selalu memberi nama file `Str::uuid() . '.png'` — ekstensi tidak lagi berasal dari client.
2. TTE dipindahkan ke disk `private` (lihat V-03).
3. Aturan validasi diperketat: `cropped_photo`/`cropped_signature` maksimal 3.000.000 karakter; `profile_photo_raw` ditambahkan; `svg` dihapus dari daftar `mimes`.
4. *Defense-in-depth*: `storage/app/public/.htaccess` menolak eksekusi `.php|.phtml|.phar|...` dan menonaktifkan directory listing, sehingga seandainya ada file aneh yang lolos, file tersebut tidak dapat dijalankan.

**Bukti uji:** `tests/Unit/ImageUploadServiceTest.php` — 5 pengujian lulus, termasuk penolakan `data:image/php` dan pembersihan payload PHP pada PNG polyglot.

---

### V-02 — Path traversal pada Settings → Logs (KRITIS)

**Lokasi sebelum perbaikan:** `app/Http/Controllers/Admin/LogController.php`

```php
$selectedFile = $request->get('file');                     // index()
if ($selectedFile && File::exists($logPath.'/'.$selectedFile)) { ... }

$path = storage_path('logs/' . $file);                     // download(), clear(), delete()
return Response::download($path);
```

Tidak ada validasi nama file. Penyerang dengan role admin apa pun (Arsiparis, Kasubbag TU, Kepala Balai) dapat mengirim `?file=../../.env` pada halaman Logs, atau memanggil route `download/clear/delete` dengan `..\..\.env` (Windows) / `../../.env`. Dampaknya:
- **unduh** `.env` → kredensial PostgreSQL, `APP_KEY` (bisa memalsukan sesi/enkripsi), kredensial SMTP;
- **hapus/kosongkan** file aplikasi apa pun yang dapat ditulis (mis. `.env`, file konfigurasi) → *denial of service* dan eskalasi.

**Perbaikan.** Semua akses file sekarang melewati `resolveLogPath()` yang:
- mengambil `basename()` (membuang `/` maupun `\`),
- mewajibkan pola `^[A-Za-z0-9][A-Za-z0-9._-]*\.log$`,
- mewajibkan hasil `realpath()` berada **tepat di dalam** `storage/logs` (`dirname($path) === realpath(storage_path('logs'))`).

Route juga diberi constraint `->where('file', '[A-Za-z0-9._-]+')`.

---

### V-03 — TTE tersimpan di disk publik (TINGGI)

**Lokasi sebelum perbaikan:** `ProfileController::updateTte()` + `resources/views/admin/Settings/user/show.blade.php`

Tanda tangan disimpan lewat `Storage::disk('public')->put('signatures/...')` dan ditampilkan dengan `Storage::url($user->signature_path)`. Akibatnya:
- file TTE dapat diakses **tanpa login** melalui `/storage/signatures/<nama>.png`;
- nama file `uniqid()` mudah ditebak/di-enumerasi, dan directory listing Apache (`Options Indexes`) membuat seluruh daftar file terlihat;
- halaman admin bahkan mengklaim “Private Storage (tidak accessible publik)”, padahal tidak.

**Perbaikan.**
1. TTE baru disimpan di disk `private` (`storage/app/private/signatures/`).
2. Penyajian hanya lewat rute terautentikasi: `GET /profile/signature/{user?}` dengan aturan: pemilik boleh melihat miliknya, admin boleh melihat semua, selain itu `403`.
3. Respons memakai `Content-Type` gambar, `Cache-Control: private, no-store`.
4. File lama dipindahkan dari disk publik ke privat dengan command idempoten:
   ```bash
   php artisan signatures:privatize
   ```
   (sudah dijalankan pada mesin ini — 5 file dipindahkan, salinan publik dihapus).
5. Tampilan admin & profil memakai `route('profile.signature', ...)`, bukan URL storage langsung.

> Catatan: foto profil tetap di disk `public` karena memang ditampilkan sebagai avatar. Namun isinya sekarang dijamin hanya PNG hasil *re-encode*.

---

### V-04 — Settings → Template: path arbitrer & ekstensi dari client (SEDANG)

**Lokasi:** `app/Http/Controllers/Admin/TemplateSuratController.php`

- `destroy()` menerima `path` mentah lalu `Storage::delete($request->path)`. Walau Flysystem menolak `..`, controller tetap menerima path folder lain di dalam disk `private` (mis. menghapus berkas surat jika path-nya diketahui).
- `store()` memakai `getClientOriginalExtension()` untuk nama file, sehingga ekstensi mengikuti client.

**Perbaikan:** nama file diambil dari `basename()`, diwajibkan berada di folder `templates/`, dan harus ada; ekstensi unggahan ditentukan dari `$file->extension()` (deteksi server) dengan whitelist `docx|doc|pdf`; nama bentrok diberi suffix acak agar tidak menimpa template lain. Unduhan juga menolak ekstensi di luar whitelist.

---

### V-05 — Validasi MIME konten pada unggah ulang berkas (SEDANG)

**Lokasi:**
- `app/Http/Controllers/Admin/SuratController.php::uploadFileAdmin()`
- `app/Http/Controllers/User/SuratController.php::reuploadFile()`

Keduanya hanya memakai `mimes:` (berbasis tebakan ekstensi dari MIME) tanpa `mimetypes:`. Artinya file dengan konten yang tidak sesuai (mis. arsip ZIP yang dipoles) bisa lolos aturan tertentu. Ditambahkan `mimetypes:` yang eksplisit untuk `docx/doc/pdf` dan lampiran (`pdf/jpeg/png/xlsx/xls`), sejalan dengan `StoreSuratRequest`/`UpdateSuratRequest`.

---

### V-06 — Rate limit unggah tidak diterapkan (SEDANG)

`AppServiceProvider` mendefinisikan limiter `upload` (20/menit) tetapi tidak dipakai di route mana pun, sehingga unggah foto/TTE/template dapat di-spam untuk menghabiskan disk dan CPU (setiap unggah gambar kini di-*re-encode*).

**Perbaikan:** `throttle:upload` dipasang pada:
- `PATCH /profile` (foto profil),
- `PATCH /profile/tte` (tanda tangan),
- `POST /Template`,
- `POST /Surat/{surat}/upload-file-admin`.

---

### V-07 — Eksposur direktori storage & `.env` (SEDANG, butuh konfigurasi server)

XAMPP pada mesin ini memakai `DocumentRoot "C:/xampp/htdocs"` dengan `Options Indexes FollowSymLinks`. Bila aplikasi diakses lewat `http://localhost/dashboard-tubpsuml/` (bukan `.../public/`):
- `.env`, `.git/`, `storage/app/private/*`, dan kode sumber dapat diunduh langsung;
- `/storage/profile_photos/` dapat di-*listing*.

**Mitigasi yang sudah ditambahkan:**
- `storage/app/public/.htaccess` → `Options -Indexes` + menolak eksekusi skrip.
- `storage/app/private/.htaccess` → `Require all denied`.
- Kedua file `.htaccess` dikecualikan dari `.gitignore` agar ikut ter-deploy.

**Wajib dilakukan di server:** arahkan DocumentRoot/vhost ke folder **`public/`**, bukan root proyek. Untuk Nginx, tambahkan penolakan eksekusi PHP di `/storage/`.

---

### V-08 — Batas ukuran payload base64 (RENDAH)

Sebelumnya `cropped_photo`/`cropped_signature` hanya `nullable|string` tanpa batas, sehingga body hingga `post_max_size` dapat diproses dan ditulis ke disk. Sekarang dibatasi 3.000.000 karakter (≈2 MB setelah decode) dan service menolak payload di atas 2 MB.

---

### V-09 — Catatan lingkup: Settings → Users & File-Surat

- `Settings → Users` (`admin.Settings.user.index/show`) **tidak memiliki form upload**. Data yang ditampilkan: foto profil (`Storage::url`), TTE (kini lewat rute terproteksi), statistik surat, dan aksi reset password/ubah role/hapus.
- `Settings → File-Surat` hanya **menghapus** berkas (tidak mengunggah), dan sudah dibatasi pada surat berstatus `selesai`/`ditolak`.
- `Settings → Logs` diperbaiki pada V-02.

---

### V-10 — Zip bomb & XXE pada preview DOCX (SEDANG)

**Lokasi:** `app/Services/DocxToHtmlConverter.php`

Saat admin membuka pratinjau surat `.docx` milik user, seluruh isi ZIP (termasuk `word/media/*`) didekompresi ke memori tanpa batas. File 5 MB dapat mengembang menjadi ratusan MB/GB (*zip bomb*) → server kehabisan memori. Salah satu `loadXML()` pada `document.xml.rels` juga belum memakai `LIBXML_NONET`.

**Perbaikan:**
- batas 15 MB untuk `word/document.xml`,
- batas 5 MB per gambar dan 20 MB total untuk `word/media/*` (dicek lewat `statIndex()` **sebelum** dekompresi),
- `LIBXML_NONET` pada kedua `loadXML()`.

---

## 3. Berkas yang Diubah / Ditambahkan

| Berkas | Perubahan |
|---|---|
| `app/Services/ImageUploadService.php` | **Baru** — validasi + re-encode PNG, batas ukuran/dimensi |
| `app/Http/Controllers/ProfileController.php` | Upload foto & TTE memakai service; TTE ke disk privat; rute `signature()`; hapus validasi magic-byte lama |
| `app/Http/Requests/ProfileUpdateRequest.php` | `profile_photo_raw`, hapus `svg`, batas `cropped_photo` |
| `app/Http/Controllers/Admin/LogController.php` | `resolveLogPath()` anti path traversal untuk index/download/clear/delete |
| `app/Http/Controllers/Admin/TemplateSuratController.php` | Whitelist ekstensi server-side, `basename()` pada destroy, anti-overwrite |
| `app/Http/Controllers/Admin/SuratController.php` | `mimetypes` pada unggah berkas admin |
| `app/Http/Controllers/User/SuratController.php` | `mimetypes` pada unggah ulang |
| `app/Console/Commands/PrivatizeSignatures.php` | **Baru** — migrasi TTE public → private |
| `app/Services/DocxToHtmlConverter.php` | Batas ukuran XML/gambar (zip bomb) + `LIBXML_NONET` |
| `routes/web.php` | Rute `profile.signature`, `throttle:upload`, constraint nama file log |
| `resources/views/profile/partials/tte-management.blade.php` | URL TTE via route, tampilkan error validasi |
| `resources/views/admin/Settings/user/show.blade.php` | URL TTE via route, path ditampilkan sebagai nama file |
| `storage/app/public/.htaccess` | **Baru** — larang eksekusi skrip & directory listing |
| `storage/app/private/.htaccess` | **Baru** — tolak akses HTTP langsung |
| `storage/app/{public,private}/.gitignore` | Izinkan `.htaccess` ikut ter-commit |
| `tests/Unit/ImageUploadServiceTest.php` | **Baru** — 5 pengujian keamanan gambar |
| `docs/01-.../04-mekanisme-keamanan-dan-privasi.md` | Path TTE & tabel throttle disesuaikan |
| `docs/07-.../04-manajemen-hak-akses-storage.md` | Path TTE disesuaikan |

---

## 4. Verifikasi

```bash
# 1. Lint seluruh berkas yang diubah
php -l app/Services/ImageUploadService.php
php -l app/Http/Controllers/ProfileController.php
# ... (semua berkas: tidak ada syntax error)

# 2. Rute terdaftar
php artisan route:list --path=profile
# → profile.signature/{user?} terdaftar

# 3. Migrasi TTE
php artisan signatures:privatize
# → 5 file dipindahkan, 0 dilewati

# 4. Test suite
php artisan test
# → 34 passed; 5 gagal karena masalah LAMA: query PostgreSQL
#   EXTRACT(YEAR FROM ...) tidak didukung SQLite in-memory
#   (ExampleTest & SuratFilterDateTest), bukan akibat perubahan ini.
```

Pengujian keamanan gambar (`tests/Unit/ImageUploadServiceTest.php`):

```
✓ data uri gambar valid dinormalisasi menjadi png
✓ data uri dengan tipe palsu ditolak
✓ data uri bukan gambar ditolak
✓ polyglot png berisi kode php dibersihkan saat re-encode
✓ gambar melebihi batas ukuran ditolak
```

---

## 5. Checklist Deployment (Wajib)

1. **Migrasi TTE** (setelah `git pull`):
   ```bash
   php artisan signatures:privatize
   ```
   Idempoten; aman dijalankan berulang.
2. **DocumentRoot harus ke `public/`**
   - Apache: `DocumentRoot /var/www/dashboard-tubpsuml/public`
   - Nginx: `root /var/www/dashboard-tubpsuml/public;`
   Jangan pernah mengarahkan web root ke root proyek — `.env` akan terekspos.
3. **Pastikan symlink storage ada:**
   ```bash
   php artisan storage:link
   ```
4. **Nginx** — tolak eksekusi skrip di folder unggahan:
   ```nginx
   location ^~ /storage/ {
       autoindex off;
       location ~* \.(php|phtml|phar|pl|py|sh|cgi)$ { deny all; }
   }
   ```
5. **Apache** — pastikan `AllowOverride All` agar `.htaccess` di folder storage berlaku.
6. **Reset kredensial** bila `.env` pernah dapat diakses lewat web: ganti password database, `APP_KEY` (hati-hati: `APP_KEY` mengubah dekripsi NIP & `nip_hash`), kredensial SMTP.
7. **`APP_DEBUG=false`** dan `APP_ENV=production` di server.
8. Verifikasi manual:
   - buka `/storage/signatures/<nama>.png` → harus **404**;
   - `GET /Admin/Settings/Logs/download/..%5C..%5C.env` → harus ditolak (redirect + pesan “File not found”);
   - unggah foto & TTE → tersimpan `.png`, TTE hanya tampil lewat `/profile/signature/...`;
   - unggah file `*.php` yang menyamar sebagai gambar → ditolak.

---

## 6. Rekomendasi Lanjutan (Belum Diterapkan)

1. **Antivirus/scanning** untuk lampiran surat (mis. ClamAV) — saat ini hanya validasi tipe.
2. **Batas ukuran hasil re-encode** atau *resize* otomatis untuk foto profil (saat ini maksimal 4096 px, bisa menghasilkan PNG besar).
3. **Audit log** khusus untuk aksi unggah/hapus berkas sensitif (template, TTE) agar ada jejak forensik.
4. **Hapus `storage/app/public/signatures`** dari backup lama dan pastikan tidak ada salinan TTE di folder publik (sudah dipindahkan pada mesin ini).
5. **Kebijakan retensi foto profil & TTE** (hanya simpan versi terbaru — sudah otomatis menghapus versi lama).
