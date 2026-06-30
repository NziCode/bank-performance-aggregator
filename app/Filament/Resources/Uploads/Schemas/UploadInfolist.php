<?php

namespace App\Filament\Resources\Uploads\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UploadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات فایل')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('original_filename')
                            ->label('نام اصلی فایل'),
                        TextEntry::make('branch.name')
                            ->label('شعبه')
                            ->placeholder('-'),
                        TextEntry::make('period')
                            ->label('دوره گزارش')
                            ->jalaliDate()
                            ->placeholder('-'),
                        TextEntry::make('status')
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
                            }),
                        TextEntry::make('rows_processed')
                            ->label('ردیف‌های موفق')
                            ->numeric()
                            ->color('success')
                            ->weight('bold'),
                        TextEntry::make('rows_rejected')
                            ->label('ردیف‌های رد شده')
                            ->numeric()
                            ->color('danger')
                            ->weight('bold'),
                        TextEntry::make('uploader.first_name')
                            ->label('آپلودکننده')
                            ->formatStateUsing(fn($record) => $record->uploader?->full_name)
                            ->placeholder('-'),
                        TextEntry::make('processed_at')
                            ->label('زمان پردازش')
                            ->jalaliDateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('زمان آپلود')
                            ->jalaliDateTime(),
                    ]),

                Section::make('دلیل شکست کلی فایل')
                    ->visible(fn($record) => filled($record->failure_reason))
                    ->schema([
                        TextEntry::make('failure_reason')
                            ->label('')
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),

                Section::make('جزئیات خطاهای ردیف‌ها')
                    ->visible(fn($record) => ! empty($record->errors))
                    ->schema([
                        RepeatableEntry::make('errors')
                            ->label('')
                            ->schema([
                                TextEntry::make('sheet')
                                    ->label('شیت'),
                                TextEntry::make('row')
                                    ->label('شماره ردیف')
                                    ->numeric(),
                                TextEntry::make('reason')
                                    ->label('دلیل رد')
                                    ->color('danger')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
