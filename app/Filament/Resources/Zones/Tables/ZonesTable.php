<?php

namespace App\Filament\Resources\Zones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('کد حوزه')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('نام حوزه')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branches_count')
                    ->label('تعداد شعب')
                    ->counts('branches')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
