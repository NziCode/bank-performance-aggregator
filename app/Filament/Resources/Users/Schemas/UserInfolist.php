<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('اطلاعات هویتی')
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('نام کامل')
                            ->state(fn($record) => $record->full_name)
                            ->weight('bold'),
                        TextEntry::make('personnel_code')
                            ->label('کد پرسنلی')
                            ->copyable(),
                        TextEntry::make('national_code')
                            ->label('کد ملی')
                            ->copyable(),
                        TextEntry::make('gender')
                            ->label('جنسیت')
                            ->placeholder('-'),
                        TextEntry::make('education')
                            ->label('مدرک تحصیلی')
                            ->placeholder('-'),
                        TextEntry::make('mobile')
                            ->label('موبایل')
                            ->placeholder('-'),
                    ]),

                Section::make('اطلاعات شغلی')
                    ->icon('heroicon-o-briefcase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('position')
                            ->label('سمت')
                            ->placeholder('-'),
                        TextEntry::make('workplace_type')
                            ->label('نوع محل خدمت')
                            ->badge()
                            ->formatStateUsing(fn($state) => match($state) {
                                'branch'        => 'شعبه',
                                'branch_office' => 'باجه',
                                'zone'          => 'حوزه',
                                'staff'         => 'ستاد',
                                default         => '-',
                            })
                            ->color(fn($state) => match($state) {
                                'branch'        => 'info',
                                'branch_office' => 'success',
                                'zone'          => 'warning',
                                'staff'         => 'gray',
                                default         => 'gray',
                            }),
                        TextEntry::make('branch.name')
                            ->label('شعبه')
                            ->visible(fn($record) => $record->workplace_type === 'branch')
                            ->placeholder('-'),
                        TextEntry::make('branchOffice.name')
                            ->label('باجه')
                            ->visible(fn($record) => $record->workplace_type === 'branch_office')
                            ->placeholder('-'),
                        TextEntry::make('zone.name')
                            ->label('حوزه')
                            ->visible(fn($record) => $record->workplace_type === 'zone')
                            ->placeholder('-'),
                        TextEntry::make('staffUnit.name')
                            ->label('واحد ستادی')
                            ->visible(fn($record) => $record->workplace_type === 'staff')
                            ->placeholder('-'),
                    ]),

                Section::make('اطلاعات سیستمی')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
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
