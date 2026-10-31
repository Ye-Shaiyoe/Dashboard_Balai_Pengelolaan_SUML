# 📦 Instalasi Awal & Konfigurasi Berkas `.env`
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini memandu langkah demi langkah pemasangan awal (*initial deployment*), kloning repositori kode sumber, kompilasi aset, dan penyusunan berkas environment `.env` di server produksi.

---

## 1. Kloning Repositori Kode Sumber

Arahkan terminal ke direktori root web server Ubuntu (umumnya `/var/www`):

```bash
cd /var/www
sudo git clone <URL_GIT_REPOSITORY> dashboard-tubpsuml
cd dashboard-tubpsuml
```

---

## 2. Penyusunan Berkas Konfigurasi `.env`

Salin draf template `.env.example` menjadi `.env`:

```bash
cp .env.example .env
nano .env
```

Sesuaikan parameter kunci sesuai dengan lingkungan operasional kantor BP SUML:

```dotenv
APP_NAME="Dashboard TU BP SUML"
APP_ENV=production
APP_KEY=                          # Akan digenerate pada langkah berikutnya
APP_DEBUG=false
APP_URL=http://persuratan.bpsuml.lan # Atau IP lokal, misal: http://192.168.1.100

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Konfigurasi Database PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dashboard_tubpsuml
DB_USERNAME=user_bpsuml
DB_PASSWORD=Katasandi_Rahasia_Db_2026!

# Konfigurasi Sesi & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

# MITIGASI SMTP LAN KANTOR:
# Seluruh port keluar SMTP diblokir firewall instansi, gunakan driver log:
MAIL_MAILER=log
MAIL_FROM_ADDRESS="tu@bpsuml.lan"
MAIL_FROM_NAME="Tata Usaha BP SUML"

# Filesystem Driver
FILESYSTEM_DISK=local
```

---

## 3. Instalasi Dependensi PHP & Kompilasi Aset Frontend

Jalankan perintah berikut di dalam direktori `/var/www/dashboard-tubpsuml`:

```bash
# 1. Instal seluruh dependensi PHP (mode produksi tanpa paket development)
composer install --no-dev --optimize-autoloader

# 2. Hasilkan kunci enkripsi aplikasi (APP_KEY)
php artisan key:generate

# 3. Jalankan migrasi tabel database secara menyeluruh
php artisan migrate --force

# 4. Opsional: Jalankan seeder akun awal jika database masih kosong
php artisan db:seed --force

# 5. Buat tautan simbolik direktori berkas publik
php artisan storage:link

# 6. Kompilasi aset frontend Tailwind & JavaScript
npm install
npm run build
```

