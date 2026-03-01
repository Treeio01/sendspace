<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Download\Pages;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class DownloadDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Preview::make('File', 'file_id', fn($item) => $item->file?->original_name ?? '—'),
            Text::make('IP', 'ip'),
            Text::make('User Agent', 'user_agent'),
            Text::make('Referer', 'referer'),
            Text::make('Country', 'country'),
            Date::make('Downloaded At', 'created_at')->format('d.m.Y H:i:s'),
        ];
    }
}
