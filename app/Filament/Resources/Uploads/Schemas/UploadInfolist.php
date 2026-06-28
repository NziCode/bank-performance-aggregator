<?php

namespace App\Filament\Resources\Uploads\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UploadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('original_filename'),
                TextEntry::make('stored_path'),
                TextEntry::make('branch_code')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('period')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('rows_processed')
                    ->numeric(),
                TextEntry::make('rows_rejected')
                    ->numeric(),
                TextEntry::make('failure_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('uploaded_by')
                    ->placeholder('-'),
                TextEntry::make('processed_at')
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
