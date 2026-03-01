<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function index()
    {
        return view('index');
    }

    /**
     * Handle drag-and-drop XHR file upload.
     * Returns a hash that the frontend uses to track the file.
     */
    public function dragUpload(Request $request): JsonResponse
    {
        $uploadedFile = $request->file('fileField');

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        $maxSize = 300 * 1024 * 1024; // 300MB
        if ($uploadedFile->getSize() > $maxSize) {
            return response()->json(['error' => 'File too large'], 413);
        }

        $originalName = $request->header('X-File-Name', $uploadedFile->getClientOriginalName());
        $originalName = urldecode($originalName);
        $extension = $uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = Str::uuid() . '.' . $extension;
        $hash = Str::random(32);

        $path = $uploadedFile->storeAs('uploads/' . now()->format('Y/m'), $storedName, 'local');

        $file = File::create([
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $extension,
            'size' => $uploadedFile->getSize(),
            'hash' => $hash,
            'download_token' => Str::random(32),
            'uploader_ip' => $request->ip(),
            'uploader_email' => null,
            'recipient_email' => null,
        ]);

        return response()->json($file->hash);
    }

    /**
     * Handle the traditional form-based upload (multiple files).
     * Redirects to the download page after upload.
     */
    public function upload(Request $request)
    {
        $isDrag = $request->has('is_drag');
        $hashes = $request->input('hash', []);
        $names = $request->input('name', []);
        $descriptions = $request->input('description', []);
        $recipientEmail = $request->input('recpemail');
        $senderEmail = $request->input('ownemail');

        $fileIds = [];

        if ($isDrag) {
            foreach ($hashes as $i => $hash) {
                if (empty($hash)) continue;

                $file = File::where('hash', $hash)->first();
                if ($file) {
                    $file->update([
                        'description' => $descriptions[$i] ?? $descriptions[0] ?? null,
                        'uploader_email' => $senderEmail ?: null,
                        'recipient_email' => $recipientEmail ?: null,
                    ]);
                    $fileIds[] = $file->id;
                }
            }
        } else {
            $uploadedFiles = $request->file('upload_file', []);
            $description = $descriptions[0] ?? null;

            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile || !$uploadedFile->isValid()) continue;

                $maxSize = 300 * 1024 * 1024;
                if ($uploadedFile->getSize() > $maxSize) continue;

                $originalName = $uploadedFile->getClientOriginalName();
                $extension = $uploadedFile->getClientOriginalExtension();
                $storedName = Str::uuid() . '.' . $extension;

                $path = $uploadedFile->storeAs('uploads/' . now()->format('Y/m'), $storedName, 'local');

                $file = File::create([
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'path' => $path,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'extension' => $extension,
                    'size' => $uploadedFile->getSize(),
                    'description' => $description,
                    'uploader_ip' => $request->ip(),
                    'uploader_email' => $senderEmail ?: null,
                    'recipient_email' => $recipientEmail ?: null,
                ]);

                $fileIds[] = $file->id;
            }
        }

        if (empty($fileIds)) {
            return redirect('/')->with('error', 'No files were uploaded.');
        }

        $tokens = File::whereIn('id', $fileIds)->pluck('download_token');
        $tokenStr = $tokens->implode(',');

        return redirect('/uploaded?tokens=' . $tokenStr);
    }

    /**
     * Show the upload success / download page.
     */
    public function uploaded(Request $request)
    {
        $tokenStr = $request->query('tokens', '');
        $tokens = array_filter(explode(',', $tokenStr));

        if (empty($tokens)) {
            return redirect('/');
        }

        $files = File::whereIn('download_token', $tokens)->get();

        return view('uploaded', compact('files'));
    }

    /**
     * Show download page for a single file.
     */
    public function show(string $token)
    {
        $file = File::where('download_token', $token)->firstOrFail();

        if ($file->isExpired()) {
            abort(410, 'This file has expired.');
        }

        return view('download', compact('file'));
    }

    /**
     * Download the file.
     */
    public function download(string $token): StreamedResponse
    {
        $file = File::where('download_token', $token)->firstOrFail();

        if ($file->isExpired()) {
            abort(410, 'This file has expired.');
        }

        $file->increment('download_count');

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /**
     * Upload progress endpoint (returns JSON with progress info).
     */
    public function progress(Request $request): JsonResponse
    {
        return response()->json([
            'percent' => 100,
            'status' => 'complete',
        ]);
    }
}
