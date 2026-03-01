<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class AdminFileController extends Controller
{
    /**
     * Download a single file from admin panel.
     */
    public function download(File $file): StreamedResponse
    {
        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /**
     * Download selected files as a ZIP archive.
     */
    public function downloadArchive(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No files selected.');
        }

        $files = File::whereIn('id', $ids)->get();

        if ($files->isEmpty()) {
            return back()->with('error', 'No files found.');
        }

        $zipPath = storage_path('app/temp/archive_' . time() . '.zip');
        $tempDir = dirname($zipPath);

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create archive.');
        }

        foreach ($files as $file) {
            $fullPath = Storage::disk('local')->path($file->path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->original_name);
            }
        }

        $zip->close();

        return response()->download($zipPath, 'sendspace_files_' . date('Y-m-d_H-i') . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Download all files from a specific IP as archive.
     */
    public function downloadByIp(string $ip)
    {
        $files = File::where('uploader_ip', $ip)->get();

        if ($files->isEmpty()) {
            return back()->with('error', 'No files found for this IP.');
        }

        $zipPath = storage_path('app/temp/ip_' . str_replace([':', '.'], '_', $ip) . '_' . time() . '.zip');
        $tempDir = dirname($zipPath);

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create archive.');
        }

        foreach ($files as $file) {
            $fullPath = Storage::disk('local')->path($file->path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file->original_name);
            }
        }

        $zip->close();

        return response()->download($zipPath, 'files_ip_' . str_replace([':', '.'], '_', $ip) . '.zip')
            ->deleteFileAfterSend(true);
    }
}
