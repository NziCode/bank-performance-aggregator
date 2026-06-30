<?php

namespace App\Filament\Resources\StaffUnits\Pages;

use App\Filament\Resources\StaffUnits\StaffUnitResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffUnit extends ViewRecord
{
    protected static string $resource = StaffUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('ویرایش'),
        ];
    }
}
