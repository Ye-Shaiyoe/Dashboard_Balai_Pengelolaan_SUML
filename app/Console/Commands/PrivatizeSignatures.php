<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Pindahkan file tanda tangan TTE yang masih tersimpan di disk `public`
 * ke disk `private`.
 *
 * Sebelum perbaikan keamanan, TTE disimpan di `storage/app/public/signatures`
 * yang dapat diakses siapa saja lewat URL `/storage/signatures/...`.
 * Command ini idempoten: aman dijalankan berulang kali.
 */
class PrivatizeSignatures extends Command
{
    protected $signature = 'signatures:privatize
                            {--keep-public : Biarkan salinan lama tetap ada di disk public}';

    protected $description = 'Pindahkan file tanda tangan TTE dari disk public ke disk private';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');

        if (!$public->exists('signatures')) {
            $this->info('Tidak ada folder signatures di disk public. Tidak ada yang perlu dipindahkan.');

            return self::SUCCESS;
        }

        $files = $public->files('signatures');

        if (empty($files)) {
            $this->info('Folder signatures di disk public kosong.');

            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;

        foreach ($files as $path) {
            $name = basename($path);
            $target = 'signatures/' . $name;

            // Hanya file gambar dengan nama aman yang diproses.
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(png|jpe?g|gif|webp)$/i', $name)) {
                $this->warn("Dilewati (nama tidak aman): {$name}");
                $skipped++;
                continue;
            }

            if (!$private->exists($target)) {
                $stream = $public->readStream($path);

                if ($stream === null) {
                    $this->error("Gagal membaca file: {$path}");
                    $skipped++;
                    continue;
                }

                $private->writeStream($target, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (!$private->exists($target)) {
                $this->error("Gagal menyalin file: {$path}");
                $skipped++;
                continue;
            }

            if (!$this->option('keep-public')) {
                $public->delete($path);
            }

            $moved++;
            $this->line("Dipindahkan: {$path}");
        }

        $this->newLine();
        $this->info("Selesai. {$moved} file dipindahkan, {$skipped} dilewati.");

        if (!$this->option('keep-public')) {
            $this->line('Salinan di disk public sudah dihapus, sehingga TTE tidak lagi dapat diakses langsung lewat /storage/signatures/...');
        }

        return self::SUCCESS;
    }
}
