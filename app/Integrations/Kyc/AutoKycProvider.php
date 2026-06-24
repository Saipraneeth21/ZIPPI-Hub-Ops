<?php

namespace App\Integrations\Kyc;

use App\Integrations\Contracts\KycProvider;
use Illuminate\Support\Str;

/**
 * Dev KYC provider that returns 'pending' so submissions route to manual
 * admin review. A real Digio/Signzy adapter would return 'verified'/'failed'.
 */
class AutoKycProvider implements KycProvider
{
    public function submit(int $userId, string $documentType, string $filePath, array $meta = []): array
    {
        return ['reference' => 'kyc_'.Str::random(12), 'auto_result' => 'pending'];
    }
}
