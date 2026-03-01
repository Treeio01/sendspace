<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\FormatHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            $file->download_token ??= Str::random(32);
            $file->hash ??= Str::random(32);
        });
    }

    public function getFormattedSizeAttribute(): string
    {
        return FormatHelper::bytes($this->size);
    }

    public function getDownloadUrlAttribute(): string
    {
        return url('/file/' . $this->download_token);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
