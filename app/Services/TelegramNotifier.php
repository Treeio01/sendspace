<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    private ?string $botToken;
    private ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('sendspace.telegram.bot_token');
        $this->chatId = config('sendspace.telegram.chat_id');
    }

    public function isEnabled(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    public function send(string $message): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed: ' . $e->getMessage());
        }
    }
}
