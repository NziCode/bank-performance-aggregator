<?php

namespace App\Filament\Resources\BranchOffices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchOfficeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات باجه')
                    ->icon('heroicon-o-building-storefront')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('نام باجه')
                            ->weight('bold')
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('branch.name')
                            ->label('شعبه عامل')
                            ->icon('heroicon-o-building-office-2')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('branch_code')
                            ->label('کد شعبه عامل'),
                        TextEntry::make('employees_count')
                            ->label('تعداد کارمندان')
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
