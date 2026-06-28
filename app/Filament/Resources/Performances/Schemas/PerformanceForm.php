<?php

namespace App\Filament\Resources\Performances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PerformanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required(),
                TextInput::make('branch_code')
                    ->required()
                    ->numeric(),
                TextInput::make('personnel_code')
                    ->required(),
                Select::make('service_type_id')
                    ->relationship('serviceType', 'name')
                    ->required(),
                TextInput::make('customer_account')
                    ->required(),
                TextInput::make('customer_name')
                    ->required(),
                TextInput::make('terminal_number'),
                TextInput::make('colleague_account'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('upload_id')
                    ->numeric(),
                Select::make('validation_status_id')
                    ->relationship('validationStatus', 'name')
                    ->required()
                    ->default(1),
                Select::make('rejection_reason_id')
                    ->relationship('rejectionReason', 'id'),
                TextInput::make('validated_by'),
                DateTimePicker::make('validated_at'),
            ]);
    }
}
