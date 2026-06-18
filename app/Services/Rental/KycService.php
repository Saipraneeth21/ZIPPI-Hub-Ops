<?php
namespace App\Services\Rental;

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

    public function uploadDocument(User $user, string $type, string $filePath, ?string $numberMasked = null, ?string $backPath = null): KycDocument
    {
        if ($user->isKycApproved()) {
            throw new RuntimeException('KYC already approved.');
        }
        $result = $this->provider->submit($user->id, $type, $filePath);

        return KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $type,
            'document_number_masked' => $numberMasked,
            'file_path' => $filePath,
            'file_back_path' => $backPath,
            'provider_ref' => $result['reference'],
            'status' => 'pending',
        ]);
    }

    public function submit(User $user): UserProfile
    {
        $types = $user->kycDocuments()->pluck('document_type')->unique();
        foreach (['government_id', 'driving_license'] as $required) {
            if (! $types->contains($required)) {
                throw new RuntimeException("Missing required document: {$required}");
            }
        }
        $profile = $this->profile($user);
        if (in_array($profile->kyc_status, ['approved', 'under_review'], true)) {
            throw new RuntimeException('KYC already submitted or approved.');
        }
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
