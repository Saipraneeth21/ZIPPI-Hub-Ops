<?php

namespace App\Services\Rental;

use App\Enums\KycStatus;
use App\Integrations\Contracts\KycProvider;
use App\Models\Rental\KycDocument;
use App\Models\Rental\KycReview;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** KYC lifecycle: upload -> submit -> (auto/manual) approve|reject -> gate. */
class KycService
{
    public function __construct(
        private readonly KycProvider $provider,
        private readonly NotificationService $notifications,
    ) {}

    public function uploadDocument(User $user, string $type, string $filePath, ?string $numberMasked = null, ?string $backPath = null, ?string $documentNumber = null, array $meta = []): KycDocument
    {
        if ($user->isKycApproved()) {
            throw new RuntimeException('KYC already approved.');
        }
        // Pass the raw number + DOB to the provider for number-based verification
        // (e.g. Quickkyc driving-licence). Only the masked number is persisted.
        $result = $this->provider->submit($user->id, $type, $filePath, array_filter([
            'document_number' => $documentNumber,
        ] + $meta));

        // Honor the provider's automated outcome; 'pending' routes to manual review.
        $status = match ($result['auto_result'] ?? 'pending') {
            'verified' => 'approved',
            'failed' => 'rejected',
            default => 'pending',
        };

        return KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $type,
            'document_number_masked' => $numberMasked,
            'file_path' => $filePath,
            'file_back_path' => $backPath,
            'provider_ref' => $result['reference'],
            'status' => $status,
            'verified_at' => $status === 'approved' ? now() : null,
        ]);
    }

    public function submit(User $user): UserProfile
    {
        $docs = $user->kycDocuments()->get();
        $types = $docs->pluck('document_type')->unique();
        foreach (['government_id', 'driving_license'] as $required) {
            if (! $types->contains($required)) {
                throw new RuntimeException("Missing required document: {$required}");
            }
        }
        $profile = $this->profile($user);
        if (in_array($profile->kyc_status, ['approved', 'under_review'], true)) {
            throw new RuntimeException('KYC already submitted or approved.');
        }

        // Auto-decision when the provider already verified/failed the required docs.
        $required = $docs->whereIn('document_type', ['government_id', 'driving_license']);
        if ($required->isNotEmpty() && $required->every(fn ($d) => $d->status === KycStatus::Approved)) {
            $this->approve($user); // adminId null => source 'auto'

            return $profile->refresh();
        }
        if ($required->contains(fn ($d) => $d->status === KycStatus::Rejected)) {
            $this->reject($user, 'Automated verification could not confirm one or more documents. Our team will review them shortly.');

            return $profile->refresh();
        }

        // Otherwise route to manual admin review (default / ambiguous).
        $profile->update(['kyc_status' => 'under_review']);
        $user->kycDocuments()->update(['status' => 'under_review']);

        return $profile;
    }

    public function approve(User $user, ?int $adminId = null): void
    {
        DB::transaction(function () use ($user, $adminId) {
            $this->profile($user)->update(['kyc_status' => 'approved']);
            $user->kycDocuments()->update(['status' => 'approved']);
            KycReview::create([
                'user_id' => $user->id, 'reviewed_by' => $adminId,
                'decision' => 'approved', 'source' => $adminId ? 'manual' : 'auto',
            ]);
        });
        $this->notifications->notify($user->id, 'kyc', 'KYC approved', 'You can now book rentals!', ['deep_link' => 'zippi://kyc']);
    }

    public function reject(User $user, string $reason, ?int $adminId = null, array $documentIds = []): void
    {
        DB::transaction(function () use ($user, $reason, $adminId, $documentIds) {
            $this->profile($user)->update(['kyc_status' => 'rejected']);
            $q = $user->kycDocuments();
            if ($documentIds) {
                $q->whereIn('id', $documentIds);
            }
            $q->update(['status' => 'rejected']);
            KycReview::create([
                'user_id' => $user->id, 'reviewed_by' => $adminId,
                'decision' => 'rejected', 'rejection_reason' => $reason,
                'source' => $adminId ? 'manual' : 'auto',
            ]);
        });
        $this->notifications->notify($user->id, 'kyc', 'KYC needs attention', $reason, ['deep_link' => 'zippi://kyc']);
    }

    private function profile(User $user): UserProfile
    {
        return UserProfile::firstOrCreate(['user_id' => $user->id], ['kyc_status' => 'none']);
    }
}
