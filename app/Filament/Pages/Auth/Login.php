<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class Login extends \Filament\Auth\Pages\Login
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('personnel_code')
                    ->label('کد پرسنلی')
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'personnel_code' => $data['personnel_code'],
            'password'       => $data['password'],
        ];
    }
}
