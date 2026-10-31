# 🔍 Verifikasi Publik Dokumen Berbasis QR Code
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini mendokumentasikan fitur verifikasi otentisitas dokumen publik melalui URL berkode UUID dan pemindaian QR Code tanpa memerlukan login.

---

## 1. Latar Belakang & Tujuan Fitur

Setiap dokumen surat dinas resmi yang diterbitkan oleh Balai Pengelolaan Sarana dan Utilitas Metrologi Legal (BP SUML) didistribusikan kepada pihak internal maupun eksternal instansi. 

Untuk memastikan bahwa naskah cetak atau PDF yang beredar merupakan dokumen asli yang sah, tidak mengalami pemalsuan nomor surat, dan telah ditandatangani oleh Kepala Balai, sistem menyematkan kode QR pada lembar naskah resmi.

---

## 2. Struktur Tautan Publik & Keamanan UUID

Setiap naskah memiliki pengenal unik universal (*Universally Unique Identifier / UUID v4*) yang dibuat otomatis saat naskah diinisiasi (`Surat::boot()`):

- **Format URL Verifikasi**:
  ```
  http://<domain-atau-ip-server>/v/{uuid}
  ```
- **Nama Rute**: `surat.verifikasi`
- **Controller**: `App\Http\Controllers\VerifikasiSuratController::class`
- **Keamanan Rute**:
  - Rute ini bersifat terbuka (*public access*) tanpa memerlukan middleware autentikasi (`guest`).
  - Menggunakan UUID acak 36 karakter (misal: `c8d19f52-4e09-4d37-975d-318e47bcf308`), sehingga pihak luar tidak dapat menebak (*enumeration attack*) nomor urut surat (`id` primary key tetap tersembunyi).

---

## 3. Informasi yang Disajikan pada Halaman Verifikasi

Ketika penerima surat memindai kode QR menggunakan kamera ponsel atau membuka tautan:

1. **Badge Otentisitas**:
   - Jika dokumen valid dan telah melalui persetujuan resmi:
     > **"DOKUMEN RESMI TERVERIFIKASI BP SUML"** (Latar Hijau Aman)
   - Jika dokumen belum tuntas atau status masih draf/revisi:
     > **"DOKUMEN DALAM PROSES / BELUM SAH"** (Latar Kuning Waspada)
2. **Metadata Naskah yang Ditampilkan**:
   - Nomor Surat Resmi (misal: `B/123/BP-SUML/TU/IV/2026`)
   - Tanggal Terbit Surat
   - Perihal / Judul Surat
   - Sifat dan Jenis Surat (Nota Dinas, Surat Keputusan, dll)
   - Pejabat Penandatangan Resmi (Kepala Balai)
   - Tanggal & Waktu Pembubuhan Tanda Tangan Elektronik (TTE)
3. **Privasi & Keamanan Konten**:
   - Halaman verifikasi publik **tidak menampilkan** file lampiran rahasia atau catatan telaah internal verifikator, menjaga kerahasiaan data internal kantor.

