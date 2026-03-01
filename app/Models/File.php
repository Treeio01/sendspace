<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'extension',
        'size',
        'hash',
        'download_token',
        'description',
        'password',
        'uploader_ip',
        'uploader_email',
        'recipient_email',
        'download_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'download_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (File $file) {
            if (empty($file->download_token)) {
                $file->download_token = Str::random(32);
            }
            if (empty($file->hash)) {
                $file->hash = Str::random(32);
            }
        });
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDownloadUrlAttribute(): string
    {
        return url('/file/' . $this->download_token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
