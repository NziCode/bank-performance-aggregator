<?php

namespace App\Filament\Resources\Uploads\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class UploadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('اطلاعات فایل')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('original_filename')
                            ->label('نام اصلی فایل')
                            ->icon('heroicon-o-paper-clip')
                            ->weight('bold'),
                        TextEntry::make('period')
                            ->label('دوره گزارش')
                            ->jalaliDate()
                            ->icon('heroicon-o-calendar')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('وضعیت')
                            ->badge()
                            ->size('lg')
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
                            }),

                        TextEntry::make('rows_processed')
                            ->label('ردیف‌های موفق')
                            ->numeric()
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('success')
                            ->color('success')
                            ->weight('bold'),
                        TextEntry::make('rows_rejected')
                            ->label('ردیف‌های رد شده')
                            ->numeric()
                            ->icon('heroicon-o-x-circle')
                            ->iconColor('danger')
                            ->color('danger')
                            ->weight('bold'),
                        TextEntry::make('uploader.first_name')
                            ->label('آپلودکننده')
                            ->icon('heroicon-o-user')
                            ->formatStateUsing(fn($record) => $record->uploader?->full_name)
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('زمان آپلود')
                            ->jalaliDateTime()
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('processed_at')
                            ->label('زمان پردازش')
                            ->jalaliDateTime()
                            ->icon('heroicon-o-cog')
                            ->placeholder('-')
                            ->columnSpan(2),
                    ]),

                Section::make('دلیل شکست کلی فایل')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn($record) => filled($record->failure_reason))
                    ->schema([
                        TextEntry::make('failure_reason')
                            ->label('')
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),

                Tabs::make('نتایج پردازش')
                    ->columnSpanFull()
                    ->tabs([

                        Tab::make('ردیف‌های موفق')
                            ->icon('heroicon-o-check-circle')
                            ->badge(fn($record) => $record->rows_processed)
                            ->badgeColor('success')
                            ->schema([
                                RepeatableEntry::make('performances')
                                    ->label('')
                                    ->state(fn($record) => $record->performances()
                                        ->with(['employee', 'serviceType', 'validationStatus'])
                                        ->latest('id')
                                        ->get())
                                    ->schema([
                                        TextEntry::make('date')
                                            ->label('تاریخ')
                                            ->jalaliDate(),
                                        TextEntry::make('employee.full_name')
                                            ->label('همکار')
                                            ->formatStateUsing(fn($record) => $record->employee?->full_name),
                                        TextEntry::make('serviceType.name')
                                            ->label('نوع خدمت'),
                                        TextEntry::make('customer_name')
                                            ->label('نام مشتری'),
                                        TextEntry::make('customer_account')
                                            ->label('شماره حساب')
                                            ->copyable(),
                                        TextEntry::make('validationStatus.name')
                                            ->label('وضعیت بررسی')
                                            ->badge()
                                            ->color(fn($record) => match($record->validation_status_id) {
                                                1 => 'warning',
                                                2 => 'success',
                                                3 => 'danger',
                                                default => 'gray',
                                            }),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('ردیف‌های رد شده')
                            ->icon('heroicon-o-x-circle')
                            ->badge(fn($record) => $record->rows_rejected)
                            ->badgeColor('danger')
                            ->visible(fn($record) => ! empty($record->errors))
                            ->schema([
                                RepeatableEntry::make('errors')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('sheet')
                                            ->label('شیت')
                                            ->badge()
                                            ->color('gray'),
                                        TextEntry::make('row')
                                            ->label('شماره ردیف')
                                            ->numeric()
                                            ->weight('bold'),
                                        TextEntry::make('reason')
                                            ->label('دلیل رد')
                                            ->color('danger')
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
