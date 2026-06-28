<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('personnel_code')
                    ->label('کد پرسنلی')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('نام خانوادگی')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('سمت')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')
                    ->label('تحصیلات')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->label('جنسیت')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('workplace_type')
                    ->label('نوع محل خدمت')
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'branch'        => 'شعبه',
                        'zone'          => 'حوزه',
                        'branch_office' => 'باجه',
                        'staff'         => 'ستاد',
                        default         => $state,
                    })
                    ->color(fn($state) => match($state) {
                        'branch'        => 'info',
                        'zone'          => 'warning',
                        'branch_office' => 'success',
                        'staff'         => 'gray',
                        default         => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('شعبه')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('zone.name')
                    ->label('حوزه')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('staffUnit.name')
                    ->label('واحد ستادی')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workplace_type')
                    ->label('نوع محل خدمت')
                    ->options([
                        'branch'        => 'شعبه',
                        'zone'          => 'حوزه',
                        'branch_office' => 'باجه',
                        'staff'         => 'ستاد',
                    ]),
                SelectFilter::make('branch_code')
                    ->label('شعبه')
                    ->relationship('branch', 'name'),
                SelectFilter::make('zone_code')
                    ->label('حوزه')
                    ->relationship('zone', 'name'),
                SelectFilter::make('education')
                    ->label('تحصیلات')
                    ->options([
                        'زیر دیپلم'  => 'زیر دیپلم',
                        'دیپلم'      => 'دیپلم',
                        'فوق دیپلم'  => 'فوق دیپلم',
                        'لیسانس'     => 'لیسانس',
                        'فوق لیسانس' => 'فوق لیسانس',
                        'دکترا'      => 'دکترا',
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
            ->defaultSort('last_name')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
