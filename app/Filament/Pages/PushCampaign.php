<?php

namespace App\Filament\Pages;

use App\Enums\KycStatus;
use App\Models\Rental\DeviceToken;
use App\Models\User;
use App\Services\Rental\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Compose and send a push campaign to a rider audience (Admin-Dashboard §3.8).
 * Delegates to NotificationService (queued push provider). Gated on
 * `marketing.manage`.
 */
class PushCampaign extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.push-campaign';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;


    protected static ?string $navigationLabel = 'Push Campaign';

    protected static ?int $navigationSort = 15;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('marketing.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['audience' => 'all']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compose')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(120),
                        Textarea::make('body')->required()->maxLength(500),
                        Select::make('audience')
                            ->options([
                                'all' => 'All riders',
                                'kyc_approved' => 'KYC-approved riders',
                                'with_device' => 'Riders with a device token',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->helperText(fn () => $this->audienceCount() . ' riders match'),
                        TextInput::make('deep_link')
                            ->label('Deep link (optional)')
                            ->placeholder('zippi://offers'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function audienceQuery(?string $audience = null)
    {
        $audience ??= $this->data['audience'] ?? 'all';

        return match ($audience) {
            'kyc_approved' => User::whereHas('profile', fn ($q) => $q->where('kyc_status', KycStatus::Approved->value)),
            'with_device' => User::whereIn('id', DeviceToken::query()->select('user_id')),
            default => User::query(),
        };
    }

    protected function audienceCount(): int
    {
        return $this->audienceQuery()->count();
    }

    public function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send campaign')
            ->icon('heroicon-m-paper-airplane')
            ->requiresConfirmation()
            ->modalDescription(fn () => 'This will queue a push to ' . $this->audienceCount() . ' riders.')
            ->action(function (NotificationService $notifications): void {
                $data = $this->form->getState();

                $sent = 0;
                $this->audienceQuery($data['audience'])
                    ->select('id')
                    ->chunkById(500, function ($users) use ($notifications, $data, &$sent) {
                        foreach ($users as $user) {
                            $notifications->notify(
                                $user->id,
                                'marketing',
                                $data['title'],
                                $data['body'],
                                array_filter(['deep_link' => $data['deep_link'] ?? null]),
                                promotional: true,
                            );
                            $sent++;
                        }
                    });

                Notification::make()
                    ->title("Campaign queued to {$sent} riders")
                    ->success()
                    ->send();

                $this->form->fill(['audience' => 'all']);
            });
    }
}
