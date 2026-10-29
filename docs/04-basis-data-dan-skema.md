# 🗄️ Modul 04: Basis Data & Skema Model (ERD)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menyediakan spesifikasi basis data komprehensif berbasis **PostgreSQL**, diagram relasi antar tabel (*Entity-Relationship Diagram*), serta kamus data untuk seluruh entitas sistem.

---

## 1. Diagram Relasi Entitas (ERD)

```mermaid
erDiagram
    USERS ||--o{ SURATS : "mengajukan"
    USERS ||--o{ SURAT_TAHAPANS : "memproses"
    USERS ||--o{ SURAT_DELETE_REQUESTS : "meminta_atau_menyetujui"
    USERS ||--o{ ASPIRASIS : "mengirim"
    USERS ||--o{ ACTIVITY_LOGS : "mencatat_aktivitas"
    USERS ||--o{ IT_SUPPORT_TICKETS : "melaporkan"
    
    SURATS ||--|{ SURAT_TAHAPANS : "memiliki_10_tahapan"
    SURATS ||--o{ SURAT_DELETE_REQUESTS : "dihapus_melalui"

    USERS {
        bigserial id PK
        uuid uuid UK
        varchar name
        varchar email UK
        varchar password
        varchar role "user, admin_kasubbag_tu, dll"
        varchar nip
        char nip_hash UK "HMAC SHA-256"
        boolean role_selected
        varchar profile_photo
        varchar signature_path "Private TTE image"
        varchar signature_pin "Hashed PIN"
        varchar switch_token
        timestamp switch_token_expires_at
        timestamp created_at
    }

    SURATS {
        bigserial id PK
        uuid uuid UK
        bigint user_id FK
        varchar judul
        varchar jenis "nota_dinas, surat_dinas, dll"
        varchar sifat "biasa, penting, rahasia, segera"
        varchar tujuan
        text catatan_pengusul
        varchar file_word "Path docx"
        varchar file_lampiran "Path lampiran"
        varchar nomor_surat
        date tanggal_surat
        smallint tahap_sekarang "1 s/d 10"
        varchar status "draft, proses, revisi, selesai, ditolak"
        boolean perlu_follow_up
        text catatan_follow_up
        timestamp deadline_sla
        varchar alasan_keterlambatan
        timestamp disetujui_pada
        timestamp file_dihapus_pada
        timestamp file_expires_at
        boolean status_revisi
        integer revisi_count
        timestamp revisi_uploaded_at
        smallint rating "1 - 5 bintang"
        timestamp created_at
    }

    SURAT_TAHAPANS {
        bigserial id PK
        bigint surat_id FK
        smallint tahap "1 s/d 10"
        varchar nama_tahap
        varchar status "menunggu, proses, selesai, ditolak"
        bigint diproses_oleh FK "users.id"
        text catatan
        timestamp selesai_pada
        timestamp created_at
    }

    SURAT_DELETE_REQUESTS {
        bigserial id PK
        bigint surat_id FK
        bigint user_id FK
        bigint admin_id FK
        text alasan
        varchar status "pending, disetujui, ditolak"
        text admin_catatan
        timestamp admin_approved_at
        timestamp created_at
    }

    ASPIRASIS {
        bigserial id PK
        uuid uuid UK
        bigint user_id FK
        varchar judul
        text isi
        varchar tujuan "admin, it_support"
        varchar kategori "saran, keluhan, pertanyaan"
        varchar status "baru, diproses, selesai"
        text balasan
        timestamp dibalas_at
        timestamp created_at
    }

    IT_SUPPORT_TICKETS {
        bigserial id PK
        bigint admin_id FK
        varchar subjek
        varchar kategori
        text detail
        varchar status "open, resolved, closed"
        text catatan_it
        timestamp created_at
    }

    ACTIVITY_LOGS {
        bigserial id PK
        bigint user_id FK
        varchar action "create, update, approve, reject, dll"
        varchar model_type
        bigint model_id
        text description
        jsonb changes "before vs after"
        varchar ip_address
        text user_agent
        timestamp created_at
    }
```

---

## 2. Kamus Data Entitas Utama

### A. Tabel `users`
Menyimpan identitas seluruh pemangku kepentingan (pegawai dan pejabat administrasi):

| Kolom | Tipe Data | Keterangan & Aturan Bisnis |
|---|---|---|
| `id` | `BIGINT UNSIGNED` (Auto-increment, PK) | Identifier primer tabel. |
| `uuid` | `CHAR(36)` (Unique Index) | Identifier publik untuk rute URL aman (*Route Key Name*). |
| `name` | `VARCHAR(255)` | Nama lengkap pegawai dan gelar. |
| `email` | `VARCHAR(255)` (Unique Index) | Alamat email resmi pegawai. |
| `password` | `VARCHAR(255)` | Hash kata sandi menggunakan algoritma Bcrypt. |
| `role` | `ENUM` | Nilai: `user`, `admin_aspirasi`, `admin_kasubbag_tu`, `admin_kepala_balai`, `it_support`, `admin`. |
| `nip` | `VARCHAR(50)` (Nullable) | NIP pegawai (18 digit angka standar BKN). |
| `nip_hash` | `CHAR(64)` (Unique, Nullable) | Hash HMAC-SHA256 dari NIP untuk keperluan pencarian login cepat tanpa mengekspos NIP asli. |
| `signature_path`| `VARCHAR(255)` (Nullable) | Lokasi penyimpanan berkas scan tanda tangan privat di disk server. |
| `signature_pin` | `VARCHAR(255)` (Nullable) | Hash PIN keamanan pembubuhan tanda tangan elektronik (TTE). |
| `profile_photo` | `VARCHAR(255)` (Nullable) | Lokasi foto avatar profil pegawai. |

