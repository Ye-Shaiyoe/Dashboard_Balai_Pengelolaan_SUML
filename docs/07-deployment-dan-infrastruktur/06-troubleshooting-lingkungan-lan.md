# 🛠️ Pemecahan Masalah Lingkungan LAN & Offline (Troubleshooting)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menguraikan kendala teknis yang paling sering dihadapi pada instalasi server di lingkungan Local Area Network (LAN) / Intranet kantor BP SUML beserta langkah pemecahan masalahnya (*troubleshooting*).

---

## 1. Masalah 1: Layar "500 Server Error" Pasca Git Pull

- **Gejala**: Halaman web menampilkan HTTP Status 500 setelah administrator menjalankan perintah `git pull`.
- **Akar Penyebab**:
  1. Berkas cache lama (`bootstrap/cache/config.php` atau `routes-v7.php`) bentrok dengan kelas controller/route baru.
  2. Izin direktori `storage/` ter-reset menjadi milik pengguna root/git.
- **Langkah Pemecahan**:
  ```bash
  cd /var/www/dashboard-tubpsuml
  php artisan optimize:clear
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  sudo systemctl restart php8.2-fpm
  ```
- **Pengecekan Log Galat**:
  ```bash
  tail -n 50 storage/logs/laravel.log
  ```

---

## 2. Masalah 2: Port SMTP Diblokir oleh Firewall Kantor

- **Gejala**: Ketika pengguna mengklik *"Lupa Password"*, web mengalami *loading* lama dan berakhir dengan pesan galat *Connection timed out (Port 587/465)*.
- **Akar Penyebab**: Kebijakan jaringan intranet instansi menutup akses koneksi keluar (*outbound traffic*) untuk protokol SMTP pihak ketiga (seperti Gmail/Office365).
- **Langkah Pemecahan**:
  1. Pastikan di `.env` telah disetel: `MAIL_MAILER=log`.
  2. Arahkan pegawai yang lupa sandi untuk menghubungi **Kasubbag TU**.
  3. Kasubbag TU menggunakan fitur bawaan **Reset Password Mandiri** melalui menu:
     - **Pengaturan** &rarr; **Kelola Pengguna** &rarr; Klik tombol merah **🔄 Reset Password**.
     - Klik **"Pakai Default (Bpsuml2026!)"** dan simpan.
     - Klik **"📋 Salin Password"** dan kirimkan sandi baru ke pegawai via pesan internal kantor.

---

## 3. Masalah 3: Berkas Unggahan Tidak Ditemukan (404 Not Found)

- **Gejala**: Foto profil avatar atau template surat dinas yang baru diunggah tidak tampil / muncul ikon gambar rusak.
- **Akar Penyebab**: Tautan simbolik (*symbolic link*) antara `public/storage` dan `storage/app/public` belum dibuat atau rusak (*broken symlink*).
- **Langkah Pemecahan**:
  ```bash
  cd /var/www/dashboard-tubpsuml
  rm -f public/storage
  php artisan storage:link
  ```

---

## 4. Masalah 4: Perubahan CSS/JS Tidak Berubah di Browser Pegawai

- **Gejala**: Desain antarmuka baru tidak muncul di komputer staf kantor meskipun sudah dilakukan `git pull`.
- **Akar Penyebab**: Kompilasi aset Vite belum dijalankan atau browser klien menyimpan *browser cache* lama.
- **Langkah Pemecahan**:
  1. Di server, jalankan pembangunan aset:
     ```bash
     npm run build
     php artisan view:clear
     ```
  2. Di komputer pegawai, lakukan pemuatan ulang paksa (*Hard Reload*):
     - Tekan **Ctrl + F5** (atau **Ctrl + Shift + R**).

