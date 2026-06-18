<?php

namespace App\Filament\Resources\Users\Support;

use App\Models\Rental\AuditLog;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Request;

/**
 * Block / unblock a rider (Admin-Dashboard/01-Admin-Modules.md §3.2).
 * A reason is mandatory, the toggle is audited, and visibility is gated on the
 * `users.block` ability (super_admin / ops per the RBAC matrix).
 */
final class BlockToggleAction
{
    public static function make(): Action
    {
        return Action::make('toggleBlock')
            ->label(fn (User $record) => $record->isBlocked() ? 'Unblock' : 'Block')
            ->icon(fn (User $record) => $record->isBlocked() ? 'heroicon-m-lock-open' : 'heroicon-m-lock-closed')
            ->color(fn (User $record) => $record->isBlocked() ? 'success' : 'danger')
            ->visible(fn () => auth()->user()?->can('users.block') ?? false)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => ($record->isBlocked() ? 'Unblock' : 'Block') . ' rider')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Why is this rider being blocked / unblocked?'),
            ]))
            ->action(function (User $record, array $data): void {
                $profile = $record->profile ?? new UserProfile(['user_id' => $record->id]);
                $wasBlocked = (bool) $profile->is_blocked;

                $profile->user_id = $record->id;
                $profile->is_blocked = ! $wasBlocked;
                $profile->blocked_reason = $data['reason'];
                $profile->save();

                AuditLog::create([
                    'actor_id' => auth()->id(),
                    'actor_type' => 'admin',
                    'action' => $wasBlocked ? 'user.unblock' : 'user.block',
                    'entity_type' => 'user',
                    'entity_id' => $record->id,
                    'before' => ['is_blocked' => $wasBlocked],
                    'after' => ['is_blocked' => ! $wasBlocked, 'reason' => $data['reason']],
                    'ip_address' => Request::ip(),
                ]);

                Notification::make()
                    ->title('Rider ' . ($wasBlocked ? 'unblocked' : 'blocked'))
                    ->success()
                    ->send();
            });
    }
}
