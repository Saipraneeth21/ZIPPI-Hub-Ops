<?php

namespace App\Filament\Resources\CustomerDocuments\Support;

use App\Enums\AdminRole;
use App\Models\Rental\AdminUser;
use App\Models\Rental\AuditLog;
use App\Models\Rental\KycDocument;
use App\Models\Rental\UserProfile;
use App\Support\Rental\AdminAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Request;

/**
 * Red-flag (or clear the flag on) the customer who owns this document, for use
 * when repeated KYC / document issues are found. A reason is required when
 * raising the flag, and the toggle is audited.
 *
 * Visibility: full-access super admins (Super Admin / Admin / Developer /
 * Manager) and Supervisors only.
 */
final class RedFlagUserAction
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

    public static function make(): Action
    {
        return Action::make('redFlagUser')
            ->label(fn (KycDocument $record) => $record->user?->profile?->is_red_flagged
                ? 'Remove red flag'
                : 'Red flag user')
            ->icon('heroicon-m-flag')
            ->color(fn (KycDocument $record) => $record->user?->profile?->is_red_flagged ? 'success' : 'danger')
            ->visible(fn () => self::allowed())
            ->modalHeading(fn (KycDocument $record) => $record->user?->profile?->is_red_flagged
                ? 'Remove red flag'
                : 'Red flag user')
            ->modalDescription('Use this when repeated issues are found with this customer\'s documents.')
            ->schema(fn (Schema $schema, KycDocument $record): Schema => $schema->components(
                $record->user?->profile?->is_red_flagged
                    ? []
                    : [
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Why is this user being red flagged?'),
                    ]
            ))
            ->action(function (KycDocument $record, array $data): void {
                $user = $record->user;

                if ($user === null) {
                    Notification::make()->title('No user attached to this document')->danger()->send();

                    return;
                }

                $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
                $wasFlagged = (bool) $profile->is_red_flagged;

                $profile->user_id = $user->id;
                $profile->is_red_flagged = ! $wasFlagged;
                $profile->red_flag_reason = $wasFlagged ? null : ($data['reason'] ?? null);
                $profile->red_flagged_at = $wasFlagged ? null : now();
                $profile->save();

                // Raising a flag revokes all of the rider's API tokens so any
                // active session is signed out immediately.
                if (! $wasFlagged) {
                    $user->tokens()->delete();
                }

                AuditLog::create([
                    'actor_id' => auth()->id(),
                    'actor_type' => 'admin',
                    'action' => $wasFlagged ? 'user.red_flag.clear' : 'user.red_flag',
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                    'before' => ['is_red_flagged' => $wasFlagged],
                    'after' => ['is_red_flagged' => ! $wasFlagged, 'reason' => $data['reason'] ?? null],
                    'ip_address' => Request::ip(),
                ]);

                Notification::make()
                    ->title($wasFlagged ? 'Red flag removed' : 'User red flagged')
                    ->success()
                    ->send();
            });
    }
}
