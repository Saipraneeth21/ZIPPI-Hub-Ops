<?php

namespace App\Filament\Hub\Pages;

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
 * Account → Profile: the signed-in hub staff edits their own name. Employee
 * code, role and hub are managed by admins, so they are shown read-only.
 */
class Profile extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.hub.pages.profile';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'My Profile';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->form->fill(auth('hub')->user()->only(['name']));
        $this->passwordForm->fill();
    }

    public function form(Schema $schema): Schema
    {
        $staff = auth('hub')->user()->loadMissing('hub');

        return $schema
            ->components([
                Section::make('Profile')
                    ->description('Your account on the Hub Operations app.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('employee_code')
                            ->label('Employee code')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => $staff->employee_code),
                        TextInput::make('role_label')
                            ->label('Role')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => str((string) $staff->role)->headline()),
                        TextInput::make('hub_label')
                            ->label('Hub')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => $staff->hub?->name ?? '—'),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save changes')
            ->icon('heroicon-m-check')
            ->action(function (): void {
                $data = $this->form->getState();

                auth('hub')->user()->update(['name' => $data['name']]);

                Notification::make()->title('Profile updated')->success()->send();
            });
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Change password')
                    ->description('Update the password you use to sign in to the Hub Ops app.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('new_password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('passwordData');
    }

    public function changePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label('Update password')
            ->icon('heroicon-m-key')
            ->action(function (): void {
                $data = $this->passwordForm->getState();
                $staff = auth('hub')->user();

                if (! Hash::check($data['current_password'], $staff->password)) {
                    Notification::make()->title('Current password is incorrect')->danger()->send();

                    return;
                }

                $staff->update(['password' => $data['new_password']]);

                $this->passwordForm->fill();

                Notification::make()->title('Password updated')->success()->send();
            });
    }
}
