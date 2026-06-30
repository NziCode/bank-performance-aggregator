<?php

namespace App\Filament\Resources\BranchOffices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchOfficesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام باجه')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('branch.name')
                    ->label('شعبه عامل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch_code')
                    ->label('کد شعبه عامل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('آدرس')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->filters([
                SelectFilter::make('branch_code')
                    ->label('شعبه عامل')
                    ->relationship('branch', 'name')
                    ->searchable(),
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
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
