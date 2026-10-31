# 📜 Audit Trail & Pencatatan Log Aktivitas
### Sistem Informasi Dashboard TU BP SUML

Dokumen ini menjelaskan implementasi pencatatan rekam jejak (*audit trail*), arsitektur model `ActivityLog`, pemanfaatan trait Eloquent `LogsUserActivity`, serta mekanisme pengawasan transparansi di lingkungan kantor BP SUML.

---

## 1. Pentingnya Audit Trail dalam Tata Persuratan Kedinasan

Dalam birokrasi pemerintahan, setiap perubahan status, pengesahan, penomoran, penolakan, pengunduhan dokumen dinas, hingga reset kredensial wajib memiliki pertanggungjawaban yang jelas (*non-repudiation*).

Sistem Dashboard TU BP SUML menjamin akuntabilitas tersebut dengan merekam:
1. **Siapa pelakunya** (`user_id`, nama, dan email pengguna).
2. **Kapan aksi dilakukan** (`created_at` presisi hingga detik).
3. **Dari mana aksi berasal** (`ip_address` jaringan kantor dan `user_agent` perangkat).
4. **Apa yang dilakukan** (`action`: `create`, `update`, `approve`, `reject`, `download`, `delete`, `reset_password`, `login`, `logout`).
5. **Data apa yang terpengaruh** (`model_type` dan `model_id`).
6. **Perubahan apa yang terjadi** (snapshot `changes` dalam format JSON berisi nilai kolom sebelum vs sesudah perubahan).

---

## 2. Implementasi Otomatis via Trait `LogsUserActivity`

Setiap model yang memerlukan pelacakan (seperti `Surat`, `User`, dan `Aspirasi`) menggunakan trait `App\Traits\LogsUserActivity`:

```php
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsUserActivity
{
    public static function bootLogsUserActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('create', 'Membuat data baru');
        });

        static::updated(function ($model) {
            $changes = [
                'before' => array_intersect_key($model->getOriginal(), $model->getDirty()),
                'after'  => $model->getDirty(),
            ];
            $model->recordActivity('update', 'Memperbarui data', $changes);
        });

        static::deleted(function ($model) {
            $model->recordActivity('delete', 'Menghapus data');
        });
    }

    public function recordActivity(string $action, string $description, ?array $changes = null): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'model_type'  => get_class($this),
            'model_id'    => $this->getKey(),
            'description' => $description,
            'changes'     => $changes ? json_encode($changes) : null,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }
}
```

---

## 3. Pencatatan Aksi Spesifik via `ActivityLogHelper`

Untuk aksi di luar operasi standar model (seperti pengunduhan naskah Word, persetujuan tahap tertentu, atau reset password manual):

```php
use App\Helpers\ActivityLogHelper;

// Contoh saat Kasubbag TU melakukan reset kata sandi pegawai:
ActivityLogHelper::log(
    action: 'reset_password',
    description: "Kasubbag TU mereset kata sandi pegawai: {$user->name} ({$user->email})",
    model: $user
);

// Contoh saat naskah dinas diunduh:
ActivityLogHelper::log(
    action: 'download',
    description: "Mengunduh berkas surat: {$surat->judul}",
    model: $surat
);
```

---

## 4. Antarmuka Pemantauan & Ekspor Log

Log audit dapat ditinjau melalui dua antarmuka:

1. **Sisi Pegawai** (`/activity-log`):
   - Pegawai dapat melihat seluruh riwayat aktivitas akun pribadinya (pengajuan surat, pengunggahan revisi, login, logout).
   - Menyediakan fitur ekspor riwayat aktivitas ke file spreadsheet Excel (`/activity-log/export`).
2. **Sisi Administrator** (`/Admin/Settings/Logs`):
   - Administrator teknis dapat membaca file log harian Laravel (`laravel.log`).
   - Menyediakan opsi unduh log mentah (`/Admin/Settings/Logs/download/{file}`) dan pembersihan log (`/Admin/Settings/Logs/clear/{file}`).

