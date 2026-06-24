<?php

namespace Tests\Feature;

use App\Integrations\Contracts\KycProvider;
use App\Integrations\Kyc\AutoKycProvider;
use App\Integrations\Kyc\QuickkycProvider;
use App\Models\Rental\KycDocument;
use App\Models\User;
use App\Services\Rental\KycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuickkycTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): QuickkycProvider
    {
        return new QuickkycProvider([
            'api_key' => 'k', 'base_url' => 'https://api.quickkyc.test',
            'dl_path' => '/verify/driving-license',
            'success_path' => 'status', 'success_value' => 'success',
        ]);
    }

    public function test_dl_verification_returns_verified_on_success(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success'], 200)]);

        $result = $this->provider()->submit(1, 'driving_license', 'dl.jpg', [
            'document_number' => 'KA0120200012345', 'dob' => '1995-05-20',
        ]);

        $this->assertSame('verified', $result['auto_result']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer k')
            && $req['id_number'] === 'KA0120200012345' && $req['dob'] === '1995-05-20');
    }

    public function test_dl_verification_returns_failed_on_non_success(): void
    {
        Http::fake(['*' => Http::response(['status' => 'not_found'], 200)]);
        $result = $this->provider()->submit(1, 'driving_license', 'dl.jpg', [
            'document_number' => 'X', 'dob' => '1990-01-01',
        ]);
        $this->assertSame('failed', $result['auto_result']);
    }

    public function test_aadhaar_and_selfie_are_pending(): void
    {
        Http::fake();
        $this->assertSame('pending', $this->provider()->submit(1, 'government_id', 'a.jpg', ['document_number' => '1234'])['auto_result']);
        $this->assertSame('pending', $this->provider()->submit(1, 'selfie', 's.jpg')['auto_result']);
        Http::assertNothingSent();
    }

    public function test_dl_without_number_or_dob_is_pending(): void
    {
        Http::fake();
        $this->assertSame('pending', $this->provider()->submit(1, 'driving_license', 'dl.jpg')['auto_result']);
        Http::assertNothingSent();
    }

    public function test_provider_outage_falls_back_to_pending(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $result = $this->provider()->submit(1, 'driving_license', 'dl.jpg', [
            'document_number' => 'X', 'dob' => '1990-01-01',
        ]);
        $this->assertSame('pending', $result['auto_result']);
    }

    public function test_container_resolves_kyc_provider_from_config(): void
    {
        config()->set('rental.kyc.provider', 'quickkyc');
        config()->set('rental.kyc.quickkyc', ['api_key' => 'k', 'base_url' => 'https://x.test']);
        $this->assertInstanceOf(QuickkycProvider::class, app(KycProvider::class));

        config()->set('rental.kyc.provider', 'auto');
        app()->forgetInstance(KycProvider::class);
        $this->assertInstanceOf(AutoKycProvider::class, app(KycProvider::class));
    }

    public function test_submit_auto_approves_when_required_docs_verified(): void
    {
        $user = User::factory()->create();
        foreach (['government_id', 'driving_license'] as $type) {
            KycDocument::create([
                'user_id' => $user->id, 'document_type' => $type,
                'file_path' => "kyc/{$type}.jpg", 'provider_ref' => 'r', 'status' => 'approved',
            ]);
        }

        app(KycService::class)->submit($user);

        $this->assertSame('approved', $user->profile()->first()->kyc_status);
        $this->assertDatabaseHas('rental_kyc_reviews', [
            'user_id' => $user->id, 'decision' => 'approved', 'source' => 'auto',
        ]);
    }

    public function test_submit_auto_rejects_when_a_required_doc_failed(): void
    {
        $user = User::factory()->create();
        KycDocument::create([
            'user_id' => $user->id, 'document_type' => 'government_id',
            'file_path' => 'a.jpg', 'provider_ref' => 'r', 'status' => 'pending',
        ]);
        KycDocument::create([
            'user_id' => $user->id, 'document_type' => 'driving_license',
            'file_path' => 'dl.jpg', 'provider_ref' => 'r', 'status' => 'rejected',
        ]);

        app(KycService::class)->submit($user);

        $this->assertSame('rejected', $user->profile()->first()->kyc_status);
        $this->assertDatabaseHas('rental_kyc_reviews', [
            'user_id' => $user->id, 'decision' => 'rejected', 'source' => 'auto',
        ]);
    }
}