---

### B. Tabel `surats`
Pusat metadata transaksi naskah dinas kedinasan:

| Kolom | Tipe Data | Keterangan & Aturan Bisnis |
|---|---|---|
| `id` | `BIGINT UNSIGNED` (PK) | Identifier primer internal database. |
| `uuid` | `CHAR(36)` (Unique Index) | Identifier publik naskah untuk pelacakan dan kode QR (`/v/{uuid}`). |
| `user_id` | `BIGINT UNSIGNED` (FK -> `users.id`) | Pegawai pembuat/pengusul naskah dinas. |
| `judul` | `VARCHAR(255)` | Perihal / ringkasan isi surat. |
| `jenis` | `ENUM` | `nota_dinas`, `surat_dinas`, `surat_keputusan`, `surat_pernyataan`, `surat_keterangan`, `surat_undangan`, `surat_lainnya`. |
| `sifat` | `ENUM` | Tingkat urgensi: `biasa`, `penting`, `rahasia`, `segera`. |
| `tujuan` | `VARCHAR(255)` | Pihak atau instansi penerima surat. |
| `tahap_sekarang` | `TINYINT UNSIGNED` (Default: 1) | Posisi tahapan saat ini (angka 1 sampai 10). |
| `status` | `ENUM` | `draft`, `proses`, `revisi`, `revisi_admin`, `selesai`, `ditolak`. |
| `deadline_sla` | `DATETIME` (Nullable) | Target batas waktu penyelesaian berdasarkan jam kerja aktif (30 jam). |
| `alasan_keterlambatan` | `VARCHAR(255)` (Nullable) | Keterangan justifikasi jika pemrosesan melewati `deadline_sla`. |
| `file_word` | `VARCHAR(255)` (Nullable) | Lokasi berkas fisik utama format Microsoft Word (`.docx`). |
| `file_lampiran`| `VARCHAR(255)` (Nullable) | Lokasi berkas pendukung (PDF/ZIP). |
| `file_expires_at` | `DATETIME` (Nullable) | Waktu kedaluwarsa berkas fisik untuk pembersihan otomatis. |
| `revisi_count` | `INT UNSIGNED` (Default: 0) | Akumulasi frekuensi revisi yang telah dilakukan. |
| `rating` | `TINYINT UNSIGNED` (Nullable) | Indeks kepuasan pengguna (1 s/d 5) yang diisi pasca surat selesai. |

---

### C. Tabel `surat_tahapans`
Riwayat kronologis pemrosesan 10 tahapan per surat:

| Kolom | Tipe Data | Keterangan & Aturan Bisnis |
|---|---|---|
| `id` | `BIGINT UNSIGNED` (PK) | Identifier log tahapan. |
| `surat_id` | `BIGINT UNSIGNED` (FK -> `surats.id`) | Naskah dinas terkait. |
| `tahap` | `TINYINT UNSIGNED` | Nomor tahapan (1 sampai 10). |
| `nama_tahap` | `VARCHAR(100)` | Deskripsi nama tahapan (misal: "Verifikasi Arsiparis"). |
| `status` | `ENUM` | Nilai: `menunggu`, `proses`, `selesai`, `ditolak`. |
| `diproses_oleh`| `BIGINT UNSIGNED` (FK -> `users.id`) | Pejabat/Admin yang mengeksekusi tahapan tersebut. |
| `catatan` | `TEXT` (Nullable) | Catatan verifikator, instruksi perbaikan, atau nomor agenda. |
| `selesai_pada` | `DATETIME` (Nullable) | Timestamp saat tahapan tersebut dituntaskan. |

---

### D. Tabel `activity_logs`
Audit trail seluruh aktivitas sistem:

| Kolom | Tipe Data | Keterangan & Aturan Bisnis |
|---|---|---|
| `id` | `BIGINT UNSIGNED` (PK) | Identifier log aktivitas. |
| `user_id` | `BIGINT UNSIGNED` (FK -> `users.id`) | Aktor yang melakukan aksi. |
| `action` | `VARCHAR(50)` | Jenis tindakan (`create`, `update`, `approve`, `reject`, `download`, `reset_password`). |
| `model_type` | `VARCHAR(100)` (Nullable) | Nama kelas model yang disentuh (misal: `App\Models\Surat`). |
| `model_id` | `BIGINT UNSIGNED` (Nullable) | ID baris data yang disentuh. |
| `description` | `TEXT` | Narasi penjelasan aktivitas audit dalam bahasa manusia. |
| `changes` | `JSON` (Nullable) | Snapshot perubahan atribut kolom (*old vs new values*). |
| `ip_address` | `VARCHAR(45)` | Alamat IP jaringan lokal/publik aktor. |
| `user_agent` | `TEXT` | Identitas browser dan perangkat yang digunakan. |
