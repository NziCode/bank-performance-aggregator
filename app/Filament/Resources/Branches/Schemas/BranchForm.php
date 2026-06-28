<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('zone_code')
                    ->numeric(),
                Select::make('grade')
                    ->options([
            'ممتاز الف' => 'ممتازالف',
            'ممتاز ب' => 'ممتازب',
            'درجه 1' => 'درجه1',
            'درجه 2' => 'درجه2',
            'درجه 3' => 'درجه3',
            'درجه 4' => 'درجه4',
            'درجه 5' => 'درجه5',
        ]),
                TextInput::make('address'),
            ]);
    }
}
