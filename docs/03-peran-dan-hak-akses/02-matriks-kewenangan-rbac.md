# 📊 Matriks Kewenangan Hak Akses (RBAC Matrix)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menyajikan matriks perizinan menyeluruh (*Role-Based Access Control / RBAC*) yang memetakan setiap modul, aksi, dan rute aplikasi terhadap 6 peran pengguna di sistem.

---

## 1. Matriks Akses Fitur & Menu Navigasi

| Modul / Fitur Aplikasi | Pegawai (`user`) | Arsiparis (`admin_aspirasi`) | Kasubbag TU (`admin_kasubbag_tu`) | Kepala Balai (`admin_kepala_balai`) | IT Support (`it_support`) | Superadmin (`admin`) |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Ajukan Naskah Dinas Baru** (`/surat/ajukan`) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Kelola Draf & Surat Pribadi** (`/surat`) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Antrean Seluruh Surat Masuk** (`/Admin/Surat`) | ❌ | ✅ (Tahap 2, 5+) | ✅ (Tahap 3) | ✅ (Tahap 4) | ❌ | ✅ (Semua) |
| **Verifikasi Format & Template (Tahap 2)** | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Verifikasi Administrasi TU (Tahap 3)** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Persetujuan Naskah (Tahap 4)** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **Input Nomor Surat Resmi (Tahap 5)** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Pembubuhan Tanda Tangan TTE (Tahap 6)** | ❌ | ❌ | ❌ | ✅ (PIN) | ❌ | ✅ |
| **Distribusi TNDe & Srikandi (Tahap 7 & 8)** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Pengarsipan Naskah (Tahap 9)** | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Unggah Draf Revisi Naskah** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Upload Perbaikan Berkas Admin** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Permohonan Hapus Surat** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Persetujuan Hapus Surat (Delete Request)**| ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Pembersihan Berkas Fisik (Purge Disk)** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Reset Password Pegawai (Solusi LAN)** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Kelola Data Pegawai & Role** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Ekspor Laporan & SLA ke Excel** | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Kirim Aspirasi / Saran** (`/aspirasi`) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Tanggapi Aspirasi Pengguna** | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Lapor Kendala Teknis (Bug Report)** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Broadcast Pengumuman Sistem** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Inspeksi File Log Laravel** (`/Admin/Settings/Logs`) | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Direktori Pencarian Pegawai** (`/pegawai`)| ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Buku Agenda Kalender** (`/agenda`) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 2. Tingkat Otoritas Khusus Berdasarkan Rute

Sistem menerapkan validasi berlapis di tingkat router (`routes/web.php`):
1. **Middleware `auth` & `verified`**: Menjamin pengguna telah terotentikasi.
2. **Middleware `admin`**: Memblokir pengguna yang bukan bagian dari admin grup.
3. **Middleware `admin.role.check`**: Memeriksa bahwa admin telah melalui seleksi peran aktif di `/Admin/Role-Selection`.
4. **Middleware `it_support`**: Menjamin rute broadcast dan tiket hanya diakses oleh teknisi resmi.

