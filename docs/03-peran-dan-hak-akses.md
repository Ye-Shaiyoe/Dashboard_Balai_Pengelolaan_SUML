# 👥 Modul 03: Peran & Hak Akses (RBAC)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan matriks kewenangan berbasis peran (*Role-Based Access Control / RBAC*), otorisasi antar level jabatan, dan fitur-fitur administratif khusus di lingkungan BP SUML.

---

## 1. Daftar Peran (User Roles)

Sistem membedakan pengguna ke dalam 6 peran utama:

| Kode Role (`role`) | Nama Jabatan / Peran | Deskripsi Tanggung Jawab Utama |
|---|---|---|
| `user` | **Pegawai Pengusul** | Pegawai BP SUML yang mengajukan naskah dinas, memantau tracking surat, mengunggah revisi, dan memberikan rating. |
| `admin_aspirasi` | **Arsiparis** | Verifikator Tahap 2 (pemeriksaan tata naskah), penginput nomor surat resmi (Tahap 5), dan pengarsipan akhir (Tahap 9). |
| `admin_kasubbag_tu` | **Kasubbag Tata Usaha** | Verifikator Tahap 3 (administrasi & substansi), manajemen pengguna/pegawai, dan eksekutor **Reset Password Pegawai** (solusi kendala SMTP LAN). |
| `admin_kepala_balai` | **Kepala Balai** | Pejabat pengambil keputusan akhir (Tahap 4) dan penandatangan elektronik naskah resmi (Tahap 6 TTE/DS). |
| `it_support` | **Tim Bantuan Teknis IT** | Mengelola tiket kendala teknis (bug report), pemeliharaan aplikasi, dan pengiriman siaran notifikasi (*broadcast push notification*). |
| `admin` | **Superadmin (Legacy)** | Akun pengelola tingkat sistem tertinggi dengan hak akses menyeluruh ke seluruh modul administrasi. |

---

## 2. Matriks Hak Akses & Kewenangan (Permission Matrix)

Tabel berikut memetakan kapabilitas fitur terhadap masing-masing role pengguna:

| Fitur / Modul Sistem | Pegawai (`user`) | Arsiparis (`admin_aspirasi`) | Kasubbag TU (`admin_kasubbag_tu`) | Kepala Balai (`admin_kepala_balai`) | IT Support (`it_support`) | Superadmin (`admin`) |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Mengajukan Surat Baru** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Lihat Riwayat Surat Pribadi** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Lihat Seluruh Surat Masuk** | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Verifikasi Format (Tahap 2)** | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Verifikasi Kasubbag (Tahap 3)**| ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Persetujuan Surat (Tahap 4)** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **Input Nomor Surat (Tahap 5)** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Tanda Tangan TTE (Tahap 6)** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **Distribusi TNDe / Srikandi** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Pengarsipan Naskah (Tahap 9)** | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Persetujuan Hapus Surat** | ❌ (Hanya Request) | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Manajemen File Fisik (Purge)**| ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Reset Password Pegawai** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Ubah Role Pegawai** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Laporan & Rekap SLA Excel** | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Broadcast Notifikasi Sistem** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Kelola Tiket Kendala Bug** | ❌ (Hanya Ajukan) | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Unduh System Logs Teknis** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |

---

## 3. Fitur Otoritas Khusus Kasubbag TU: Reset Password Mandiri

### Latar Belakang Masalah
Pada infrastruktur jaringan lokal (LAN/Intranet) kantor BP SUML, seluruh port keluar untuk protokol surat elektronik (SMTP seperti port 25, 465, 587) diblokir oleh firewall keamanan jaringan instansi. Hal ini menyebabkan fitur bawaan Laravel "Lupa Password via Email" mengalami kendala pengiriman (*connection timeout / failure*).

### Solusi Desain Sistem
Kasubbag TU (`admin_kasubbag_tu`) dan Superadmin (`admin`) diberikan wewenang administratif eksklusif untuk memperbarui kata sandi akun pegawai secara langsung melalui antarmuka web tanpa bergantung pada pengiriman email:
1. Tombol **🔄 Reset Password** tersedia pada tabel data pegawai (`/Admin/Settings/Users`) dan halaman detail pegawai (`/Admin/Settings/Users/{user}`).
2. Tersedia preset cepat kata sandi standar kantor: `Bpsuml2026!`.
3. Password di-hash menggunakan algoritma Bcrypt yang aman sebelum disimpan ke database.
4. Fitur dilengkapi tombol **📋 Salin Password** untuk memudahkan Kasubbag TU meneruskan kredensial baru kepada pegawai melalui pesan instan internal.
5. Seluruh riwayat reset kata sandi otomatis dicatat ke dalam audit log sistem.

---

## 4. Fitur Otoritas Khusus IT Support

Role `it_support` difokuskan pada aspek stabilitas dan operasional teknis tanpa mencampuri proses administrasi surat kedinasan:
1. **Tiket Bantuan & Bug Report**: Merespons kendala teknis atau permohonan bantuan sistem yang diajukan oleh pengguna atau admin lain.
2. **Broadcast Notifikasi**: Mengirimkan pengumuman penting (misalnya: *Jadwal Pemeliharaan Server*, *Pembaruan Fitur*, atau *Imbauan Persuratan*) ke seluruh akun secara serentak.
3. **Penyelidikan Log Error**: Memantau file log harian Laravel di direktori `storage/logs/` untuk menganalisis anomali sistem secara cepat.

---

## 5. Alur Pemilihan Peran Admin (*Role Selection*)

Saat pengguna yang memiliki hak administratif login untuk pertama kalinya, sistem mengarahkan mereka ke antarmuka `/Admin/Role-Selection`:
- Pengguna memilih spesifikasi perannya: *Arsiparis*, *Kasubbag TU*, atau *Kepala Balai*.
- Sistem menyimpan pilihan tersebut ke atribut `role_selected = true`.
- Setelah peran terkonfirmasi, menu navigasi sidebar dan batasan hak akses diaktifkan sesuai perannya secara dinamis melalui middleware `admin.role.check`.
