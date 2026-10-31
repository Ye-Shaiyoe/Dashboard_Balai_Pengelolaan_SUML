# 👤 Kamus Data Tabel `users` & Autentikasi
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menyajikan kamus data (*data dictionary*) mendalam untuk tabel `users`, struktur indeks, aturan validasi kolom, serta model autentikasi kredensial.

---

## 1. Struktur Kolom Tabel `users`

Tabel `users` menyimpan seluruh akun pengguna, baik Pegawai Pengusul, Pejabat Verifikator, Pimpinan, maupun Tim IT:

| Nama Kolom | Tipe Data PostgreSQL | Atribut / Nullable | Nilai Default | Deskripsi & Aturan Bisnis |
|---|---|:---:|:---:|---|
| `id` | `BIGSERIAL` | Primary Key, Not Null | Auto | Pengenal unik baris tabel (*Internal ID*). |
| `uuid` | `UUID` | Unique, Not Null | `Str::uuid()` | Pengenal publik untuk *Route Model Binding* (`{user:uuid}`). |
| `name` | `VARCHAR(255)` | Not Null | - | Nama lengkap pegawai beserta gelar dinas. |
| `email` | `VARCHAR(255)` | Unique, Not Null | - | Alamat email resmi kedinasan pegawai. |
| `password` | `VARCHAR(255)` | Not Null | - | Hash kata sandi menggunakan algoritma Bcrypt. |
| `role` | `VARCHAR(50)` | Not Null | `'user'` | Pilihan: `user`, `admin_aspirasi`, `admin_kasubbag_tu`, `admin_kepala_balai`, `it_support`, `admin`. |
| `nip` | `VARCHAR(50)` | Nullable | `NULL` | Nomor Induk Pegawai resmi (16–20 digit angka). |
| `nip_hash` | `CHAR(64)` | Unique, Nullable | `NULL` | Digest HMAC-SHA256 dari NIP dengan `APP_KEY` untuk pencarian login cepat. |
| `role_selected`| `BOOLEAN` | Not Null | `false` | Menandakan apakah admin telah mengonfirmasi spesialisasi perannya di `/Admin/Role-Selection`. |
| `profile_photo`| `VARCHAR(255)` | Nullable | `NULL` | Path relatif foto profil pengguna di `storage/app/public/`. |
| `signature_path`| `VARCHAR(255)`| Nullable | `NULL` | Path scan tanda tangan basah di `storage/app/private/tte/`. |
| `signature_pin` | `VARCHAR(255)`| Nullable | `NULL` | Hash Bcrypt PIN keamanan pembubuhan TTE (khusus pimpinan). |
| `switch_token` | `VARCHAR(100)` | Nullable | `NULL` | Token temporer untuk fitur alih akun instan (*Switch Account*). |
| `switch_token_expires_at` | `TIMESTAMP` | Nullable | `NULL` | Batas kedaluwarsa token alih akun. |
| `email_verified_at` | `TIMESTAMP` | Nullable | `NULL` | Timestamp verifikasi alamat email. |
| `remember_token` | `VARCHAR(100)` | Nullable | `NULL` | Token sesi "Ingat Saya" (*Remember Me*). |
| `created_at` | `TIMESTAMP` | Nullable | `now()` | Waktu pembuatan akun pegawai. |
| `updated_at` | `TIMESTAMP` | Nullable | `now()` | Waktu pembaruan profil terakhir. |

---

## 2. Struktur Indeks & Optimasi Performa

1. **`users_email_unique`**: Indeks unik pada kolom `email` menjamin tidak ada duplikasi akun.
2. **`users_uuid_unique`**: Indeks unik pada kolom `uuid` mempercepat pencarian profil tanpa mengekspos integer auto-increment.
3. **`users_nip_hash_unique`**: Indeks unik pada kolom `nip_hash` untuk pencarian login menggunakan NIP secara konstan $O(1)$.

---

## 3. Logika Mutasi & Hash Otomatis pada Model `User`

Ketika atribut `nip` diisi atau diperbarui pada objek user, model secara otomatis mengisi kolom `nip_hash`:

```php
protected static function boot()
{
    parent::boot();
    
    // Auto-generate UUID
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = Str::uuid();
        }
    });

    // Auto-hash NIP saat kolom nip berubah
    static::saving(function ($model) {
        if ($model->isDirty('nip')) {
            $model->nip_hash = $model->nip ? self::hashNip($model->nip) : null;
        }
    });
}
```

