# 🗄️ Diagram Relasi Entitas (Entity-Relationship Diagram)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menyajikan gambaran visual menyeluruh mengenai struktur relasi antar tabel basis data **PostgreSQL** yang menopang seluruh operasional **Dashboard TU BP SUML**.

---

## 1. Diagram Relasi Entitas Komprehensif (ERD)

```mermaid
erDiagram
    USERS ||--o{ SURATS : "mengajukan (1:N)"
    USERS ||--o{ SURAT_TAHAPANS : "memproses (1:N)"
    USERS ||--o{ SURAT_DELETE_REQUESTS : "meminta_atau_menyetujui (1:N)"
    USERS ||--o{ ASPIRASIS : "mengirim (1:N)"
    USERS ||--o{ ACTIVITY_LOGS : "mencatat_aktivitas (1:N)"
    USERS ||--o{ IT_SUPPORT_TICKETS : "melaporkan (1:N)"
    
    SURATS ||--|{ SURAT_TAHAPANS : "memiliki_10_tahapan (1:N)"
    SURATS ||--o{ SURAT_DELETE_REQUESTS : "dihapus_melalui (1:N)"

    USERS {
        bigserial id PK
        uuid uuid UK "Route binding key"
        varchar name "Nama lengkap pegawai"
        varchar email UK "Email resmi kedinasan"
        varchar password "Bcrypt hashed password"
        varchar role "user, admin_aspirasi, admin_kasubbag_tu, dll"
        varchar nip "NIP 18 digit"
        char nip_hash UK "HMAC-SHA256 untuk fast search"
        boolean role_selected "Status konfirmasi peran admin"
        varchar profile_photo "Path foto profil publik"
        varchar signature_path "Path file TTE privat"
        varchar signature_pin "Hashed PIN TTE"
        varchar switch_token "Token alih akun instan"
        timestamp switch_token_expires_at
        timestamp created_at
        timestamp updated_at
    }

    SURATS {
        bigserial id PK
        uuid uuid UK "Kode pelacakan & QR code publik"
        bigint user_id FK "Pengusul (users.id)"
        varchar judul "Perihal / judul naskah dinas"
        varchar jenis "nota_dinas, surat_dinas, dll"
        varchar sifat "biasa, penting, rahasia, segera"
        varchar tujuan "Penerima surat / instansi tujuan"
        text catatan_pengusul "Catatan awal pengusul"
        varchar file_word "Path naskah .docx di private storage"
        varchar file_lampiran "Path file pendukung PDF/ZIP"
        varchar nomor_surat "Nomor registrasi resmi BP SUML"
        date tanggal_surat "Tanggal resmi surat terbit"
        smallint tahap_sekarang "Posisi tahapan 1 s/d 10"
        varchar status "draft, proses, revisi, revisi_admin, selesai, ditolak"
        boolean perlu_follow_up "Indikator tindak lanjut"
        text catatan_follow_up "Catatan telaah lanjutan"
        timestamp deadline_sla "Batas waktu 30 jam kerja"
        varchar alasan_keterlambatan "Justifikasi jika SLA overdue"
        timestamp disetujui_pada "Waktu pengesahan final"
        timestamp file_dihapus_pada "Waktu berkas fisik dibersihkan"
        timestamp file_expires_at "Batas waktu retensi fisik"
        boolean status_revisi "Pernah/sedang revisi"
        integer revisi_count "Frekuensi pengulangan revisi"
        timestamp revisi_uploaded_at "Waktu unggah revisi terakhir"
        smallint rating "Penilaian kepuasan 1-5 bintang"
        timestamp created_at
        timestamp updated_at
    }

    SURAT_TAHAPANS {
        bigserial id PK
        bigint surat_id FK "surats.id"
        smallint tahap "Tahap ke 1 s/d 10"
        varchar nama_tahap "Label nama tahap"
        varchar status "menunggu, proses, selesai, ditolak"
        bigint diproses_oleh FK "users.id (Verifikator pelaksana)"
        text catatan "Catatan verifikator / instruksi revisi"
        timestamp selesai_pada "Waktu tahap dituntaskan"
        timestamp created_at
        timestamp updated_at
    }

    SURAT_DELETE_REQUESTS {
        bigserial id PK
        bigint surat_id FK "surats.id"
        bigint user_id FK "Pemohon pembatalan (users.id)"
        bigint admin_id FK "Kasubbag TU yang memproses (users.id)"
        text alasan "Alasan permohonan pembatalan surat"
        varchar status "pending, disetujui, ditolak"
        text admin_catatan "Catatan Kasubbag TU"
        timestamp admin_approved_at "Waktu keputusan pembatalan"
        timestamp created_at
        timestamp updated_at
    }

    ASPIRASIS {
        bigserial id PK
        uuid uuid UK
        bigint user_id FK "users.id"
        varchar judul "Subjek saran / keluhan"
        text isi "Uraian pesan pengaduan"
        varchar tujuan "admin atau it_support"
        varchar kategori "saran, keluhan, pertanyaan"
        varchar status "baru, diproses, selesai"
        text balasan "Tanggapan dari pihak penanggung jawab"
        timestamp dibalas_at
        timestamp created_at
        timestamp updated_at
    }

    IT_SUPPORT_TICKETS {
        bigserial id PK
        bigint admin_id FK "Pelapor tiket (users.id)"
        varchar subjek "Judul kendala teknis"
        varchar kategori "Bug, Jaringan, Database, dll"
        text detail "Uraian teknis permasalahan"
        varchar status "open, resolved, closed"
        text catatan_it "Solusi pemecahan masalah oleh IT"
        timestamp created_at
        timestamp updated_at
    }

    ACTIVITY_LOGS {
        bigserial id PK
        bigint user_id FK "Pelaksana aksi (users.id)"
        varchar action "create, update, approve, reject, dll"
        varchar model_type "Namespace model target"
        bigint model_id "ID entitas target"
        text description "Uraian deskriptif audit"
        jsonb changes "Snapshot diff before vs after"
        varchar ip_address "IP Address jaringan kantor"
        text user_agent "Identitas perangkat & browser"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 2. Karakteristik Basis Data PostgreSQL yang Digunakan

1. **Foreign Key Constraints**: Menjaga konsistensi relasional (misal: saat surat dihapus atau direferensikan).
2. **Kolom Tipe JSONB**: Kolom `changes` pada tabel `activity_logs` menggunakan tipe data native `jsonb` PostgreSQL yang mendukung query indeks internal dan penghematan ruang penyimpanan.
3. **B-Tree & Hash Indexing**:
   - Kolom `nip_hash` diindeks khusus untuk pencarian instan bernilai konstan $O(1)$.
   - Kolom `uuid` diindeks unik (*unique key*) sebagai kunci rute publik tanpa mengekspos identifier primary key (`id`).

