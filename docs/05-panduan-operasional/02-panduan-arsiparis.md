# 🏛️ Panduan Operasional Tim Arsiparis (`admin_aspirasi`)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini merupakan petunjuk teknis operasional bagi **Tim Arsiparis** dalam memverifikasi kesesuaian tata naskah dinas (Tahap 2), menerbitkan nomor surat resmi (Tahap 5), mendistribusikan naskah via TNDe/Srikandi (Tahap 7 & 8), mengarsipkan dokumen (Tahap 9), serta mengelola template surat baku.

---

## 1. Verifikasi Format & Tata Naskah (Tahap 2)

Sebagai gerbang pertama verifikasi pasca pengajuan naskah oleh pegawai:

1. Buka menu **"Surat Masuk"** atau **"Antrean Surat"** (`/Admin/Surat`).
2. Filter antrean akan otomatis menampilkan surat-surat yang berada pada **Tahap 2**.
3. Klik tombol **Periksa Naskah**:
   - Pratinjau draf berkas Microsoft Word langsung di browser atau klik tombol unduh naskah.
   - Periksa kelengkapan kop surat, format tanggal, penulisan nama jabatan, penggunaan template baku, kerapian margin, serta kejelasan lampiran pendukung.
4. Tentukan keputusan:
   - **Setujui Format**: Klik tombol hijau **"Setujui Tahap Ini"**. Naskah otomatis berpindah ke **Tahap 3 (Verifikasi Kasubbag TU)**.
   - **Minta Revisi**: Klik tombol merah **"Tolak / Revisi"**. Ketikkan catatan koreksi secara terperinci (misal: *"Format kop surat belum sesuai Keputusan Kepala Balai No. 12 Tahun 2025"*). Naskah dikembalikan ke pegawai pengusul.

---

## 2. Penomoran Surat Resmi (Tahap 5)

Setelah naskah disetujui oleh Kepala Balai di Tahap 4:

1. Surat masuk ke antrean Arsiparis di **Tahap 5 (Penomoran Surat)**.
2. Buka detail surat, buka buku agenda penomoran kantor BP SUML.
3. Inputkan:
   - **Nomor Surat Resmi**: Sesuai kode klasifikasi naskah dan nomor urut agenda kantor (misal: `B/145/BP-SUML/TU/V/2026`).
   - **Tanggal Surat Resmi**: Tanggal penetapan surat yang sah.
4. Klik tombol **"Simpan & Lanjutkan ke Tanda Tangan"**. Naskah akan diteruskan ke antrean Kepala Balai untuk penandatanganan elektronik.

---

## 3. Ekspedisi Naskah via TNDe & Srikandi (Tahap 7 & 8)

Setelah naskah dibubuhi TTE oleh Kepala Balai pada Tahap 6:
1. **Tahap 7 (Pengiriman via TNDe)**:
   - Arsiparis mendistribusikan naskah keluar melalui sistem aplikasi Tata Naskah Dinas Elektronik (TNDe).
   - Klik **"Konfirmasi Pengiriman TNDe Selesai"**.
2. **Tahap 8 (Pencatatan Srikandi)**:
   - Input berkas atau sinkronisasi nomor registrasi pada aplikasi kearsipan nasional **Srikandi**.
   - Klik **"Konfirmasi Srikandi Selesai"**.

---

## 4. Pengarsipan Digital (Tahap 9) & Finalisasi (Tahap 10)

1. Pada **Tahap 9 (Pengarsipan)**:
   - Arsiparis memastikan berkas final yang telah bertanda tangan dan bernomor tersimpan ke folder repositori arsip.
   - Klik **"Arsipkan Naskah"**.
2. Sistem otomatis menggeser naskah ke **Tahap 10 (Follow Up / Selesai)**.
3. Mengeset tanggal batas aktif berkas fisik (`file_expires_at`) dan membuka akses unduh bagi pegawai pengusul.

---

## 5. Manajemen Master Template Surat Dinas

Arsiparis bertanggung jawab memelihara format baku naskah dinas di menu `/Admin/Template`:
1. Klik **"Unggah Template Baru"**.
2. Masukkan nama template (misal: *Template Nota Dinas 2026*, *Template Surat Tugas*, *Template Undangan Resmi*).
3. Unggah file berformat Microsoft Word (`.docx`).
4. Template yang diunggah akan langsung muncul di halaman pegawai pengusul agar dapat diunduh sebagai acuan penulisan naskah.

