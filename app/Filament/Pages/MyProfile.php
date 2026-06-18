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
use UnitEnum;

/**
 * Account → My Profile: the signed-in admin edits their own name and email.
 * Personal page — available to every authenticated admin regardless of role.
 */
class MyProfile extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.my-profile';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->only(['name', 'email']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->description('Your name and email as they appear across the dashboard.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(160)
                            ->unique('admin_users', 'email', ignorable: auth()->user()),
                        TextInput::make('role_label')
                            ->label('Role')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => str(auth()->user()->role->value)->headline()),
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

                auth()->user()->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);

                Notification::make()->title('Profile updated')->success()->send();
            });
    }
}
