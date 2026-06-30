<?php

namespace App\Filament\Resources\Performances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerformanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                Section::make('اطلاعات همکار')
                    ->icon('heroicon-o-user')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('نام همکار')
                            ->formatStateUsing(fn($record) => $record->employee?->full_name)
                            ->weight('bold')
                            ->icon('heroicon-o-identification'),
                        TextEntry::make('personnel_code')
                            ->label('کد پرسنلی')
                            ->copyable(),
                        TextEntry::make('workplace_label')
                            ->label('محل خدمت (در زمان ثبت)')
                            ->getStateUsing(fn($record) => $record->workplace_label)
                            ->badge()
                            ->color(fn($record) => match($record->workplace_type) {
                                'branch'        => 'info',
                                'branch_office' => 'success',
                                'zone'          => 'warning',
                                'staff'         => 'gray',
                                default         => 'gray',
                            }),
                    ]),

                Section::make('جزئیات خدمت')
                    ->icon('heroicon-o-banknotes')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('date')
                            ->label('تاریخ ثبت')
                            ->jalaliDate()
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('serviceType.name')
                            ->label('نوع خدمت')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('customer_name')
                            ->label('نام مشتری')
                            ->icon('heroicon-o-user-circle'),
                        TextEntry::make('customer_account')
                            ->label('شماره حساب مشتری')
                            ->copyable()
                            ->icon('heroicon-o-credit-card'),
                        TextEntry::make('terminal_number')
                            ->label('شماره پایانه')
                            ->visible(fn($record) => filled($record->terminal_number)),
                        TextEntry::make('colleague_account')
                            ->label('شماره حساب همکار')
                            ->visible(fn($record) => filled($record->colleague_account)),
                        TextEntry::make('notes')
                            ->label('توضیحات')
                            ->placeholder('بدون توضیحات'),
                    ]),

                Section::make('وضعیت اعتبارسنجی')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('validationStatus.name')
                            ->label('وضعیت')
                            ->badge()
                            ->size('lg')
                            ->color(fn($record) => match($record->validation_status_id) {
                                1 => 'warning',
                                2 => 'success',
                                3 => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('rejectionReason.reason')
                            ->label('دلیل رد')
                            ->color('danger')
                            ->visible(fn($record) => $record->validation_status_id === 3),
                        TextEntry::make('validator.full_name')
                            ->label('بررسی‌کننده')
                            ->formatStateUsing(fn($record) => $record->validator?->full_name)
                            ->placeholder('هنوز بررسی نشده'),
                        TextEntry::make('validated_at')
                            ->label('زمان بررسی')
                            ->jalaliDateTime()
                            ->placeholder('-'),
                        TextEntry::make('upload_id')
                            ->label('شناسه فایل آپلودی')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('زمان ثبت در سیستم')
                            ->jalaliDateTime(),
                    ]),
            ]);
    }
}
