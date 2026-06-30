<?php

namespace App\Filament\Resources\StaffUnits\Pages;

use App\Filament\Resources\StaffUnits\StaffUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffUnits extends ListRecords
{
    protected static string $resource = StaffUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ایجاد واحد جدید'),
        ];
    }
}
