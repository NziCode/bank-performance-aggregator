<?php

namespace App\Filament\Resources\Uploads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UploadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('original_filename')
                    ->required(),
                TextInput::make('stored_path')
                    ->required(),
                TextInput::make('branch_code')
                    ->numeric(),
                TextInput::make('period'),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('rows_processed')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('rows_rejected')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('errors'),
                Textarea::make('failure_reason')
                    ->columnSpanFull(),
                TextInput::make('uploaded_by'),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
