<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\File;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Components\Table\TableRow;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;

class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
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
        $totalFiles = File::count();
        $totalSize = File::sum('size');
        $totalDownloads = (int) File::sum('download_count');
        $todayUploads = File::whereDate('created_at', today())->count();
        $uniqueIps = File::distinct('uploader_ip')->count('uploader_ip');

        $recentFiles = File::orderByDesc('created_at')->limit(10)->get();
        $topDownloaded = File::orderByDesc('download_count')->limit(10)->get();

        $topIps = File::selectRaw('uploader_ip, count(*) as file_count, sum(size) as total_size')
            ->groupBy('uploader_ip')
            ->orderByDesc('file_count')
            ->limit(10)
            ->get();

        return [
            Grid::make([
                Column::make([
                    ValueMetric::make('Total Files')
                        ->value($totalFiles)
                        ->icon('document'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Total Size')
                        ->value($this->formatBytes($totalSize))
                        ->icon('server'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Total Downloads')
                        ->value($totalDownloads)
                        ->icon('arrow-down-tray'),
                ])->columnSpan(3),

                Column::make([
                    ValueMetric::make('Unique IPs')
                        ->value($uniqueIps)
                        ->icon('globe-alt'),
                ])->columnSpan(3),
            ]),

            Grid::make([
                Column::make([
                    Heading::make('Recent Uploads'),
                    TableBuilder::make(
                        fields: [
                            ID::make(),
                            Text::make('File', 'original_name'),
                            Preview::make('Size', 'size', fn($item) => $this->formatBytes($item->size)),
                            Text::make('IP', 'uploader_ip'),
                            Date::make('Date', 'created_at')->format('d.m.Y H:i'),
                        ],
                        items: $recentFiles,
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
                        items: $topDownloaded,
                    ),
                ])->columnSpan(6),
            ]),

            Grid::make([
                Column::make([
                    Heading::make('Top Uploaders by IP'),
                    TableBuilder::make(
                        fields: [
                            Text::make('IP', 'uploader_ip'),
                            Number::make('Files', 'file_count'),
                            Preview::make('Total Size', 'total_size', fn($item) => $this->formatBytes($item->total_size)),
                        ],
                        items: $topIps,
                    ),
                ])->columnSpan(12),
            ]),
        ];
    }

    private function formatBytes(int|float|string|null $bytes): string
    {
        $bytes = (float) $bytes;
        if ($bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
