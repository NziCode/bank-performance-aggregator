<?php

namespace App\Filament\Resources\StaffUnits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffUnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات واحد ستادی')
                    ->icon('heroicon-o-building-library')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('کد واحد')
                            ->weight('bold'),
                        TextEntry::make('name')
                            ->label('نام واحد')
                            ->weight('bold'),
                        TextEntry::make('employees_count')
                            ->label('تعداد کارمندان')
                            ->state(fn($record) => $record->employees()->count())
                            ->badge()
                            ->color('success'),
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
