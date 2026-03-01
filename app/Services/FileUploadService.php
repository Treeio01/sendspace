<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadService
{
    public function store(UploadedFile $uploadedFile, array $metadata = []): File
    {
        $originalName = $metadata['original_name'] ?? $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = Str::uuid() . '.' . $extension;
        $path = $uploadedFile->storeAs($this->getUploadDirectory(), $storedName, 'local');

        return File::create([
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $extension,
            'size' => $uploadedFile->getSize(),
            'uploader_ip' => $metadata['ip'] ?? null,
            'uploader_email' => $metadata['uploader_email'] ?? null,
            'recipient_email' => $metadata['recipient_email'] ?? null,
            'description' => $metadata['description'] ?? null,
        ]);
    }

    public function maxSizeBytes(): int
    {
        return (int) config('sendspace.max_upload_size', 300 * 1024 * 1024);
    }

    public function isValidSize(UploadedFile $file): bool
    {
        return $file->getSize() <= $this->maxSizeBytes();
    }

    private function getUploadDirectory(): string
    {
        return 'uploads/' . now()->format('Y/m');
    }
}
