<?php

namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\KycReview;
use App\Services\Rental\KycService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly KycService $kyc) {}

    public function show(Request $r)
    {
        $u = $r->user()->load('profile', 'kycDocuments');
        $latestReview = KycReview::where('user_id', $u->id)->latest()->first();

        return $this->ok([
            'status' => $u->profile?->kyc_status ?? 'none',
            'rejection_reason' => $latestReview?->decision === 'rejected' ? $latestReview->rejection_reason : null,
            'documents' => $u->kycDocuments->map(fn ($d) => [
                'id' => $d->id, 'type' => $d->document_type, 'status' => $d->status,
            ]),
            'can_resubmit' => ($u->profile?->kyc_status ?? 'none') === 'rejected',
        ]);
    }

    public function upload(Request $r)
    {
        $data = $r->validate([
            'document_type' => ['required', 'in:government_id,driving_license,selfie'],
            'document_number' => ['required_unless:document_type,selfie', 'nullable', 'string', 'max:40'],
            // Date of birth — required by Quickkyc for driving-licence verification.
            'dob' => ['nullable', 'date_format:Y-m-d'],
            // In real use this is a multipart file; we accept a path/string for API completeness.
            'file_path' => ['required', 'string'],
            'file_back_path' => ['nullable', 'string'],
        ]);
        try {
            $doc = $this->kyc->uploadDocument(
                $r->user(), $data['document_type'], $data['file_path'],
                isset($data['document_number']) ? $this->mask($data['document_number']) : null,
                $data['file_back_path'] ?? null,
                $data['document_number'] ?? null,
                array_filter(['dob' => $data['dob'] ?? null]),
            );
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 409);
        }

        return $this->created(['document_id' => $doc->id, 'type' => $doc->document_type, 'status' => $doc->status], 'Document uploaded');
    }

    public function submit(Request $r)
    {
        try {
            $profile = $this->kyc->submit($r->user());
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }

        return $this->ok(['status' => $profile->kyc_status], 'KYC submitted for review');
    }

    private function mask(string $n): string
    {
        return str_repeat('X', max(0, strlen($n) - 4)).substr($n, -4);
    }
}
