<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ZipArchiveService
{
    public function createFromFiles(Collection $files, string $archiveName): BinaryFileResponse
    {
        $zipPath = $this->buildZip($files);

        return response()->download($zipPath, $archiveName)->deleteFileAfterSend(true);
    }

    private function buildZip(Collection $files): string
    {
        $zipPath = storage_path('app/temp/archive_' . uniqid() . '.zip');

        $this->ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Could not create archive.');

        foreach ($files as $file) {
            $fullPath = Storage::disk('local')->path($file->path);

            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->original_name);
            }
        }

        $zip->close();

        return $zipPath;
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
