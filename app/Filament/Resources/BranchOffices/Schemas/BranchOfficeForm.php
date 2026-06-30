<?php

namespace App\Filament\Resources\BranchOffices\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BranchOfficeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('نام باجه')
                    ->required()
                    ->maxLength(100),
                Select::make('branch_code')
                    ->label('شعبه عامل')
                    ->options(Branch::orderBy('name')->pluck('name', 'code'))
                    ->searchable()
                    ->required(),
                Textarea::make('address')
                    ->label('آدرس')
                    ->rows(3)
                    ->maxLength(255),
            ]);
    }
}
