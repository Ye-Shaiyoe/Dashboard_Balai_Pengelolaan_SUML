# 🐧 Konfigurasi Crontab Server (Ubuntu Linux)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini memandu Administrator Sistem (Sysadmin/DevOps) dalam mengonfigurasi penjadwalan Linux Crontab agar seluruh background task Laravel Scheduler berjalan otomatis di server produksi Ubuntu.

---

## 1. Mekanisme Cron Heartbeat Laravel

Laravel mengadopsi pola *single cron heartbeat*. Daripada administrator mendaftarkan puluhan baris crontab Linux untuk masing-masing perintah:
- Server Linux hanya perlu menjalankan satu baris perintah `php artisan schedule:run` **setiap 1 menit**.
- Engine internal Laravel yang akan memeriksa `routes/console.php` dan mengeksekusi tugas mana yang jatuh tempo pada menit tersebut.

---

## 2. Petunjuk Konfigurasi Crontab di Ubuntu Server

### Langkah 1: Tentukan Pengguna Web Server
Sebaiknya crontab dijalankan di bawah akun pemilik direktori proyek web server (biasanya `www-data` atau user deployment khusus kantor):

```bash
# Buka editor crontab untuk user www-data:
sudo crontab -u www-data -e
```
*(Atau jika menggunakan akun deployer Anda sendiri, jalankan `crontab -e`).*

### Langkah 2: Tambahkan Baris Scheduler
Tambahkan satu baris berikut di baris paling bawah editor crontab:

```cron
* * * * * cd /var/www/dashboard-tubpsuml && php artisan schedule:run >> /dev/null 2>&1
```

> **Penjelasan Sintaks**:
> - `* * * * *`: Menandakan dieksekusi setiap menit, setiap jam, setiap hari.
> - `cd /var/www/dashboard-tubpsuml`: Berpindah ke direktori root aplikasi proyek.
> - `php artisan schedule:run`: Menjalankan pemicu jadwal Laravel.
> - `>> /dev/null 2>&1`: Membuang output konsol agar tidak mengirimkan email lokal Linux secara terus-menerus.

---

## 3. Menghubungkan Log Output Penjadwalan (Opsional)

Jika Sysadmin ingin merekam jejak apakah cron benar-benar terpanggil setiap menit:

```cron
* * * * * cd /var/www/dashboard-tubpsuml && php artisan schedule:run >> /var/www/dashboard-tubpsuml/storage/logs/cron.log 2>&1
```

Periksa isi file log sewaktu-waktu:
```bash
tail -f /var/www/dashboard-tubpsuml/storage/logs/cron.log
```

