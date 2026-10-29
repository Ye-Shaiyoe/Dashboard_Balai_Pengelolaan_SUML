# 🚀 Modul 07: Panduan Deployment Server (Ubuntu Linux)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini adalah panduan komprehensif bagi Administrator Sistem (Sysadmin/DevOps) untuk melakukan instalasi awal, pembaruan (*git pull*), dan konfigurasi server produksi berbasis **Ubuntu Server Linux** di jaringan LAN/Intranet BP SUML.

---

## 1. Prasyarat Sistem Server

- **Sistem Operasi**: Ubuntu Server 22.04 LTS / 24.04 LTS
- **PHP**: Versi 8.2 atau 8.3 dengan ekstensi:
  - `php-fpm`, `php-pgsql`, `php-mbstring`, `php-xml`, `php-zip`, `php-gd`, `php-curl`, `php-bcmath`
- **Basis Data**: PostgreSQL 14+ / 16+
- **Web Server**: Nginx (disarankan) atau Apache2
- **Tools**: Composer 2.x, Git, Node.js (v18+) & NPM

Perintah instalasi paket dasar di Ubuntu:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql postgresql-contrib git curl unzip
sudo apt install -y php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-curl
```

### Konfigurasi Database PostgreSQL di Server
```bash
# Masuk ke prompt postgres
sudo -u postgres psql

# Buat database dan user
CREATE DATABASE dashboard_tubpsuml;
CREATE USER user_bpsuml WITH ENCRYPTED PASSWORD 'password_rahasia_anda';
GRANT ALL PRIVILEGES ON DATABASE dashboard_tubpsuml TO user_bpsuml;
\c dashboard_tubpsuml
GRANT ALL ON SCHEMA public TO user_bpsuml;
\q
```

---

## 2. Langkah Instalasi Awal (First-Time Setup)

### A. Kloning Repositori
```bash
cd /var/www
sudo git clone <URL_REPOSITORY_GIT> dashboard-tubpsuml
cd dashboard-tubpsuml
```

### B. Konfigurasi Berkas `.env`
Salin template konfigurasi dan sesuaikan parameternya:
```bash
cp .env.example .env
nano .env
```

**Poin-poin Kritis pada `.env` di Lingkungan LAN BP SUML**:
```dotenv
APP_NAME="Dashboard TU BP SUML"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.x.x   # Atau domain internal kantor, misal: http://persuratan.bpsuml.lan

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dashboard_tubpsuml
DB_USERNAME=user_bpsuml
DB_PASSWORD=password_rahasia_anda

# CATATAN SMTP JARINGAN LAN:
# Karena port keluar SMTP diblokir firewall kantor, gunakan driver log:
MAIL_MAILER=log
```

### C. Instalasi Dependensi & Migrasi Database
```bash
# Instal dependensi PHP (mode produksi tanpa paket dev)
composer install --no-dev --optimize-autoloader

# Generate kunci aplikasi
php artisan key:generate

# Eksekusi migrasi tabel
php artisan migrate --force

# Buat symbolic link untuk direktori storage
php artisan storage:link

# Kompilasi aset frontend
npm install
npm run build
```

---

## 3. Pengaturan Hak Akses & Kepemilikan (*Permissions*)

Salah satu penyebab paling sering munculnya **HTTP Error 500** di Ubuntu Server adalah izin folder `storage/` dan `bootstrap/cache/` yang belum diberikan ke web server (`www-data`).

Jalankan perintah berikut:
```bash
# Berikan kepemilikan ke user web server
sudo chown -R www-data:www-data /var/www/dashboard-tubpsuml

# Berikan izin tulis untuk storage dan cache
sudo chmod -R 775 /var/www/dashboard-tubpsuml/storage
sudo chmod -R 775 /var/www/dashboard-tubpsuml/bootstrap/cache
```

---

## 4. Konfigurasi Web Server

### Opsi A: Konfigurasi Nginx (Disarankan)
Buat berkas konfigurasi baru di `/etc/nginx/sites-available/dashboard-tubpsuml`:

```nginx
server {
    listen 80;
    server_name persuratan.bpsuml.lan 192.168.1.100; # Sesuaikan IP LAN server
    root /var/www/dashboard-tubpsuml/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 30M; # Mendukung unggah dokumen Word & Lampiran besar

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/dashboard-tubpsuml /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## 5. Prosedur Pembaruan Kode di Produksi (*Git Pull Checklist*)

Setiap kali Anda selesai memperbarui kode atau fitur baru di GitHub/GitLab dan ingin menerapkannya ke server Ubuntu tanpa gangguan:

```bash
cd /var/www/dashboard-tubpsuml

# 1. Tarik pembaruan terbaru dari Git
git pull origin main

# 2. Instal dependensi PHP baru jika ada perubahan composer.json
composer install --no-dev --optimize-autoloader

# 3. Jalankan migrasi jika ada penambahan tabel atau kolom baru
php artisan migrate --force

# 4. Bangun ulang aset jika ada perubahan CSS / JS
npm run build

# 5. Segarkan seluruh cache Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Pastikan izin folder tetap aman
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7. Restart PHP-FPM untuk membersihkan OPcache memory
sudo systemctl restart php8.2-fpm
```

---

## 6. Solusi & Troubleshooting Masalah Umum Produksi

### Masalah 1: Muncul Layar "500 Server Error" Pasca Git Pull
- **Penyebab**: View cache atau route cache lama bentrok dengan controller/view baru, atau izin direktori `storage/` ter-reset.
- **Solusi**:
  ```bash
  cd /var/www/dashboard-tubpsuml
  php artisan optimize:clear
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  ```
- Periksa detail error di baris paling bawah log:
  ```bash
  tail -n 50 storage/logs/laravel.log
  ```

### Masalah 2: Port SMTP Terblokir di LAN Kantor
- **Solusi**: Jangan gunakan fitur kirim email eksternal jika port 25/465/587 diblokir firewall instansi.
- Kasubbag TU dapat langsung menggunakan fitur **Reset Password Mandiri** melalui menu:
  * **Pengaturan &rarr; Kelola Pengguna &rarr; Tombol 🔄 Reset Password**.
  * Password akan langsung diperbarui di basis data secara instan tanpa perlu email verifikasi.

### Masalah 3: Berkas Unggahan Tidak Bisa Dibuka (404 Not Found)
- **Solusi**: Pastikan symbolic link storage telah terbentuk:
  ```bash
  php artisan storage:link
  ```
  Dan pastikan direktori `storage/app/public` serta `storage/app/private` dapat dibaca oleh user `www-data`.
