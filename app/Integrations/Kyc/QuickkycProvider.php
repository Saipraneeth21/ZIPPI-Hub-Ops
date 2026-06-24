<?php

namespace App\Integrations\Kyc;

use App\Integrations\Contracts\KycProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Quickkyc KYC provider.
 *
 * Driving-license verification is a direct, synchronous number + DOB lookup, so
 * it returns a real 'verified'/'failed' result here. Aadhaar verification is an
 * OTP-based flow (the Aadhaar holder receives an OTP) which cannot complete in a
 * single submit() call, so government_id (Aadhaar) and selfie return 'pending'
 * and route to manual admin review until the Aadhaar-OTP sub-flow is wired.
 *
 * Endpoints, auth header, and the response success path are config-driven
 * (config('rental.kyc.quickkyc')) so they can be matched to your Quickkyc
 * account's API contract without code changes.
 */
class QuickkycProvider implements KycProvider
{
    /**
     * @param  array  $config  config('rental.kyc.quickkyc'):
     *                         api_key, base_url, dl_path, success_path, success_value
     */
    public function __construct(private readonly array $config) {}

    public function submit(int $userId, string $documentType, string $filePath, array $meta = []): array
    {
        $reference = 'qkyc_'.Str::random(12);

        // Only the driving licence can be auto-verified synchronously.
        if ($documentType !== 'driving_license') {
            return ['reference' => $reference, 'auto_result' => 'pending'];
        }

        $number = $meta['document_number'] ?? null;
        $dob = $meta['dob'] ?? null;
        if (! $number || ! $dob) {
            // Missing inputs — let a human review rather than hard-fail.
            return ['reference' => $reference, 'auto_result' => 'pending'];
        }

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->asJson()
                ->post($this->url($this->config['dl_path'] ?? '/verify/driving-license'), [
                    'id_number' => $number,
                    'dob' => $dob,
                ]);

            if ($response->failed()) {
                return ['reference' => $reference, 'auto_result' => 'pending'];
            }

            $ok = (string) Arr::get($response->json() ?? [], $this->config['success_path'] ?? 'status')
                === (string) ($this->config['success_value'] ?? 'success');

            return ['reference' => $reference, 'auto_result' => $ok ? 'verified' : 'failed'];
        } catch (Throwable $e) {
            // Never block onboarding on a provider outage — fall back to review.
            Log::warning('Quickkyc DL verification error: '.$e->getMessage());

            return ['reference' => $reference, 'auto_result' => 'pending'];
        }
    }

    /** @return array<string,string> */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.($this->config['api_key'] ?? '')];
    }

    private function url(string $path): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/').'/'.ltrim($path, '/');
    }
}
