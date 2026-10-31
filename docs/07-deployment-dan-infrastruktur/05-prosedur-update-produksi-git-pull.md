# 🔄 Prosedur Pembaruan Kode di Produksi (Git Pull Checklist)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menyediakan panduan langkah-demi-langkah bagi administrator sistem saat menerapkan rilis fitur baru, pembaruan keamanan, atau perbaikan bug dari repositori Git ke server produksi tanpa menyebabkan *downtime* atau galat runtime.

---

## 1. Daftar Periksa Pembaruan Lengkap (*Release Checklist*)

Jalankan urutan perintah berikut di terminal server:

```bash
# Masuk ke direktori aplikasi
cd /var/www/dashboard-tubpsuml

# 1. Tarik commit terbaru dari branch utama (main)
git pull origin main

# 2. Perbarui paket dependensi composer (jika ada pustaka baru)
composer install --no-dev --optimize-autoloader

# 3. Jalankan migrasi database otomatis (jika ada skema/tabel baru)
php artisan migrate --force

# 4. Bangun ulang aset frontend (Tailwind & JavaScript)
npm install
npm run build

# 5. Bersihkan dan segarkan seluruh cache optimasi Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Pastikan kembali kepemilikan dan izin folder tidak berubah
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7. Mulai ulang PHP-FPM untuk membersihkan memori OPcache
sudo systemctl restart php8.2-fpm
```

---

## 2. Mengaktifkan Mode Pemeliharaan (Opsional untuk Update Skala Besar)

Jika pembaruan menyangkut perubahan struktur tabel database yang masif:

```bash
# 1. Aktifkan mode pemeliharaan (menampilkan halaman maintenance ramah pengguna)
php artisan down --secret="bpsuml-bypass-2026"

# 2. Eksekusi pembaruan Git, composer, migrate, npm
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 3. Nonaktifkan mode pemeliharaan kembali
php artisan up
```
*(Administrator yang mengakses web melalui `http://domain/bpsuml-bypass-2026` dapat melakukan pengetesan sebelum sistem dibuka kembali untuk seluruh pengguna kantor).*

