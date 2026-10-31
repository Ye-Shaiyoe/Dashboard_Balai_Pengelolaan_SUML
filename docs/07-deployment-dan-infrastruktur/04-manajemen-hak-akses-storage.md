# 🛡️ Manajemen Hak Akses Direktori & Izin Storage
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan tata kelola izin berkas (*file permissions*), kepemilikan grup web server (`www-data`), serta langkah-langkah pencegahan galat **HTTP 500 Internal Server Error** akibat masalah hak akses direktori.

---

## 1. Penyebab Umum Galat HTTP 500 di Ubuntu

Pada deployment Laravel di Ubuntu Server, lebih dari 80% kemunculan layar galat HTTP 500 disebabkan oleh ketidakmampuan PHP-FPM atau Nginx menulis (*write permission denied*) ke direktori:
- `storage/framework/views/`: Kompilasi cache Blade
- `storage/framework/sessions/`: Penyimpanan sesi pengguna jika menggunakan driver file
- `storage/logs/`: Penulisan berkas `laravel.log`
- `bootstrap/cache/`: Penulisan cache konfigurasi dan service provider

---

## 2. Prosedur Standar Hak Kepemilikan (*Chown*) & Izin (*Chmod*)

Jalankan perintah berikut di direktori proyek:

```bash
cd /var/www/dashboard-tubpsuml

# 1. Atur kepemilikan direktori ke user web server Ubuntu (www-data)
sudo chown -R www-data:www-data /var/www/dashboard-tubpsuml

# 2. Berikan izin baca-eksekusi pada direktori secara umum (755)
sudo find /var/www/dashboard-tubpsuml -type d -exec chmod 755 {} \;

# 3. Berikan izin baca-tulis pada berkas kode (644)
sudo find /var/www/dashboard-tubpsuml -type f -exec chmod 644 {} \;

# 4. Berikan izin tulis penuh (775) khusus direktori storage & cache
sudo chmod -R 775 /var/www/dashboard-tubpsuml/storage
sudo chmod -R 775 /var/www/dashboard-tubpsuml/bootstrap/cache
```

---

## 3. Menjamin Izin Direktori Berkas Privat

Untuk direktori dokumen kedinasan terisolasi:
- `storage/app/private/surat/`
- `storage/app/private/lampiran/`
- `storage/app/private/tte/`

Pastikan direktori-direktori tersebut berpemilik `www-data:www-data` dengan mode `770` atau `775`. Hal ini menjamin proses PHP-FPM dapat menyimpan naskah Word dan membubuhkan tanda tangan elektronik, sementara pengguna biasa di server tidak dapat membuka berkas secara sembarangan.

