<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\File\Pages;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class FileDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Original Name', 'original_name'),
            Text::make('Stored Name', 'stored_name'),
            Text::make('Path', 'path'),
            Text::make('MIME Type', 'mime_type'),
            Text::make('Extension', 'extension'),
            Preview::make('Size', 'size', fn($item) => $item->formatted_size),
            Text::make('Hash', 'hash'),
            Preview::make('Download Link', 'download_token', fn($item) => $item->download_url),
            Textarea::make('Description', 'description'),
            Text::make('Uploader IP', 'uploader_ip'),
            Text::make('Uploader Email', 'uploader_email'),
            Text::make('Recipient Email', 'recipient_email'),
            Number::make('Download Count', 'download_count'),
            Date::make('Expires At', 'expires_at')->format('d.m.Y H:i'),
            Date::make('Created At', 'created_at')->format('d.m.Y H:i'),
            Date::make('Updated At', 'updated_at')->format('d.m.Y H:i'),
        ];
    }

    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

        $buttons->add(
            ActionButton::make('Download File', fn($file) => route('admin.file.download', $file->getKey()))
                ->icon('arrow-down-tray')
                ->blank()
                ->showInLine()
        );

        $buttons->add(
            ActionButton::make('Open Public Page', fn($file) => route('file.show', $file->download_token))
                ->icon('link')
                ->blank()
                ->showInLine()
        );

        return $buttons;
    }
}
