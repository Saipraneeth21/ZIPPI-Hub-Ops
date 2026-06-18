<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\Kyc\Pages\ListKycReviews;
use App\Filament\Resources\Kyc\Pages\ViewKycReview;
use App\Http\Controllers\Admin\KycDocumentController;
use App\Models\Rental\AdminUser;
use App\Models\Rental\KycDocument;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminKycModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(AdminRole $role): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function rider(string $kycStatus, string $name = 'Rider'): User
    {
        $user = User::create([
            'name' => $name,
            'mobile' => '9' . str_pad((string) random_int(0, 99999999), 9, '0', STR_PAD_LEFT),
            'password' => 'password',
        ]);
        UserProfile::create(['user_id' => $user->id, 'kyc_status' => $kycStatus]);
        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'driving_license',
            'document_number_masked' => 'DL-XXXX-1234',
            'file_path' => "kyc/{$user->id}/dl.jpg",
            'status' => 'pending',
        ]);

        return $user;
    }

    public function test_queue_shows_only_submitted_riders_and_defaults_to_pending(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $pending = $this->rider('pending', 'Pending Rider');
        $approved = $this->rider('approved', 'Approved Rider');
        $notSubmitted = $this->rider('none', 'No KYC Rider');

        Livewire::test(ListKycReviews::class)
            ->assertOk()
            // default status filter = pending
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved, $notSubmitted]);
    }

    public function test_reviewer_can_approve_kyc(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $rider = $this->rider('under_review');

        Livewire::test(ViewKycReview::class, ['record' => $rider->getKey()])
            ->callAction('approveKyc');

        $this->assertSame('approved', $rider->fresh()->profile->kyc_status);
        $this->assertDatabaseHas('rental_kyc_reviews', [
            'user_id' => $rider->id,
            'decision' => 'approved',
            'source' => 'manual',
        ]);
    }

    public function test_reviewer_can_reject_kyc_with_reason(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $rider = $this->rider('under_review');

        Livewire::test(ViewKycReview::class, ['record' => $rider->getKey()])
            ->callAction('rejectKyc', data: ['reason' => 'Blurry license photo']);

        $this->assertSame('rejected', $rider->fresh()->profile->kyc_status);
        $this->assertDatabaseHas('rental_kyc_reviews', [
            'user_id' => $rider->id,
            'decision' => 'rejected',
            'rejection_reason' => 'Blurry license photo',
        ]);
    }

    public function test_ops_cannot_access_kyc_queue(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(ListKycReviews::class)->assertForbidden();
    }

    public function test_document_route_rejects_unsigned_requests(): void
    {
        $reviewer = $this->admin(AdminRole::KycReviewer);
        $rider = $this->rider('pending');
        $doc = $rider->kycDocuments->first();

        // No signature -> blocked by the `signed` middleware.
        $this->actingAs($reviewer, 'admin')
            ->get(route('admin.kyc.document', $doc))
            ->assertForbidden();
    }

    public function test_document_route_streams_for_signed_admin_request(): void
    {
        Storage::fake('local');
        $reviewer = $this->admin(AdminRole::KycReviewer);
        $rider = $this->rider('pending');
        $doc = $rider->kycDocuments->first();
        Storage::disk('local')->put($doc->file_path, 'fake-image-bytes');

        $url = KycDocumentController::signedUrl($doc);

        $this->actingAs($reviewer, 'admin')
            ->get($url)
            ->assertOk();
    }
}
