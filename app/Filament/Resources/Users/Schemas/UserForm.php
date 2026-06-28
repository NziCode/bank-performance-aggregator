<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('national_code')
                    ->required(),
                TextInput::make('position'),
                TextInput::make('mobile'),
                Select::make('education')
                    ->options([
            'زیر دیپلم' => 'زیردیپلم',
            'دیپلم' => 'دیپلم',
            'فوق دیپلم' => 'فوقدیپلم',
            'لیسانس' => 'لیسانس',
            'فوق لیسانس' => 'فوقلیسانس',
            'دکترا' => 'دکترا',
        ]),
                Select::make('gender')
                    ->options(['آقا' => 'آقا', 'خانم' => 'خانم']),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Select::make('workplace_type')
                    ->options(['branch' => 'Branch', 'zone' => 'Zone', 'branch_office' => 'Branch office', 'staff' => 'Staff']),
                TextInput::make('branch_code')
                    ->numeric(),
                TextInput::make('zone_code')
                    ->numeric(),
                Select::make('branch_office_id')
                    ->relationship('branchOffice', 'name'),
                TextInput::make('staff_unit_code')
                    ->numeric(),
            ]);
    }
}
