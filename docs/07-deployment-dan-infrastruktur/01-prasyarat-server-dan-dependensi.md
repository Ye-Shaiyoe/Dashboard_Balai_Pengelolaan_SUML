# 🖥️ Prasyarat Sistem & Dependensi Server
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan spesifikasi perangkat keras (*hardware*), sistem operasi, paket perangkat lunak pendukung, ekstensi PHP, serta konfigurasi database PostgreSQL untuk hosting di lingkungan server kantor BP SUML.

---

## 1. Rekomendasi Spesifikasi Server (Ubuntu Linux)

| Komponen | Spesifikasi Minimum | Spesifikasi Disarankan |
|---|---|---|
| **Sistem Operasi** | Ubuntu Server 22.04 LTS | Ubuntu Server 24.04 LTS (x86_64) |
| **Prosesor (CPU)** | 2 Core vCPU | 4 Core vCPU |
| **Memori (RAM)** | 4 GB | 8 GB RAM |
| **Penyimpanan (Storage)** | 40 GB SSD / NVMe | 100+ GB SSD (Kapasitas arsip dokumen Word & PDF) |
| **Jaringan (LAN)** | 100 Mbps NIC | 1 Gbps Ethernet terhubung switch inti LAN kantor |

---

## 2. Instalasi Paket Perangkat Lunak Inti

Jalankan perintah berikut di terminal server Ubuntu:

```bash
# 1. Perbarui repositori sistem
sudo apt update && sudo apt upgrade -y

# 2. Instal paket esensial & web server Nginx
sudo apt install -y nginx git curl unzip zip software-properties-common

# 3. Instal PostgreSQL Database Server
sudo apt install -y postgresql postgresql-contrib

# 4. Instal PHP 8.2 atau 8.3 beserta ekstensi wajib Laravel 12 & PhpWord
sudo apt install -y php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring \
                    php8.2-xml php8.2-zip php8.2-gd php8.2-curl \
                    php8.2-bcmath php8.2-intl php8.2-opcache

# 5. Instal Composer (Dependency Manager PHP)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# 6. Instal Node.js (LTS) & NPM (untuk kompilasi aset Vite)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v
```

---

## 3. Konfigurasi Basis Data PostgreSQL

Buat database dan pengguna khusus untuk aplikasi Dashboard TU BP SUML:

```bash
# Buka prompt PostgreSQL interaktif
sudo -u postgres psql
```

Eksekusi perintah SQL berikut:
```sql
-- Buat database khusus
CREATE DATABASE dashboard_tubpsuml;

-- Buat user dengan password aman
CREATE USER user_bpsuml WITH ENCRYPTED PASSWORD 'Katasandi_Rahasia_Db_2026!';

-- Berikan hak istimewa penuh ke database
GRANT ALL PRIVILEGES ON DATABASE dashboard_tubpsuml TO user_bpsuml;

-- Masuk ke database dashboard_tubpsuml
\c dashboard_tubpsuml

-- Berikan hak akses skema public
GRANT ALL ON SCHEMA public TO user_bpsuml;

-- Keluar dari psql
\q
```

