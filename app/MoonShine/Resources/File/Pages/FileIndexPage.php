<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\File\Pages;

use App\Helpers\FormatHelper;
use App\Models\File;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class FileIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('File Name', 'original_name')->sortable(),
            Preview::make('Size', 'size', fn($item) => $item->formatted_size),
            Text::make('Extension', 'extension')->sortable()->badge('purple'),
            Text::make('Uploader IP', 'uploader_ip')->sortable(),
            Number::make('Downloads', 'download_count')->sortable(),
            Date::make('Uploaded At', 'created_at')->format('d.m.Y H:i')->sortable(),
        ];
    }

    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

        $buttons->add(
            ActionButton::make('Download', fn($file) => route('admin.file.download', $file->getKey()))
                ->icon('arrow-down-tray')
                ->blank()
                ->showInLine()
        );

        $buttons->add(
            ActionButton::make('Public Link', fn($file) => route('file.show', $file->download_token))
                ->icon('link')
                ->blank()
                ->showInLine()
        );

        return $buttons;
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('File Name', 'original_name'),
            Text::make('Uploader IP', 'uploader_ip'),
            Text::make('Extension', 'extension'),
            Text::make('Uploader Email', 'uploader_email'),
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
            QueryTag::make('Large (>50MB)', fn($q) => $q->where('size', '>', 50 * 1024 * 1024)),
            QueryTag::make('Most Downloaded', fn($q) => $q->orderByDesc('download_count')),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function metrics(): array
    {
        return [
            ValueMetric::make('Total Files')->value(File::count()),
            ValueMetric::make('Total Size')->value(FormatHelper::bytes(File::sum('size'))),
            ValueMetric::make('Downloads Today')->value(File::whereDate('updated_at', today())->sum('download_count')),
            ValueMetric::make('Uploads Today')->value(File::whereDate('created_at', today())->count()),
        ];
    }
}
