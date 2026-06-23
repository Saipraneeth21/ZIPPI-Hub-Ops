<?php

namespace App\Filament\Resources\Users\Support;

use App\Enums\AdminRole;
use App\Models\Rental\AdminUser;
use App\Models\Rental\AuditLog;
use App\Models\Rental\UserProfile;
use App\Models\User;
use App\Support\Rental\AdminAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Request;

/**
 * Red-flag (or clear the flag on) a rider, for use when repeated issues are
 * found with their documents / KYC. A reason is required when raising the flag,
 * and the toggle is audited.
 *
 * Visibility: full-access super admins (Super Admin / Admin / Developer /
 * Manager) and Supervisors only.
 */
final class RedFlagToggleAction
{
    /** Only full-access admins and supervisors may raise/clear red flags. */
    public static function allowed(): bool
    {
        $user = auth()->user();

        if (! $user instanceof AdminUser) {
            return false;
        }

        return AdminAccess::hasFullAccess($user) || $user->role === AdminRole::Supervisor;
    }

    private static function isFlagged(User $record): bool
    {
        return (bool) ($record->profile?->is_red_flagged ?? false);
    }

    public static function make(): Action
    {
        return Action::make('redFlag')
            ->label(fn (User $record) => self::isFlagged($record) ? 'Remove flag' : 'Red flag')
            ->icon('heroicon-m-flag')
            ->color(fn (User $record) => self::isFlagged($record) ? 'success' : 'danger')
            ->visible(fn () => self::allowed())
            ->modalHeading(fn (User $record) => self::isFlagged($record) ? 'Remove red flag' : 'Red flag rider')
            ->modalDescription('Use this when repeated issues are found with this rider\'s documents.')
            ->schema(fn (Schema $schema, User $record): Schema => $schema->components(
                self::isFlagged($record)
                    ? []
                    : [
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Why is this rider being red flagged?'),
                    ]
            ))
            ->action(function (User $record, array $data): void {
                $profile = $record->profile ?? new UserProfile(['user_id' => $record->id]);
                $wasFlagged = (bool) $profile->is_red_flagged;

                $profile->user_id = $record->id;
                $profile->is_red_flagged = ! $wasFlagged;
                $profile->red_flag_reason = $wasFlagged ? null : ($data['reason'] ?? null);
                $profile->red_flagged_at = $wasFlagged ? null : now();
                $profile->save();

                // Raising a flag revokes all of the rider's API tokens so any
                // active session is signed out immediately.
                if (! $wasFlagged) {
                    $record->tokens()->delete();
                }

                AuditLog::create([
                    'actor_id' => auth()->id(),
                    'actor_type' => 'admin',
                    'action' => $wasFlagged ? 'user.red_flag.clear' : 'user.red_flag',
                    'entity_type' => 'user',
                    'entity_id' => $record->id,
                    'before' => ['is_red_flagged' => $wasFlagged],
                    'after' => ['is_red_flagged' => ! $wasFlagged, 'reason' => $data['reason'] ?? null],
                    'ip_address' => Request::ip(),
                ]);

                Notification::make()
                    ->title($wasFlagged ? 'Red flag removed' : 'Rider red flagged')
                    ->success()
                    ->send();
            });
    }
}
