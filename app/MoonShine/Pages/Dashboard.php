<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Helpers\FormatHelper;
use App\Models\Download;
use App\Models\File;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class Dashboard extends Page
{
    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    public function getTitle(): string
    {
        return 'SendSpace Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            $this->metricsGrid(),
            $this->tablesGrid(),
            $this->downloadsGrid(),
            $this->topUploadersGrid(),
        ];
    }

    private function metricsGrid(): Grid
    {
        return Grid::make([
            Column::make([
                ValueMetric::make('Total Files')
                    ->value(File::count())
                    ->icon('document'),
            ])->columnSpan(3),

            Column::make([
                ValueMetric::make('Total Size')
                    ->value(FormatHelper::bytes(File::sum('size')))
                    ->icon('server'),
            ])->columnSpan(3),

            Column::make([
                ValueMetric::make('Total Downloads')
                    ->value((int) File::sum('download_count'))
                    ->icon('arrow-down-tray'),
            ])->columnSpan(3),

            Column::make([
                ValueMetric::make('Unique IPs')
                    ->value(File::distinct('uploader_ip')->count('uploader_ip'))
                    ->icon('globe-alt'),
            ])->columnSpan(3),
        ]);
    }

    private function tablesGrid(): Grid
    {
        return Grid::make([
            Column::make([
                Heading::make('Recent Uploads'),
                TableBuilder::make(
                    fields: [
                        ID::make(),
                        Text::make('File', 'original_name'),
                        Preview::make('Size', 'size', fn($item) => FormatHelper::bytes($item->size)),
                        Text::make('IP', 'uploader_ip'),
                        Date::make('Date', 'created_at')->format('d.m.Y H:i'),
                    ],
                    items: File::orderByDesc('created_at')->limit(10)->get(),
                ),
            ])->columnSpan(6),

            Column::make([
                Heading::make('Top Downloaded'),
                TableBuilder::make(
                    fields: [
                        ID::make(),
                        Text::make('File', 'original_name'),
                        Number::make('Downloads', 'download_count'),
                        Text::make('IP', 'uploader_ip'),
                    ],
                    items: File::orderByDesc('download_count')->limit(10)->get(),
                ),
            ])->columnSpan(6),
        ]);
    }

    private function downloadsGrid(): Grid
    {
        $recentDownloads = Download::with('file')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return Grid::make([
            Column::make([
                Heading::make('Recent Downloads'),
                TableBuilder::make(
                    fields: [
                        ID::make(),
                        Preview::make('File', 'file_id', fn($item) => $item->file?->original_name ?? '—'),
                        Text::make('IP', 'ip'),
                        Text::make('User Agent', 'user_agent'),
                        Date::make('When', 'created_at')->format('d.m.Y H:i:s'),
                    ],
                    items: $recentDownloads,
                ),
            ])->columnSpan(12),
        ]);
    }

    private function topUploadersGrid(): Grid
    {
        $topIps = File::selectRaw('uploader_ip, count(*) as file_count, sum(size) as total_size')
            ->groupBy('uploader_ip')
            ->orderByDesc('file_count')
            ->limit(10)
            ->get();

        return Grid::make([
            Column::make([
                Heading::make('Top Uploaders by IP'),
                TableBuilder::make(
                    fields: [
                        Text::make('IP', 'uploader_ip'),
                        Number::make('Files', 'file_count'),
                        Preview::make('Total Size', 'total_size', fn($item) => FormatHelper::bytes($item->total_size)),
                    ],
                    items: $topIps,
                ),
            ])->columnSpan(12),
        ]);
    }
}
