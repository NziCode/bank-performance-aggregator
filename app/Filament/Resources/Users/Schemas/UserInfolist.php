<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('personnel_code'),
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                TextEntry::make('national_code'),
                TextEntry::make('position')
                    ->placeholder('-'),
                TextEntry::make('mobile')
                    ->placeholder('-'),
                TextEntry::make('education')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('gender')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('workplace_type')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('branch_code')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('zone_code')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('branchOffice.name')
                    ->label('Branch office')
                    ->placeholder('-'),
                TextEntry::make('staff_unit_code')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
