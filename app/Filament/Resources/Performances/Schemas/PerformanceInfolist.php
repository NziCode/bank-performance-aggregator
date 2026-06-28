<?php

namespace App\Filament\Resources\Performances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PerformanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('branch_code')
                    ->numeric(),
                TextEntry::make('personnel_code'),
                TextEntry::make('serviceType.name')
                    ->label('Service type'),
                TextEntry::make('customer_account'),
                TextEntry::make('customer_name'),
                TextEntry::make('terminal_number')
                    ->placeholder('-'),
                TextEntry::make('colleague_account')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('upload_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('validationStatus.name')
                    ->label('Validation status'),
                TextEntry::make('rejectionReason.id')
                    ->label('Rejection reason')
                    ->placeholder('-'),
                TextEntry::make('validated_by')
                    ->placeholder('-'),
                TextEntry::make('validated_at')
                    ->dateTime()
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
