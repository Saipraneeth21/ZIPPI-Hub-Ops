<?php

namespace App\Integrations\Contracts;

interface KycProvider
{
    /**
     * Submit a document for verification.
     *
     * @param  array  $meta  Optional verification inputs the provider may need,
     *                       e.g. ['document_number' => '...', 'dob' => 'YYYY-MM-DD'].
     * @return array ['reference' => string, 'auto_result' => 'pending'|'verified'|'failed']
     */
    public function submit(int $userId, string $documentType, string $filePath, array $meta = []): array;
}
