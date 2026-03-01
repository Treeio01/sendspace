<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\ZipArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFileController extends Controller
{
    public function __construct(
        private readonly ZipArchiveService $zipService,
    ) {}

    public function download(File $file): StreamedResponse
    {
        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function downloadArchive(Request $request): BinaryFileResponse
    {
        $files = File::whereIn('id', $request->input('ids', []))->get();
        abort_if($files->isEmpty(), 404, 'No files found.');

        return $this->zipService->createFromFiles(
            $files,
            'sendspace_files_' . date('Y-m-d_H-i') . '.zip'
        );
    }

    public function downloadByIp(string $ip): BinaryFileResponse
    {
        $files = File::where('uploader_ip', $ip)->get();
        abort_if($files->isEmpty(), 404, 'No files found for this IP.');

        $safeIp = str_replace([':', '.'], '_', $ip);

        return $this->zipService->createFromFiles(
            $files,
            "files_ip_{$safeIp}.zip"
        );
    }
}
