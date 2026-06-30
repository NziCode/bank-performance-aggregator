<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Branch;
use App\Models\BranchOffice;
use App\Models\StaffUnit;
use App\Models\Zone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                Section::make('اطلاعات هویتی')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('personnel_code')
                            ->label('کد پرسنلی')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),
                        TextInput::make('national_code')
                            ->label('کد ملی')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10),
                        TextInput::make('first_name')
                            ->label('نام')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('last_name')
                            ->label('نام خانوادگی')
                            ->required()
                            ->maxLength(50),
                        Select::make('gender')
                            ->label('جنسیت')
                            ->options(['آقا' => 'آقا', 'خانم' => 'خانم']),
                        Select::make('education')
                            ->label('مدرک تحصیلی')
                            ->options([
                                'زیر دیپلم'  => 'زیر دیپلم',
                                'دیپلم'      => 'دیپلم',
                                'فوق دیپلم'  => 'فوق دیپلم',
                                'لیسانس'     => 'لیسانس',
                                'فوق لیسانس' => 'فوق لیسانس',
                                'دکترا'      => 'دکترا',
                            ]),
                    ]),

                Section::make('اطلاعات شغلی و تماس')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('position')
                            ->label('سمت')
                            ->maxLength(100),
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->tel()
                            ->maxLength(11),
                        TextInput::make('password')
                            ->label('رمز عبور')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation) => $operation === 'create')
                            ->dehydrated(fn($state) => filled($state))
                            ->helperText('در زمان ویرایش، خالی بگذارید تا رمز قبلی تغییر نکند'),
                    ]),

                Section::make('محل خدمت')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('workplace_type')
                            ->label('نوع محل خدمت')
                            ->options([
                                'branch'        => 'شعبه',
                                'branch_office' => 'باجه',
                                'zone'          => 'حوزه',
                                'staff'         => 'ستاد',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('branch_code', null) ?:
                                $set('branch_office_id', null) ?:
                                $set('zone_code', null) ?:
                                $set('staff_unit_code', null)),

                        Select::make('branch_code')
                            ->label('شعبه')
                            ->options(Branch::orderBy('name')->pluck('name', 'code'))
                            ->searchable()
                            ->visible(fn($get) => $get('workplace_type') === 'branch')
                            ->required(fn($get) => $get('workplace_type') === 'branch'),

                        Select::make('branch_office_id')
                            ->label('باجه')
                            ->options(BranchOffice::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn($get) => $get('workplace_type') === 'branch_office')
                            ->required(fn($get) => $get('workplace_type') === 'branch_office'),

                        Select::make('zone_code')
                            ->label('حوزه')
                            ->options(Zone::pluck('name', 'code'))
                            ->searchable()
                            ->visible(fn($get) => $get('workplace_type') === 'zone')
                            ->required(fn($get) => $get('workplace_type') === 'zone'),

                        Select::make('staff_unit_code')
                            ->label('واحد ستادی')
                            ->options(StaffUnit::pluck('name', 'code'))
                            ->searchable()
                            ->visible(fn($get) => $get('workplace_type') === 'staff')
                            ->required(fn($get) => $get('workplace_type') === 'staff'),
                    ]),
            ]);
    }
}
