<?php
namespace App\Integrations\Contracts;

interface KycProvider
{
    /** Submit a document for verification. Returns ['reference' => string, 'auto_result' => 'pending'|'verified'|'failed']. */
    public function submit(int $userId, string $documentType, string $filePath): array;
}
