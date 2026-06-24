<?php

namespace App\Integrations\Contracts;

interface PushProvider
{
    /** @param string[] $tokens */
    public function send(array $tokens, string $title, string $body, array $data = []): void;
}
