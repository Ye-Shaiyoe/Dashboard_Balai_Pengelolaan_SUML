# ⏱️ Service Level Agreement (SLA) & Jam Kerja
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan formula perhitungan target waktu pelayanan (*Service Level Agreement / SLA*), batasan jam kerja kedinasan, indikator status visual, mekanisme justifikasi keterlambatan, dan pelaporan kepatuhan SLA.

---

## 1. Parameter Dasar Perhitungan SLA Jam Kerja

Sistem mengimplementasikan perhitungan batas waktu berbasis **jam kerja riil** (*business working hours*), bukan jam kalender linier:

- **Target Standar Pelayanan**: **30 Jam Kerja** sejak surat pertama kali diajukan hingga selesai tuntas (Tahap 10).
- **Hari Kerja Aktif**: Senin sampai Jumat.
- **Jam Operasional**: Pukul 07.00 s/d 17.00 WIB (10 jam operasional per hari kerja).
- **Perlakuan Akhir Pekan & Hari Libur**:
  - Sabtu dan Minggu tidak dihitung ke dalam pengurangan waktu deadline.
  - Perhitungan waktu otomatis dijeda (*paused*) di luar jam kerja operasional (antara pukul 17.00 sore hingga 07.00 pagi).
  - Dengan demikian, batas waktu 30 jam kerja setara dengan tepat **3 hari kerja penuh**.

---

## 2. Indikator Visual Status SLA

Pada kartu dan tabel pemantauan surat, sistem menyajikan indikator warna dan ikon berdasarkan sisa waktu terhadap `deadline_sla`:

| Kondisi Waktu | Status SLA (`sla_status`) | Kode Warna Hex | Simbol Ikon | Keterangan & Tindakan |
|---|---|:---:|:---:|---|
| **Surat Telah Selesai** | `ok` | `#22c55e` | 🟢 | Surat tuntas diselesaikan. |
| **Sisa Waktu > 12 Jam** | `ok` | `#22c55e` | 🟢 | **SLA Aman**: Pemrosesan berada dalam batas wajar. |
| **Sisa Waktu $\le$ 12 Jam** | `ok` | `#f59e0b` | 🟡 | **Peringatan Batas Waktu**: Naskah harus segera diproses verifikator. |
| **Melewati `deadline_sla`** | `terlambat` | `#ef4444` | 🔴 | **Overdue (Terlambat)**: Naskah telah melampaui target standar pelayanan. |

### Implementasi Accessor pada Model `Surat`:
```php
public function getSlaColorAttribute(): string
{
    if ($this->status === 'selesai') return '#22c55e';
    if ($this->sla_status === 'terlambat') return '#ef4444';
    
    $sisaJam = $this->deadline_sla ? now()->diffInHours($this->deadline_sla, false) : 99;
    if ($sisaJam <= 12) return '#f59e0b'; // Kuning jika sisa <= 12 jam
    
    return '#22c55e'; // Hijau jika sisa > 12 jam
}
```

---

## 3. Alasan Keterlambatan (*Overtime Justification*)

Apabila sebuah naskah mengalami keterlambatan (warna merah), sistem mewajibkan verifikator/admin untuk memilih salah satu opsi justifikasi resmi pada kolom `alasan_keterlambatan` sebelum dapat melanjutkan atau menyelesaikan surat:

1. `Volume surat tinggi`
2. `Pejabat sedang dinas luar`
3. `Kendala teknis sistem`
4. `Menunggu konfirmasi pihak terkait`
5. `Hari Libur/Tanggal Merah`
6. `Banyak data masuk`
7. `Lainnya` (disertai catatan penjelasan pada teks follow up)

Daftar opsi ini dikonfigurasikan pada konstanta `Surat::ALASAN_KETERLAMBATAN`.

---

## 4. Pelaporan dan Monitoring Kinerja SLA

Sistem menyediakan dua instrumen analisis SLA:
1. **Monitoring Durasi Tiap Tahap** (`/Admin/SLA-Persurat`):
   - Merekam berapa lama naskah berhenti di masing-masing dari 10 tahapan.
   - Mengidentifikasi di meja siapa hambatan (*bottleneck*) birokrasi terjadi.
2. **Ekspor Laporan Kepatuhan SLA** (`/Admin/Analytics/SLA` & `/Admin/SLA-Persurat/export`):
   - Menghasilkan berkas spreadsheet Excel berisikan persentase kepatuhan instansi terhadap target 30 jam kerja.
   - Digunakan oleh Kepala Balai dan Kasubbag TU untuk evaluasi berkala kinerja aparatur.

