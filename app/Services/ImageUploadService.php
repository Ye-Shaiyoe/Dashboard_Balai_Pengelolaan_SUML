<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Validasi dan normalisasi gambar hasil upload (foto profil, TTE, dsb).
 *
 * Kenapa perlu: validasi `mimes` / `image` saja tidak cukup, karena ekstensi
 * file hasil `store()` mengikuti ekstensi dari client. Penyerang bisa mengirim
 * data URI `data:image/php;base64,...` atau file `shell.php` berisi gambar
 * valid (polyglot), lalu file itu tersimpan di disk `public` dan dieksekusi
 * oleh web server sebagai PHP.
 *
 * Solusi: seluruh gambar di-decode ulang (re-encode) memakai GD menjadi PNG
 * sebelum disimpan. Nama file juga selalu dibuat ulang oleh server, sehingga
 * ekstensi tidak lagi ditentukan oleh client.
 */
class ImageUploadService
{
    /** Batas ukuran file gambar (2 MB), sejalan dengan rule `max:2048`. */
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Batas dimensi agar gambar raksasa tidak menghabiskan memori saat di-decode. */
    public const MAX_DIMENSION = 4096;

    /** Format gambar yang diterima. */
    private const ALLOWED_TYPES = [
        IMAGETYPE_PNG,
        IMAGETYPE_JPEG,
        IMAGETYPE_GIF,
        IMAGETYPE_WEBP,
    ];

    /**
     * Ubah data URI base64 (hasil Cropper.js / canvas) menjadi PNG yang aman.
     *
     * @throws ValidationException
     */
    public function fromDataUri(?string $dataUri, string $errorField): string
    {
        $dataUri = trim((string) $dataUri);

        // Hanya format gambar yang dikenal yang boleh masuk. Ini sekaligus
        // menolak trik `data:image/php;base64,...` yang dipakai untuk RCE.
        if (!preg_match('#^data:image/(?:png|jpe?g|gif|webp);base64,#i', $dataUri, $matches)) {
            $this->fail($errorField, 'Format gambar tidak valid. Silakan pilih ulang gambar.');
        }

        $payload = preg_replace('/\s+/', '', substr($dataUri, strlen($matches[0]))) ?? '';

        // base64 membengkak ~4/3 dari ukuran asli; tolak sebelum decode.
        if ($payload === '' || strlen($payload) > (int) ceil(self::MAX_BYTES * 4 / 3) + 4) {
            $this->fail($errorField, 'Ukuran gambar maksimal 2 MB.');
        }

        $binary = base64_decode($payload, true);

        if ($binary === false) {
            $this->fail($errorField, 'Gambar tidak dapat dibaca. Silakan pilih ulang gambar.');
        }

        return $this->normalizeToPng($binary, $errorField);
    }

    /**
     * Baca file upload, lalu normalisasi menjadi PNG yang aman.
     *
     * @throws ValidationException
     */
    public function fromUploadedFile(UploadedFile $file, string $errorField): string
    {
        if (!$file->isValid() || $file->getSize() === false) {
            $this->fail($errorField, 'File gambar gagal diunggah. Silakan coba lagi.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            $this->fail($errorField, 'Ukuran gambar maksimal 2 MB.');
        }

        $binary = @file_get_contents($file->getRealPath());

        if ($binary === false || $binary === '') {
            $this->fail($errorField, 'Gambar tidak dapat dibaca. Silakan pilih ulang gambar.');
        }

        return $this->normalizeToPng($binary, $errorField);
    }

    /**
     * Decode ulang gambar apa pun menjadi PNG murni.
     *
     * Ini membuang metadata dan payload tambahan (polyglot), sehingga file
     * yang tersimpan dijamin hanya berisi data gambar.
     *
     * @throws ValidationException
     */
    private function normalizeToPng(string $binary, string $errorField): string
    {
        if ($binary === '' || strlen($binary) > self::MAX_BYTES) {
            $this->fail($errorField, 'Ukuran gambar maksimal 2 MB.');
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false || !in_array($info[2], self::ALLOWED_TYPES, true)) {
            $this->fail($errorField, 'File bukan merupakan gambar yang valid (PNG/JPEG/GIF/WebP).');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $this->fail($errorField, 'Dimensi gambar tidak wajar (maksimal 4096 x 4096 piksel).');
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            $this->fail($errorField, 'Gambar gagal diproses. Silakan pilih ulang gambar.');
        }

        // Pertahankan transparansi (penting untuk tanda tangan TTE).
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $saved = imagepng($image, null, 6);
        $png = ob_get_clean();

        imagedestroy($image);

        if (!$saved || !is_string($png) || $png === '') {
            $this->fail($errorField, 'Gambar gagal diproses. Silakan pilih ulang gambar.');
        }

        return $png;
    }

    /**
     * @return never
     *
     * @throws ValidationException
     */
    private function fail(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
