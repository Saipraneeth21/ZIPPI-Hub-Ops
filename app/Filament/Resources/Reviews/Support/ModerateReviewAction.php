<?php

namespace App\Filament\Resources\Reviews\Support;

use App\Models\Rental\Review;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;

/**
 * Publish / hide a review (Admin-Dashboard §3.9). Records the moderating admin.
 * Gated on `reviews.manage`.
 */
final class ModerateReviewAction
{
    public static function make(): Action
    {
        return Action::make('moderate')
            ->label(fn (Review $record) => $record->status === 'published' ? 'Hide' : 'Publish')
            ->icon(fn (Review $record) => $record->status === 'published' ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
            ->color(fn (Review $record) => $record->status === 'published' ? 'danger' : 'success')
            ->visible(fn () => auth()->user()?->can('reviews.manage') ?? false)
            ->requiresConfirmation()
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Textarea::make('note')->label('Moderation note (optional)')->maxLength(255),
            ]))
            ->action(function (Review $record, array $data): void {
                $newStatus = $record->status === 'published' ? 'hidden' : 'published';
                $record->update([
                    'status' => $newStatus,
                    'moderated_by' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Review ' . ($newStatus === 'hidden' ? 'hidden' : 'published'))
                    ->success()
                    ->send();
            });
    }
}
