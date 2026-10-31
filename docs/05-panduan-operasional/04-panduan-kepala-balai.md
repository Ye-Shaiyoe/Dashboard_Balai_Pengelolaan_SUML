# 👑 Panduan Operasional Kepala Balai (`admin_kepala_balai`)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini merupakan panduan praktis bagi **Kepala Balai BP SUML** dalam menelaah permohonan naskah kedinasan (Tahap 4), membubuhkan Tanda Tangan Elektronik resmi (Tahap 6), serta memantau ringkasan eksekutif tata persuratan.

---

## 1. Persetujuan Naskah Dinas Pimpinan (Tahap 4)

Setelah surat melewati pemeriksaan berjenjang oleh Arsiparis dan Kasubbag TU:

1. Masuk ke sistem menggunakan akun Kepala Balai.
2. Buka menu **"Surat Masuk"** atau antrean pimpinan.
3. Seluruh naskah yang memerlukan persetujuan Kepala Balai berada pada **Tahap 4 (Persetujuan Kepala Balai)**.
4. Klik **Periksa Surat**:
   - Tinjau ringkasan tujuan, perihal, dan draf naskah Word.
   - Periksa catatan telaah dari Kasubbag TU dan Arsiparis pada riwayat tahapan di sebelah kanan.
5. Berikan keputusan:
   - **Setujui Surat**: Klik tombol **"Setujui Naskah"**. Surat berpindah ke Tahap 5 untuk penomoran resmi oleh Arsiparis.
   - **Tolak / Arahan Perbaikan**: Klik **"Tolak / Revisi"**, ketikkan arahan substansi (misal: *"Ubah alokasi peserta atau tanggal pelaksanaan"*). Surat otomatis kembali ke pegawai pengusul.

---

## 2. Pembubuhan Tanda Tangan Elektronik (TTE / Tahap 6)

Setelah naskah mendapatkan nomor surat resmi dari Arsiparis di Tahap 5:

1. Surat masuk kembali ke antrean Kepala Balai pada **Tahap 6 (Tanda Tangan DS)**.
2. Buka naskah dinas final yang telah memuat nomor resmi dan tanggal terbit.
3. Masukkan **PIN Keamanan TTE** (6 digit PIN privat yang telah didaftarkan pada profil Kepala Balai).
4. Klik tombol hijau **"Bubuhkan TTE & Sahkan Dokumen"**.
5. Sistem secara otomatis:
   - Memverifikasi hash PIN TTE.
   - Merekkatkan gambar tanda tangan digital resmi dari penyimpanan terproteksi.
   - Menerbitkan kode QR verifikasi publik (`/v/{uuid}`) pada lembar dokumen.
   - Menggeser naskah ke **Tahap 7 (Pengiriman TNDe)**.

---

## 3. Pendaftaran & Pengubahan PIN TTE Pribadi

1. Klik foto avatar di pojok kanan atas, pilih **"Profil Saya"** (`/profile`).
2. Gulir ke bagian **"Pengaturan Tanda Tangan Elektronik (TTE)"**.
3. Unggah scan tanda tangan basah transparan (format PNG).
4. Masukkan **PIN Baru (6 Digit)** dan konfirmasi PIN.
5. Klik **"Simpan Pengaturan TTE"**. PIN akan disimpan dalam bentuk hash terenkripsi dan tidak dapat dilihat oleh staf admin mana pun.

---

## 4. Pemantauan Ringkasan Eksekutif & Statistik SLA

Kepala Balai memiliki akses visual menyeluruh ke metrik persuratan kantor:
- **Dashboard Eksekutif**: Memantau volume surat masuk bulan ini, surat yang sedang diproses, dan rata-rata durasi penyelesaian naskah.
- **Tingkat Kepatuhan SLA**: Memantau apakah pelayanan tata usaha memenuhi target standar 30 jam kerja operasional.
- **Tabel SLA Persurat**: Mengetahui durasi waktu naskah di masing-masing meja pejabat dan meninjau justifikasi jika terjadi keterlambatan.

