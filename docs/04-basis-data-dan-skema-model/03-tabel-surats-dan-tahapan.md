# 📄 Kamus Data Tabel `surats`, `surat_tahapans` & Permohonan Hapus
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini memaparkan kamus data (*data dictionary*) teknis untuk tabel utama transaksi persuratan: `surats`, tabel riwayat `surat_tahapans`, dan tabel permohonan pembatalan `surat_delete_requests`.

---

## 1. Kamus Data Tabel `surats`

Tabel `surats` adalah tabel master yang menyimpan seluruh dokumen kedinasan:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Nilai Default | Deskripsi & Aturan Bisnis |
|---|---|:---:|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Auto | Pengenal unik database (*internal surrogate key*). |
| `uuid` | `UUID` | Unique, Not Null | `Str::uuid()` | Kunci rute publik (`/surat/{surat}` dan `/v/{uuid}`). |
| `user_id` | `BIGINT` | FK -> `users.id`, Not Null | - | Pegawai internal yang membuat dan mengajukan naskah. |
| `judul` | `VARCHAR(255)` | Not Null | - | Perihal atau subjek naskah dinas. |
| `jenis` | `VARCHAR(50)` | Not Null | - | Jenis naskah: `nota_dinas`, `surat_dinas`, `surat_keputusan`, `surat_pernyataan`, `surat_keterangan`, `surat_undangan`, `surat_lainnya`. |
| `sifat` | `VARCHAR(50)` | Not Null | - | Derajat urgensi: `biasa`, `penting`, `rahasia`, `segera`. |
| `tujuan` | `VARCHAR(255)` | Not Null | - | Pihak penerima, instansi luar, atau unit kerja tujuan. |
| `catatan_pengusul` | `TEXT` | Nullable | `NULL` | Catatan latar belakang pengajuan dari pegawai. |
| `file_word` | `VARCHAR(255)` | Nullable | `NULL` | Path relatif berkas Microsoft Word (`.docx`) di disk privat. |
| `file_lampiran` | `VARCHAR(255)` | Nullable | `NULL` | Path berkas lampiran pendukung (PDF/ZIP). |
| `nomor_surat` | `VARCHAR(100)` | Nullable | `NULL` | Nomor surat dinas resmi setelah disetujui (diisi di Tahap 5). |
| `tanggal_surat` | `DATE` | Nullable | `NULL` | Tanggal resmi yang tertera pada naskah kedinasan. |
| `tahap_sekarang` | `SMALLINT` | Not Null | `1` | Posisi tahapan aktif saat ini (antara angka 1 sampai 10). |
| `status` | `VARCHAR(50)` | Not Null | `'proses'` | Status naskah: `draft`, `proses`, `revisi`, `revisi_admin`, `selesai`, `ditolak`. |
| `perlu_follow_up` | `BOOLEAN` | Not Null | `false` | Menandakan apakah naskah memerlukan tindak lanjut khusus. |
| `catatan_follow_up`| `TEXT` | Nullable | `NULL` | Uraian catatan tindak lanjut dari admin/verifikator. |
| `deadline_sla` | `TIMESTAMP` | Nullable | `NULL` | Batas waktu penyelesaian berbasis 30 jam kerja operasional. |
| `alasan_keterlambatan`| `VARCHAR(255)`| Nullable | `NULL` | Pilihan alasan justifikasi resmi jika `sla_status` = terlambat. |
| `disetujui_pada` | `TIMESTAMP` | Nullable | `NULL` | Waktu persetujuan final oleh pimpinan. |
| `file_dihapus_pada`| `TIMESTAMP` | Nullable | `NULL` | Waktu berkas fisik Word/lampiran dibersihkan dari server. |
| `file_expires_at` | `TIMESTAMP` | Nullable | `NULL` | Batas kedaluwarsa berkas fisik untuk pembersihan otomatis. |
| `status_revisi` | `BOOLEAN` | Not Null | `false` | Indikator apakah naskah pernah masuk ke alur koreksi. |
| `revisi_count` | `INTEGER` | Not Null | `0` | Akumulasi jumlah kali surat ini direvisi oleh pengusul. |
| `revisi_uploaded_at`| `TIMESTAMP` | Nullable | `NULL` | Waktu pengunggahan berkas perbaikan terbaru oleh pengusul. |
| `rating` | `SMALLINT` | Nullable | `NULL` | Nilai kepuasan layanan pengusul (skala 1 s/d 5 bintang). |
| `created_at` | `TIMESTAMP` | Nullable | `now()` | Tanggal & waktu pengajuan naskah pertama kali. |
| `updated_at` | `TIMESTAMP` | Nullable | `now()` | Waktu pembaruan naskah terakhir. |

---

## 2. Kamus Data Tabel `surat_tahapans`

Menyimpan 10 baris riwayat tahapan untuk setiap naskah surat:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Deskripsi |
|---|---|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Pengenal baris tahapan. |
| `surat_id` | `BIGINT` | FK -> `surats.id`, Not Null | Naskah dinas terkait (cascade delete). |
| `tahap` | `SMALLINT` | Not Null | Urutan tahapan (1 sampai 10). |
| `nama_tahap` | `VARCHAR(100)` | Not Null | Label tahapan (misal: "Verifikasi Arsiparis"). |
| `status` | `VARCHAR(50)` | Not Null | `menunggu`, `proses`, `selesai`, `ditolak`. |
| `diproses_oleh` | `BIGINT` | FK -> `users.id`, Nullable | Pejabat/Admin yang menyelesaikan tahap tersebut. |
| `catatan` | `TEXT` | Nullable | Catatan hasil telaah verifikator atau alasan penolakan. |
| `selesai_pada` | `TIMESTAMP` | Nullable | Waktu tahap tersebut dinyatakan tuntas disetujui. |
| `created_at` | `TIMESTAMP` | Nullable | Waktu baris inisialisasi dibuat. |
| `updated_at` | `TIMESTAMP` | Nullable | Waktu baris tahapan diperbarui. |

---

## 3. Kamus Data Tabel `surat_delete_requests`

Mengelola permohonan pembatalan/penghapusan surat oleh pegawai ke Kasubbag TU:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Deskripsi |
|---|---|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Pengenal permohonan. |
| `surat_id` | `BIGINT` | FK -> `surats.id`, Not Null | Naskah dinas yang hendak dibatalkan. |
| `user_id` | `BIGINT` | FK -> `users.id`, Not Null | Pegawai yang mengajukan permohonan pembatalan. |
| `admin_id` | `BIGINT` | FK -> `users.id`, Nullable | Kasubbag TU yang memproses permohonan. |
| `alasan` | `TEXT` | Not Null | Alasan resmi pembatalan naskah oleh pengusul. |
| `status` | `VARCHAR(50)` | Not Null | `pending`, `disetujui`, `ditolak`. |
| `admin_catatan` | `TEXT` | Nullable | Uraian pertimbangan Kasubbag TU. |
| `admin_approved_at`| `TIMESTAMP` | Nullable | Waktu Kasubbag TU memberikan keputusan permohonan. |
| `created_at` | `TIMESTAMP` | Nullable | Waktu permohonan diajukan. |
| `updated_at` | `TIMESTAMP` | Nullable | Waktu status permohonan berubah. |

