<?php

namespace App\Integrations\Push;

use App\Integrations\Contracts\PushProvider;
use Illuminate\Support\Facades\Log;

/** Dev/test push provider — logs instead of calling FCM. */
class LogPushProvider implements PushProvider
{
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        Log::info('[PUSH] '.$title, ['tokens' => count($tokens), 'body' => $body, 'data' => $data]);
    }
}
