<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

/**
 * Account → Settings: the signed-in admin changes their own password.
 * Personal page — available to every authenticated admin regardless of role.
 */
class AccountSettings extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.account-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Account Settings';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Change password')
                    ->description('Choose a strong password you do not use elsewhere.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(guard: 'admin')
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Update password')
            ->icon('heroicon-m-key')
            ->action(function (): void {
                $data = $this->form->getState();

                auth()->user()->update(['password' => Hash::make($data['password'])]);

                $this->form->fill();

                Notification::make()->title('Password updated')->success()->send();
            });
    }
}
