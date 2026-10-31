# 🌐 Konfigurasi Web Server Nginx & PHP-FPM
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini mendokumentasikan berkas konfigurasi *Virtual Host* **Nginx**, penyesuaian batas unggahan berkas (*file upload limits*), pengaturan FastCGI PHP-FPM, serta pencegahan penyalahgunaan direktori tersembunyi.

---

## 1. Berkas Konfigurasi Nginx Server Block

Buat berkas konfigurasi baru pada `/etc/nginx/sites-available/dashboard-tubpsuml`:

```bash
sudo nano /etc/nginx/sites-available/dashboard-tubpsuml
```

Salin konfigurasi standar produksi berikut:

```nginx
server {
    listen 80;
    server_name persuratan.bpsuml.lan 192.168.1.100; # Sesuaikan IP LAN server
    root /var/www/dashboard-tubpsuml/public;

    # Header Keamanan
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    # Kapasitas maksimal unggah berkas naskah Word & lampiran ZIP
    client_max_body_size 30M;

    # Buffer & Timeout FastCGI
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Menonaktifkan logging untuk favicon dan robots.txt
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # Penanganan Skrip PHP via FastCGI Unix Socket
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Blokir akses ke berkas tersembunyi seperti .git, .env
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 2. Penyesuaian `php.ini` untuk Upload Berkas Besar

Secara default, instalasi PHP di Ubuntu membatasi unggah file maksimal 2 MB. Buka berkas konfigurasi PHP-FPM:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Cari dan sesuaikan parameter berikut:
```ini
upload_max_filesize = 30M
post_max_size = 35M
memory_limit = 256M
max_execution_time = 300
```

---

## 3. Mengaktifkan Situs & Memulai Ulang Layanan

```bash
# Buat symlink ke direktori sites-enabled
sudo ln -s /etc/nginx/sites-available/dashboard-tubpsuml /etc/nginx/sites-enabled/

# Hapus konfigurasi default Nginx jika tidak digunakan
sudo rm -f /etc/nginx/sites-enabled/default

# Periksa sintaks konfigurasi Nginx
sudo nginx -t

# Muat ulang service Nginx dan PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

