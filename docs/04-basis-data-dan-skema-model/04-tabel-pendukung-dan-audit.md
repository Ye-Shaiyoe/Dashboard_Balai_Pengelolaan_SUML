# 📊 Kamus Data Tabel Pendukung & Audit Trail
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini mendokumentasikan spesifikasi teknis tabel modul pendukung, mencakup `aspirasis`, `it_support_tickets`, `activity_logs`, serta tabel infrastruktur Laravel (`notifications`, `sessions`, `jobs`, `cache`).

---

## 1. Kamus Data Tabel `aspirasis`

Menyimpan saluran penyampaian saran, pertanyaan, atau keluhan dari pegawai internal kantor:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Deskripsi |
|---|---|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Pengenal baris aspirasi. |
| `uuid` | `UUID` | Unique, Not Null | Pengenal publik aspirasi. |
| `user_id` | `BIGINT` | FK -> `users.id`, Not Null | Pegawai pengirim pesan aspirasi. |
| `judul` | `VARCHAR(255)` | Not Null | Ringkasan topik bahasan / subjek. |
| `isi` | `TEXT` | Not Null | Isi lengkap saran atau pertanyaan. |
| `tujuan` | `VARCHAR(50)` | Not Null | Pihak yang dituju: `admin` (Tata Usaha) atau `it_support` (Teknis). |
| `kategori` | `VARCHAR(50)` | Not Null | Klasifikasi: `saran`, `keluhan`, `pertanyaan`. |
| `status` | `VARCHAR(50)` | Not Null | Posisi tindak lanjut: `baru`, `diproses`, `selesai`. |
| `balasan` | `TEXT` | Nullable | Jawaban resmi dari Kasubbag TU atau IT Support. |
| `dibalas_at` | `TIMESTAMP` | Nullable | Tanggal & waktu jawaban dikirimkan. |
| `created_at` | `TIMESTAMP` | Nullable | Waktu pesan dikirimkan pertama kali. |
| `updated_at` | `TIMESTAMP` | Nullable | Waktu pembaruan status pesan. |

---

## 2. Kamus Data Tabel `it_support_tickets`

Menyimpan laporan kendala teknis aplikasi yang ditangani oleh tim IT:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Deskripsi |
|---|---|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Pengenal tiket kendala. |
| `admin_id` | `BIGINT` | FK -> `users.id`, Not Null | Pengguna/Admin yang melaporkan kendala. |
| `subjek` | `VARCHAR(255)` | Not Null | Judul kendala teknis (misal: "Gagal Cetak TTE"). |
| `kategori` | `VARCHAR(100)` | Not Null | Klasifikasi (Bug Aplikasi, Jaringan LAN, Database). |
| `detail` | `TEXT` | Not Null | Deskripsi langkah reproduksi galat dan pesan error. |
| `status` | `VARCHAR(50)` | Not Null | Status: `open`, `resolved`, `closed`. |
| `catatan_it` | `TEXT` | Nullable | Catatan solusi pemecahan oleh tim IT. |
| `created_at` | `TIMESTAMP` | Nullable | Waktu tiket dibuat. |
| `updated_at` | `TIMESTAMP` | Nullable | Waktu pembaruan tiket. |

---

## 3. Kamus Data Tabel `activity_logs`

Pusat perekaman jejak audit sistem (*system audit trail*):

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Deskripsi |
|---|---|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Pengenal log audit. |
| `user_id` | `BIGINT` | FK -> `users.id`, Nullable | Aktor pengguna yang mengeksekusi tindakan. |
| `action` | `VARCHAR(50)` | Not Null | Jenis aksi: `create`, `update`, `approve`, `reject`, `delete`, `download`, `reset_password`, `login`, `logout`. |
| `model_type` | `VARCHAR(100)` | Nullable | Nama kelas model yang dimodifikasi (`App\Models\Surat`, dll). |
| `model_id` | `BIGINT` | Nullable | Primary key baris data yang dimodifikasi. |
| `description` | `TEXT` | Not Null | Narasi penjelasan audit dalam bahasa manusia. |
| `changes` | `JSONB` | Nullable | Snapshot nilai atribut kolom sebelum vs sesudah (*diff JSON*). |
| `ip_address` | `VARCHAR(45)` | Nullable | Alamat IP jaringan lokal/publik pemohon. |
| `user_agent` | `TEXT` | Nullable | Tipe browser, OS, dan perangkat klien pemohon. |
| `created_at` | `TIMESTAMP` | Nullable | Waktu presisi saat tindakan terjadi. |
| `updated_at` | `TIMESTAMP` | Nullable | Waktu pembaruan baris log. |

---

## 4. Tabel Infrastruktur Bawaan Framework Laravel

1. **`notifications`**: Menyimpan notifikasi in-app untuk lonceng dashboard (UUID, notifiable_type, notifiable_id, data JSON, read_at).
2. **`sessions`**: Menyimpan data sesi aktif login lintas perangkat untuk mendukung fitur pencabutan sesi (*session revocation*).
3. **`jobs` & `job_batches`**: Antrean eksekusi latar belakang jika antrean database diaktifkan (`QUEUE_CONNECTION=database`).
4. **`cache` & `cache_locks`**: Manajemen caching data agregat dan atomic lock scheduler di PostgreSQL.

