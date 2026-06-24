<?php

namespace App\Filament\Hub\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Hub panel login. Hub staff have no email — they sign in with their
 * employee_code + password. We swap the email field for an employee-code field
 * and map it into the credentials the eloquent provider queries on.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('employee_code')
            ->label('Employee code')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'employee_code' => $data['employee_code'],
            'password' => $data['password'],
        ];
    }
}
