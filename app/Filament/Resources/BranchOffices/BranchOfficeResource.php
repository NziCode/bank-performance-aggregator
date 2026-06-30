<?php

namespace App\Filament\Resources\BranchOffices;

use App\Filament\Resources\BranchOffices\Pages\CreateBranchOffice;
use App\Filament\Resources\BranchOffices\Pages\EditBranchOffice;
use App\Filament\Resources\BranchOffices\Pages\ListBranchOffices;
use App\Filament\Resources\BranchOffices\Pages\ViewBranchOffice;
use App\Filament\Resources\BranchOffices\Schemas\BranchOfficeForm;
use App\Filament\Resources\BranchOffices\Schemas\BranchOfficeInfolist;
use App\Filament\Resources\BranchOffices\Tables\BranchOfficesTable;
use App\Models\BranchOffice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchOfficeResource extends Resource
{
    protected static ?string $model = BranchOffice::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'باجه‌ها';
    protected static ?string $modelLabel = 'باجه';
    protected static ?string $pluralModelLabel = 'باجه‌ها';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'مدیریت';
    }

    public static function form(Schema $schema): Schema
    {
        return BranchOfficeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchOfficeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchOfficesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBranchOffices::route('/'),
            'create' => CreateBranchOffice::route('/create'),
            'view'   => ViewBranchOffice::route('/{record}'),
            'edit'   => EditBranchOffice::route('/{record}/edit'),
        ];
    }
}
