<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات شعبه')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('کد شعبه')
                            ->weight('bold'),
                        TextEntry::make('name')
                            ->label('نام شعبه')
                            ->weight('bold'),
                        TextEntry::make('grade')
                            ->label('درجه شعبه')
                            ->badge()
                            ->placeholder('ثبت نشده'),
                        TextEntry::make('zone.name')
                            ->label('حوزه')
                            ->badge()
                            ->color('info')
                            ->placeholder('بدون حوزه (ممتاز)'),
                        TextEntry::make('offices_count')
                            ->label('تعداد باجه')
                            ->state(fn($record) => $record->offices()->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('employees_count')
                            ->label('تعداد کارمندان شعبه')
                            ->state(fn($record) => $record->employees()->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('address')
                            ->label('آدرس')
                            ->placeholder('ثبت نشده')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('تاریخ ایجاد')
                            ->jalaliDateTime(),
                        TextEntry::make('updated_at')
                            ->label('آخرین بروزرسانی')
                            ->jalaliDateTime(),
                    ]),
            ]);
    }
}
