<?php

namespace App\Filament\Resources\StaffUnits;

use App\Filament\Resources\StaffUnits\Pages\CreateStaffUnit;
use App\Filament\Resources\StaffUnits\Pages\EditStaffUnit;
use App\Filament\Resources\StaffUnits\Pages\ListStaffUnits;
use App\Filament\Resources\StaffUnits\Pages\ViewStaffUnit;
use App\Filament\Resources\StaffUnits\Schemas\StaffUnitForm;
use App\Filament\Resources\StaffUnits\Schemas\StaffUnitInfolist;
use App\Filament\Resources\StaffUnits\Tables\StaffUnitsTable;
use App\Models\StaffUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffUnitResource extends Resource
{
    protected static ?string $model = StaffUnit::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'واحدهای ستادی';
    protected static ?string $modelLabel = 'واحد ستادی';
    protected static ?string $pluralModelLabel = 'واحدهای ستادی';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'مدیریت';
    }

    public static function form(Schema $schema): Schema
    {
        return StaffUnitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaffUnitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffUnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStaffUnits::route('/'),
            'create' => CreateStaffUnit::route('/create'),
            'view'   => ViewStaffUnit::route('/{record}'),
            'edit'   => EditStaffUnit::route('/{record}/edit'),
        ];
    }
}
