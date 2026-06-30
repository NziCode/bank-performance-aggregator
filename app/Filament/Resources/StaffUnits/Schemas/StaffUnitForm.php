<?php

namespace App\Filament\Resources\StaffUnits\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StaffUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('code')
                    ->label('کد واحد')
                    ->required()
                    ->numeric()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('نام واحد')
                    ->required()
                    ->maxLength(100),
            ]);
    }
}
