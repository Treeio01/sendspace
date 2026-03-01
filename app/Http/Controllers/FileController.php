<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Models\Download;
use App\Models\File;
use App\Services\FileUploadService;
use App\Helpers\FormatHelper;
use App\Services\TelegramNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
        private readonly TelegramNotifier $telegram,
    ) {}

    public function index()
    {
        return view('index');
    }

    public function dragUpload(Request $request): JsonResponse
    {
        $uploadedFile = $request->file('fileField');

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        if (!$this->uploadService->isValidSize($uploadedFile)) {
            return response()->json(['error' => 'File too large'], 413);
        }

        $originalName = urldecode(
            $request->header('X-File-Name', $uploadedFile->getClientOriginalName())
        );

        $file = $this->uploadService->store($uploadedFile, [
            'original_name' => $originalName,
            'ip' => $request->ip(),
        ]);

        return response()->json($file->hash);
    }

    public function upload(FileUploadRequest $request)
    {
        $validated = $request->validated();
        $hashes = $validated['hash'] ?? [];
        $descriptions = $validated['description'] ?? [];
        $recipientEmail = $validated['recpemail'] ?? null;
        $senderEmail = $validated['ownemail'] ?? null;

        $fileIds = $request->has('is_drag')
            ? $this->finalizeDragUploads($hashes, $descriptions, $senderEmail, $recipientEmail)
            : $this->processFormUploads($request, $descriptions, $senderEmail, $recipientEmail);

        if (empty($fileIds)) {
            return redirect('/')->with('error', 'No files were uploaded.');
        }

        $files = File::whereIn('id', $fileIds)->get();
        $this->notifyUpload($files, $request->ip());

        $tokens = $files->pluck('download_token')->implode(',');

        return redirect('/uploaded?tokens=' . $tokens);
    }

    public function uploaded(Request $request)
    {
        $tokens = array_filter(explode(',', $request->query('tokens', '')));

        if (empty($tokens)) {
            return redirect('/');
        }

        $files = File::whereIn('download_token', $tokens)->get();

        return view('uploaded', compact('files'));
    }

    public function show(string $token)
    {
        $file = File::where('download_token', $token)->firstOrFail();
        abort_if($file->isExpired(), 410, 'This file has expired.');

        return view('download', compact('file'));
    }

    public function download(string $token, Request $request): StreamedResponse
    {
        $file = File::where('download_token', $token)->firstOrFail();
        abort_if($file->isExpired(), 410, 'This file has expired.');

        $file->increment('download_count');

        Download::create([
            'file_id' => $file->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
        ]);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function progress(): JsonResponse
    {
        return response()->json(['percent' => 100, 'status' => 'complete']);
    }

    private function finalizeDragUploads(array $hashes, array $descriptions, ?string $senderEmail, ?string $recipientEmail): array
    {
        $fileIds = [];

        foreach ($hashes as $i => $hash) {
            if (empty($hash)) {
                continue;
            }

            $file = File::where('hash', $hash)->first();

            if ($file) {
                $file->update([
                    'description' => $descriptions[$i] ?? $descriptions[0] ?? null,
                    'uploader_email' => $senderEmail,
                    'recipient_email' => $recipientEmail,
                ]);
                $fileIds[] = $file->id;
            }
        }

        return $fileIds;
    }

    private function notifyUpload($files, ?string $ip): void
    {
        $count = $files->count();
        $totalSize = FormatHelper::bytes($files->sum('size'));
        $names = $files->pluck('original_name')->implode(', ');

        $this->telegram->send(
            "📁 <b>Новая загрузка</b>\n"
            . "Файлов: {$count} ({$totalSize})\n"
            . "IP: <code>{$ip}</code>\n"
            . "Файлы: {$names}"
        );
    }

    private function processFormUploads(Request $request, array $descriptions, ?string $senderEmail, ?string $recipientEmail): array
    {
        $fileIds = [];
        $uploadedFiles = $request->file('upload_file', []);
        $description = $descriptions[0] ?? null;

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile?->isValid() || !$this->uploadService->isValidSize($uploadedFile)) {
                continue;
            }

            $file = $this->uploadService->store($uploadedFile, [
                'ip' => $request->ip(),
                'uploader_email' => $senderEmail,
                'recipient_email' => $recipientEmail,
                'description' => $description,
            ]);

            $fileIds[] = $file->id;
        }

        return $fileIds;
    }
}
