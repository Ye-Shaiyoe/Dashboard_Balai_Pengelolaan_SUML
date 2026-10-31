# 🛠️ Otoritas Khusus Tim Bantuan Teknis (IT Support)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini mendokumentasikan fitur dan wewenang khusus teknisi IT (`it_support`), arsitektur tiket kendala teknis, mekanisme siaran notifikasi (*system broadcast*), serta penyelidikan log sistem.

---

## 1. Peran & Batasan Wewenang IT Support

Peran `it_support` sengaja diisolasi dari ranah substansi persuratan kedinasan:
- **TIDAK DAPAT**: Membaca naskah rahasia persuratan kedinasan, membubuhkan nomor surat resmi, menyetujui tahapan, atau menandatangani dokumen.
- **DAPAT**:
  1. Menangani tiket laporan bug teknis dari pengguna atau administrator.
  2. Mengirimkan pengumuman serentak (*broadcast system notification*) ke seluruh pengguna.
  3. Menginspeksi log sistem untuk menelusuri pesan error dan anomali server.

---

## 2. Manajemen Tiket Kendala Teknis (`ItSupportTicket`)

Ketika pengguna atau administrator mengalami galat sistem atau kendala jaringan:
- Mereka dapat membuat tiket bantuan teknis melalui form bantuan sistem.
- Tiket dicatat di tabel `it_support_tickets` dengan atribut:
  - `subjek`, `kategori` (misal: *Bug Sistem*, *Kendala Jaringan LAN*, *Koneksi Database*).
  - `detail`: Deskripsi kronologis galat dan tangkapan layar.
  - `status`: `'open'`, `'resolved'`, atau `'closed'`.
  - `catatan_it`: Langkah pemecahan masalah yang dilakukan oleh teknisi IT.
- IT Support memproses tiket melalui antarmuka `/IT-Support/Dashboard`.

---

## 3. Modul Broadcast Notifikasi Sistem

Apabila tim IT hendak melakukan pemeliharaan server, pembaruan aplikasi, atau memberikan instruksi teknis kepada seluruh staf kantor:

- **Antarmuka Rute**:
  - `GET /IT-Support/Notification/Create`
  - `POST /IT-Support/Notification`
- **Mekanisme Distribusi**:
  - Tim IT menginputkan judul pengumuman, tingkat urgensi (Info, Peringatan, Pemeliharaan Darurat), dan isi instruksi.
  - Sistem mengalirkan notifikasi ini ke seluruh akun pegawai dan admin secara serempak.
  - Banner atau lonceng notifikasi otomatis berdering di peramban pengguna melalui saluran SSE/polling.

---

## 4. Rute Aktivasi Cepat (`become-it-support`)

Untuk kebutuhan debugging dan setup lingkungan pengembangan/darurat:
- Disediakan rute terkontrol: `GET /become-it-support`.
- Dilindungi oleh pembatasan ketat `throttle:3,1` (maksimal 3 panggilan per menit).
- Mengubah peran akun pengetes menjadi `it_support` secara aman di lingkungan lokal.

