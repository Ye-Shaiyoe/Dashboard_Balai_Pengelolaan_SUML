# 💻 Panduan Operasional Tim Bantuan Teknis (`it_support`)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini merupakan petunjuk praktis bagi **Tim IT Support** dalam menindaklanjuti laporan kendala teknis (tiket bug), mengirimkan siaran pengumuman sistem (*system broadcast*), serta melakukan investigasi berkas log galat Laravel.

---

## 1. Menangani Tiket Bantuan & Kendala Teknis (Bug Report)

Ketika staf kantor atau administrator mengirimkan keluhan gangguan teknis:

1. Masuk ke sistem dan arahkan peramban ke **Dashboard IT Support** (`/IT-Support/Dashboard`).
2. Pada tabel **Daftar Tiket Kendala**:
   - Status `open`: Tiket baru yang belum ditangani.
   - Status `resolved`: Tiket yang telah diperbaiki.
   - Status `closed`: Tiket yang telah dikonfirmasi selesai oleh pelapor.
3. Klik tombol **Tinjau Tiket**:
   - Baca deskripsi galat, langkah-langkah kejadian, dan periksa tangkapan layar pendukung.
4. Lakukan investigasi teknis pada server atau database.
5. Setelah masalah teratasi:
   - Ketikkan solusi pemecahan masalah pada kotak **"Catatan Penanganan IT"**.
   - Ubah status menjadi **`resolved`** atau **`closed`**.
   - Klik **"Perbarui Tiket"**. Pelapor akan menerima pemberitahuan bahwa kendala telah diperbaiki.

---

## 2. Mengirimkan Siaran Pengumuman Sistem (*System Broadcast*)

Gunakan fitur broadcast saat hendak mengumumkan jadwal pemeliharaan server (*maintenance*), pembaruan versi aplikasi, atau imbauan teknis jaringan kantor:

1. Buka menu **Broadcast Notifikasi** (`/IT-Support/Notification/Create`).
2. Isi formulir pengumuman:
   - **Judul Siaran**: Contoh: *"Pemberitahuan Pemeliharaan Server Intranet Malam Ini"*.
   - **Tipe Notifikasi**: Pilih tingkat kepentingan:
     - ℹ️ *Informasi Umum*
     - ⚠️ *Peringatan Sistem*
     - 🚨 *Pemeliharaan Darurat (Maintenance)*
   - **Isi Pesan**: Tuliskan petunjuk teknis yang jelas bagi para pegawai (misal: *"Sistem akan di-restart pada pukul 18.00 WIB selama 15 menit. Harap simpan draf naskah Anda"*).
3. Klik tombol **"Kirim Siaran ke Seluruh Pengguna"**.
4. Sistem akan memancarkan notifikasi secara serempak ke seluruh peramban pengguna yang sedang aktif melalui Server-Sent Events (SSE) dan mencatat notifikasi di lonceng sistem.

---

## 3. Investigasi Log Error Sistem Laravel

Untuk menelusuri bug yang tidak memunculkan detail error di browser:

1. Masuk ke menu **Pengaturan** &rarr; **Log Sistem** (`/Admin/Settings/Logs`).
2. Sistem akan menampilkan daftar file log harian di direktori `storage/logs/`.
3. Klik tombol **Unduh** untuk mengambil file `laravel.log` ke komputer Anda, atau baca pratinjau stack trace error langsung melalui antarmuka web.
4. Setelah investigasi dan perbaikan selesai di server, Anda dapat membersihkan isi berkas log menggunakan tombol **"Bersihkan Log"** agar ukuran file log tidak membengkak di harddisk server.

