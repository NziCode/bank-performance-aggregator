<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('کد شعبه')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('نام شعبه')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zone.name')
                    ->label('حوزه')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('grade')
                    ->label('درجه')
                    ->badge()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('آدرس')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('offices_count')
                    ->label('تعداد باجه')
                    ->counts('offices')
                    ->sortable(),
                TextColumn::make('employees_count')
                    ->label('تعداد کارمند')
                    ->counts('employees')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('zone_code')
                    ->label('حوزه')
                    ->relationship('zone', 'name'),
                SelectFilter::make('grade')
                    ->label('درجه')
                    ->options([
                        'ممتاز الف' => 'ممتاز الف',
                        'ممتاز ب'   => 'ممتاز ب',
                        'درجه 1'    => 'درجه 1',
                        'درجه 2'    => 'درجه 2',
                        'درجه 3'    => 'درجه 3',
                        'درجه 4'    => 'درجه 4',
                        'درجه 5'    => 'درجه 5',
                    ]),
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
            ->paginated([10, 25, 50]);
    }
}
