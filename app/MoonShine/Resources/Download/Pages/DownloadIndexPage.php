<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Download\Pages;

use App\Models\Download;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class DownloadIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Preview::make('File', 'file_id', fn($item) => $item->file?->original_name ?? '—'),
            Text::make('IP', 'ip')->sortable(),
            Text::make('User Agent', 'user_agent')
                ->sortable(),
            Text::make('Referer', 'referer'),
            Date::make('Downloaded At', 'created_at')->format('d.m.Y H:i:s')->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('IP', 'ip'),
            Text::make('User Agent', 'user_agent'),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [
            QueryTag::make('All', fn($q) => $q),
            QueryTag::make('Today', fn($q) => $q->whereDate('created_at', today())),
            QueryTag::make('This Week', fn($q) => $q->where('created_at', '>=', now()->startOfWeek())),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function metrics(): array
    {
        return [
            ValueMetric::make('Total Downloads')->value(Download::count()),
            ValueMetric::make('Today')->value(Download::whereDate('created_at', today())->count()),
            ValueMetric::make('Unique IPs')->value(Download::distinct('ip')->count('ip')),
        ];
    }
}
