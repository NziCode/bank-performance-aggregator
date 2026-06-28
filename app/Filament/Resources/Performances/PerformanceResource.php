<?php

namespace App\Filament\Resources\Performances;

use App\Filament\Resources\Performances\Pages\CreatePerformance;
use App\Filament\Resources\Performances\Pages\EditPerformance;
use App\Filament\Resources\Performances\Pages\ListPerformances;
use App\Filament\Resources\Performances\Pages\ViewPerformance;
use App\Filament\Resources\Performances\Schemas\PerformanceForm;
use App\Filament\Resources\Performances\Schemas\PerformanceInfolist;
use App\Filament\Resources\Performances\Tables\PerformancesTable;
use App\Models\Performance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PerformanceResource extends Resource
{
    protected static ?string $model = Performance::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'customer_name';
    protected static ?string $navigationLabel = 'عملکردها';
    protected static ?string $modelLabel = 'عملکرد';
    protected static ?string $pluralModelLabel = 'عملکردها';
    public static function getNavigationGroup(): ?string
    {
        return 'عملکرد';
    }
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PerformanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerformanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPerformances::route('/'),
            'create' => CreatePerformance::route('/create'),
            'view'   => ViewPerformance::route('/{record}'),
            'edit'   => EditPerformance::route('/{record}/edit'),
        ];
    }
}
