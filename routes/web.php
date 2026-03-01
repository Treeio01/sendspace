<?php

use App\Http\Controllers\AdminFileController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileController::class, 'index']);

Route::post('/upload', [FileController::class, 'upload'])->name('file.upload');
Route::post('/dragupload', [FileController::class, 'dragUpload'])->name('file.dragupload');
Route::get('/progress', [FileController::class, 'progress'])->name('file.progress');

Route::get('/uploaded', [FileController::class, 'uploaded'])->name('file.uploaded');
Route::get('/file/{token}', [FileController::class, 'show'])->name('file.show');
Route::get('/file/{token}/download', [FileController::class, 'download'])->name('file.download');

Route::prefix('admin')->group(function () {
    Route::get('/file/{file}/download', [AdminFileController::class, 'download'])->name('admin.file.download');
    Route::post('/files/archive', [AdminFileController::class, 'downloadArchive'])->name('admin.files.archive');
    Route::get('/files/ip/{ip}', [AdminFileController::class, 'downloadByIp'])->name('admin.files.by-ip');
});
