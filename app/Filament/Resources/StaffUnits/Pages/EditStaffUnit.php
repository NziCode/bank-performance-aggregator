<?php

namespace App\Filament\Resources\StaffUnits\Pages;

use App\Filament\Resources\StaffUnits\StaffUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffUnit extends EditRecord
{
    protected static string $resource = StaffUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
            DeleteAction::make()->label('حذف'),
        ];
    }
}
