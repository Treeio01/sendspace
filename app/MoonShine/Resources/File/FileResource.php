<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\File;

use App\Models\File;
use App\MoonShine\Resources\File\Pages\FileDetailPage;
use App\MoonShine\Resources\File\Pages\FileFormPage;
use App\MoonShine\Resources\File\Pages\FileIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\Icon;

#[Icon('document')]
class FileResource extends ModelResource
{
    protected string $model = File::class;

    protected string $title = 'Files';

    protected string $column = 'original_name';

    protected int $itemsPerPage = 25;

    protected bool $isCreateInModal = false;

    protected bool $isEditInModal = false;

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
            FileIndexPage::class,
            FileFormPage::class,
            FileDetailPage::class,
        ];
    }
}
