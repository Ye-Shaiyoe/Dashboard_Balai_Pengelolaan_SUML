<?php

use App\Services\ImageUploadService;
use Illuminate\Validation\ValidationException;

// Service memakai ValidationException yang membutuhkan container Laravel.
uses(Tests\TestCase::class);

/**
 * Buat PNG 1x1 kecil untuk pengujian.
 */
function makeTestPng(bool $withPhpPayload = false): string
{
    $image = imagecreatetruecolor(1, 1);
    imagealphablending($image, false);
    imagesavealpha($image, true);

    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    imagedestroy($image);

    // Tambahkan payload PHP setelah data PNG (polyglot). Validator lama yang
    // hanya memeriksa magic bytes akan meloloskan file seperti ini.
    if ($withPhpPayload) {
        $png .= '<?php echo "pwned"; ?>';
    }

    return $png;
}

test('data uri gambar valid dinormalisasi menjadi png', function () {
    $dataUri = 'data:image/png;base64,' . base64_encode(makeTestPng());

    $result = (new ImageUploadService())->fromDataUri($dataUri, 'profile_photo');

    expect($result)->toStartWith("\x89PNG");
});

test('data uri dengan tipe palsu ditolak', function () {
    // Trik lama: ekstensi diambil dari data URI sehingga file tersimpan .php.
    $dataUri = 'data:image/php;base64,' . base64_encode(makeTestPng());

    expect(fn () => (new ImageUploadService())->fromDataUri($dataUri, 'profile_photo'))
        ->toThrow(ValidationException::class);
});

test('data uri bukan gambar ditolak', function () {
    $dataUri = 'data:image/png;base64,' . base64_encode('<html>not an image</html>');

    expect(fn () => (new ImageUploadService())->fromDataUri($dataUri, 'profile_photo'))
        ->toThrow(ValidationException::class);
});

test('polyglot png berisi kode php dibersihkan saat re-encode', function () {
    $dataUri = 'data:image/png;base64,' . base64_encode(makeTestPng(withPhpPayload: true));

    $result = (new ImageUploadService())->fromDataUri($dataUri, 'profile_photo');

    expect($result)->not->toContain('<?php');
});

test('gambar melebihi batas ukuran ditolak', function () {
    // String base64 yang lebih panjang dari batas 2 MB.
    $dataUri = 'data:image/png;base64,' . str_repeat('A', 3_000_000);

    expect(fn () => (new ImageUploadService())->fromDataUri($dataUri, 'profile_photo'))
        ->toThrow(ValidationException::class);
});
