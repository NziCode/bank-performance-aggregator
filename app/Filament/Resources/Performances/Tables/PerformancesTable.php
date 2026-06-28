<?php

namespace App\Filament\Resources\Performances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\RejectionReason;
use App\Models\ValidationStatus;
use Illuminate\Database\Eloquent\Collection;

class PerformancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('تاریخ')
                    ->jalaliDate()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('شعبه')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.first_name')
                    ->label('همکار')
                    ->formatStateUsing(fn($record) => $record->employee?->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('personnel_code')
                    ->label('کد پرسنلی')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serviceType.name')
                    ->label('نوع خدمت')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer_account')
                    ->label('شماره حساب مشتری')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label('نام مشتری')
                    ->searchable(),
                TextColumn::make('terminal_number')
                    ->label('شماره پایانه')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('colleague_account')
                    ->label('شماره حساب همکار')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('validationStatus.name')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn($record) => match($record->validation_status_id) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('rejectionReason.reason')
                    ->label('دلیل رد')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('validated_at')
                    ->label('تاریخ بررسی')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('validation_status_id')
                    ->label('وضعیت')
                    ->options(ValidationStatus::pluck('name', 'id')),
                SelectFilter::make('service_type_id')
                    ->label('نوع خدمت')
                    ->relationship('serviceType', 'name'),
                SelectFilter::make('branch_code')
                    ->label('شعبه')
                    ->relationship('branch', 'name'),
                Filter::make('date')
                    ->label('بازه تاریخ')
                    ->form([
                        DatePicker::make('from')->label('از تاریخ')->jalali(),
                        DatePicker::make('to')->label('تا تاریخ')->jalali(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->where('date', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->where('date', '<=', $data['to']));
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('approve')
                        ->label('تایید انتخاب‌شده‌ها')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->validation_status_id === 1) {
                                    $record->update([
                                        'validation_status_id' => 2,
                                        'validated_by'         => auth()->id(),
                                        'validated_at'         => now(),
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('reject')
                        ->label('رد انتخاب‌شده‌ها')
                        ->color('danger')
                        ->icon('heroicon-o-x-mark')
                        ->form([
                            \Filament\Forms\Components\Select::make('rejection_reason_id')
                                ->label('دلیل رد')
                                ->options(RejectionReason::pluck('reason', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                if ($record->validation_status_id === 1) {
                                    $record->update([
                                        'validation_status_id' => 3,
                                        'rejection_reason_id'  => $data['rejection_reason_id'],
                                        'validated_by'         => auth()->id(),
                                        'validated_at'         => now(),
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
