<?php
namespace App\Services\Rental;

use App\Integrations\Contracts\PushProvider;
use App\Models\Rental\DeviceToken;
use App\Models\Rental\Notification;
use App\Models\Rental\NotificationPreference;

/** Persists in-app notifications and fans out push via the PushProvider. */
class NotificationService
{
    public function __construct(private readonly PushProvider $push) {}

    public function notify(int $userId, string $type, string $title, string $body, array $data = [], bool $promotional = false): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if ($this->shouldPush($userId, $promotional)) {
            $tokens = DeviceToken::where('user_id', $userId)->pluck('fcm_token')->all();
            if ($tokens) {
                $this->push->send($tokens, $title, $body, $data);
            }
        }

        return $notification;
    }

    private function shouldPush(int $userId, bool $promotional): bool
    {
        if (! $promotional) {
            return true; // transactional pushes are always sent
        }
        $pref = NotificationPreference::where('user_id', $userId)->first();
        return (bool) ($pref?->push_promotional ?? true);
    }
}
