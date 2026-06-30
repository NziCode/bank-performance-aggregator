<?php

namespace App\Filament\Resources\Uploads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UploadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')
                    ->label('نام فایل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label('دوره')
                    ->jalaliDate()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending'    => 'در انتظار',
                        'processing' => 'در حال پردازش',
                        'completed'  => 'تکمیل شده',
                        'failed'     => 'ناموفق',
                        default      => $state,
                    })
                    ->sortable(),
                TextColumn::make('rows_processed')
                    ->label('ردیف‌های موفق')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rows_rejected')
                    ->label('ردیف‌های رد شده')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('uploader.first_name')
                    ->label('آپلودکننده')
                    ->formatStateUsing(fn($record) => $record->uploader?->full_name),
                TextColumn::make('processed_at')
                    ->label('زمان پردازش')
                    ->jalaliDateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('زمان آپلود')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending'    => 'در انتظار',
                        'processing' => 'در حال پردازش',
                        'completed'  => 'تکمیل شده',
                        'failed'     => 'ناموفق',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
