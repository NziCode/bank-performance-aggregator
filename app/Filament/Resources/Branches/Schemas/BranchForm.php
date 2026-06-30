<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Zone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('code')
                    ->label('کد شعبه')
                    ->required()
                    ->numeric()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('نام شعبه')
                    ->required()
                    ->maxLength(100),
                Select::make('zone_code')
                    ->label('حوزه')
                    ->options(Zone::pluck('name', 'code'))
                    ->searchable()
                    ->placeholder('بدون حوزه (شعبه ممتاز)'),
                Select::make('grade')
                    ->label('درجه شعبه')
                    ->options([
                        'ممتاز الف' => 'ممتاز الف',
                        'ممتاز ب'   => 'ممتاز ب',
                        'درجه 1'    => 'درجه 1',
                        'درجه 2'    => 'درجه 2',
                        'درجه 3'    => 'درجه 3',
                        'درجه 4'    => 'درجه 4',
                        'درجه 5'    => 'درجه 5',
                    ]),
                Textarea::make('address')
                    ->label('آدرس')
                    ->rows(3)
                    ->maxLength(255),
            ]);
    }
}
