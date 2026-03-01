<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    protected $fillable = [
        'file_id',
        'ip',
        'user_agent',
        'referer',
        'country',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
