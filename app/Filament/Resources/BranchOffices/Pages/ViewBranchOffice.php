<?php

namespace App\Filament\Resources\BranchOffices\Pages;

use App\Filament\Resources\BranchOffices\BranchOfficeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBranchOffice extends ViewRecord
{
    protected static string $resource = BranchOfficeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('ویرایش'),
        ];
    }
}
