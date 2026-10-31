# 🧪 Verifikasi & Pengujian Manual Background Tasks
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan tata cara pengujian dan verifikasi perintah terjadwal secara manual melalui antarmuka baris perintah (CLI) sebelum diterapkan ke lingkungan produksi.

---

## 1. Meninjau Daftar Tugas Terjadwal (`schedule:list`)

Untuk memastikan seluruh perintah pada `routes/console.php` terdeteksi dengan benar oleh Laravel framework:

```bash
php artisan schedule:list
```

**Output Contoh**:
```text
+------------------------------------+---------------+------------------------------------------------------+----------------------------+
| Command                            | Interval      | Description                                          | Next Run                   |
+------------------------------------+---------------+------------------------------------------------------+----------------------------+
| files:cleanup-expired              | 0 1 * * *     | Hapus berkas fisik naskah kedaluwarsa               | Besok pukul 01:00          |
| notifications:cleanup              | 0 1 * * *     | Bersihkan notifikasi dibaca > 7 hari                 | Besok pukul 01:00          |
| surat:cleanup-rejected             | 15 1 * * *    | Bersihkan surat ditolak > 5 hari                     | Besok pukul 01:15          |
| surat:cleanup-orphaned-references  | 0 0 * * 0     | Bersihkan referensi DB tanpa file fisik             | Minggu pukul 00:00         |
| surat:remind-sla                   | */30 7-17 * * 1-5 | Kirim pengingat SLA jam kerja                    | Hari ini pukul 07:30       |
+------------------------------------+---------------+------------------------------------------------------+----------------------------+
```

---

## 2. Menjalankan Perintah Secara Langsung (Manual Test)

Untuk menguji kehandalan skrip tanpa menunggu jadwal malam:

### A. Uji Coba Pembersihan Berkas Kedaluwarsa
```bash
php artisan files:cleanup-expired
```
*Hasil yang diharapkan: Menghitung jumlah surat tuntas yang telah expired, menghapus file fisiknya dari disk, dan mencetak pesan sukses.*

### B. Uji Coba Pengingat SLA Jam Kerja
```bash
php artisan surat:remind-sla
```
*Hasil yang diharapkan: Menelusuri seluruh surat proses aktif. Jika ada yang mendekati deadline $\le 12$ jam, notifikasi dibuat dan disalurkan ke admin yang relevan.*

### C. Uji Coba Pembersihan Notifikasi Usang
```bash
php artisan notifications:cleanup
```
*Hasil yang diharapkan: Menghapus notifikasi lama dari tabel `notifications` dan melaporkan jumlah baris yang dibersihkan.*

---

## 3. Menjalankan Scheduler Simulasi (`schedule:work`)

Di komputer lokal atau server pementasan (*staging server*), Sysadmin dapat mengaktifkan daemon penguji penjadwalan tanpa perlu menyetel crontab Linux:

```bash
php artisan schedule:work
```
Perintah ini akan terus berjalan di terminal dan mengeksekusi `schedule:run` setiap 60 detik secara otomatis. Tekan `Ctrl + C` untuk menghentikan simulasi.

