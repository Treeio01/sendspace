<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Download;

use App\Models\Download;
use App\MoonShine\Resources\Download\Pages\DownloadDetailPage;
use App\MoonShine\Resources\Download\Pages\DownloadIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\SortDirection;

#[Icon('arrow-down-tray')]
class DownloadResource extends ModelResource
{
    protected string $model = Download::class;

    protected string $title = 'Downloads';

    protected string $column = 'id';

    protected int $itemsPerPage = 50;

    protected string $sortColumn = 'created_at';

    protected SortDirection $sortDirection = SortDirection::DESC;

    protected bool $isDetailInModal = true;

    public function getActiveActions(): array
    {
        return ['view', 'delete', 'massDelete'];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            DownloadIndexPage::class,
            DownloadDetailPage::class,
        ];
    }
}
