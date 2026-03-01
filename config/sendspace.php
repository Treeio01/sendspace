<?php

return [
    'max_upload_size' => env('SENDSPACE_MAX_UPLOAD_SIZE', 300 * 1024 * 1024),

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],
];
