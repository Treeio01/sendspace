<?php

declare(strict_types=1);

namespace App\Helpers;

class FormatHelper
{
    private const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public static function bytes(int|float|string|null $bytes): string
    {
        $bytes = (float) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $i = 0;
        while ($bytes >= 1024 && $i < count(self::UNITS) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . self::UNITS[$i];
    }
}
