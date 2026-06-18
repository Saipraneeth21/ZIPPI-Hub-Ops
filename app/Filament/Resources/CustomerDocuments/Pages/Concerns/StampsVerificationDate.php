<?php

namespace App\Filament\Resources\CustomerDocuments\Pages\Concerns;

use App\Enums\KycStatus;

/**
 * When a document is approved or rejected and no verification date was entered,
 * stamp it automatically. Clears the date if the document returns to pending.
 */
trait StampsVerificationDate
{
    protected function stampVerificationDate(array $data): array
    {
        $decided = in_array($data['status'] ?? null, [
            KycStatus::Approved->value,
            KycStatus::Rejected->value,
        ], true);

        if ($decided && empty($data['verified_at'])) {
            $data['verified_at'] = now();
        }

        if (! $decided) {
            $data['verified_at'] = null;
        }

        return $data;
    }
}
