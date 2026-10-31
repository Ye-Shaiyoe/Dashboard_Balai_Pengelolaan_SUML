# ⚡ Daftar Perintah Artisan Kustom (Custom Commands)
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini mendokumentasikan seluruh perintah artisan khusus (*custom Artisan commands*) yang dibangun untuk menangani tugas latar belakang, pembersihan storage, integritas data, dan monitoring SLA di lingkungan BP SUML.

---

## 1. Ringkasan Perintah Artisan Kustom

Seluruh perintah konsol ditempatkan di namespace `App\Console\Commands\`:

| No | Perintah Artisan | Kelas Handler Command | Sasaran Utama | Deskripsi Fungsional |
|:---:|---|---|---|---|
| 1 | `files:cleanup-expired` | `CleanupExpiredFiles.php` | Storage Disk & Tabel `surats` | Menghapus berkas fisik Word (`.docx`) dan lampiran yang naskahnya telah berstatus `selesai` dan melewati batas tanggal retensi (`file_expires_at`). Mengosongkan ruang disk server tanpa menghapus metadata surat di database. |
| 2 | `notifications:cleanup` | `CleanupExpiredNotifications.php` | Tabel `notifications` | Menghapus notifikasi sistem yang telah dibaca dan berusia lebih dari 7 hari, mencegah penumpukan jutaan baris data notifikasi di PostgreSQL. |
| 3 | `surat:cleanup-rejected`| `CleanupRejectedSurats.php` | Naskah Ditolak | Mengarsipkan naskah berstatus `ditolak` yang dibiarkan mangkrak dan tidak diperbaiki oleh pegawai pengusul selama lebih dari 5 hari kerja berturut-turut. |
| 4 | `surat:cleanup-orphaned-references` | `CleanupOrphanedDbReferences.php` | Sinkronisasi File DB | Memeriksa integritas berkas di storage; jika path file tercatat di database tetapi berkas fisiknya sudah tidak ada di disk (berkas yatim), referensi tersebut dibersihkan secara aman. |
| 5 | `surat:remind-sla` | `RemindSlaDeadline.php` | Pemantauan SLA Realtime | Memeriksa naskah dinas yang sedang dalam proses verifikasi. Jika waktu tersisa mendekati batas ($\le$ 12 jam) atau telah melewati deadline SLA, sistem secara otomatis membunyikan notifikasi peringatan ke dasbor verifikator. |
| 6 | `system:performance-monitor` | `PerformanceMonitor.php` | Kesehatan Server | Memeriksa beban pemakaian memori, waktu respon query database, dan status kapasitas storage disk server. |

---

## 2. Bedah Teknis Logika Masing-Masing Command

### A. `files:cleanup-expired`
- **Query Filter**:
  ```php
  Surat::where('status', 'selesai')
      ->whereNotNull('file_expires_at')
      ->where('file_expires_at', '<=', now())
      ->whereNull('file_dihapus_pada')
      ->get();
  ```
- **Tindakan**:
  - Menghapus file fisik di `storage/app/private/surat/` dan `lampiran/` via `Storage::disk('local')->delete()`.
  - Mengupdate kolom `file_dihapus_pada = now()`.
  - Kolom `nomor_surat`, `judul`, riwayat verifikator, dan metadata naskah **TIDAK DIHAPUS**.

### B. `surat:remind-sla`
- **Query Filter**: Naskah dengan status `proses` yang belum selesai dan memiliki `deadline_sla`.
- **Tindakan**:
  - Menghitung sisa waktu jam kerja aktif.
  - Jika sisa waktu $\le 12$ jam kerja atau sudah overdue, sistem mencari verifikator yang bertanggung jawab pada `tahap_sekarang` surat tersebut.
  - Mengirimkan `SuratStatusNotification` bertipe urgensi tinggi agar verifikator segera memproses naskah dinas tersebut.

