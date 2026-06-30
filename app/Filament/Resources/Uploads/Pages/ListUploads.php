<?php

namespace App\Filament\Resources\Uploads\Pages;

use App\Filament\Pages\UploadPerformance;
use App\Filament\Resources\Uploads\UploadResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListUploads extends ListRecords
{
    protected static string $resource = UploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadPerformance')
                ->label('آپلود فایل عملکرد')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn() => UploadPerformance::getUrl())
                ->color('primary'),
        ];
    }
}
