<?php

namespace App\Filament\Resources\StaffUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('کد واحد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('نام واحد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('employees_count')
                    ->label('تعداد کارمند')
                    ->counts('employees')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('code')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
