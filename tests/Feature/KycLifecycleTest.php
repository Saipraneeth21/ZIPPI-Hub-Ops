<?php
namespace Tests\Feature;

use App\Models\Rental\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class KycLifecycleTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_upload_submit_then_admin_approve_unlocks_booking(): void
    {
        $rider = User::factory()->create(); // kyc none
        Sanctum::actingAs($rider);

        // Upload required documents
        foreach (['government_id', 'driving_license'] as $type) {
            $this->postJson('/api/rental/v1/kyc/documents', [
                'document_type' => $type,
                'document_number' => 'ABCD1234567',
                'file_path' => "kyc/{$rider->id}/{$type}.jpg",
            ])->assertStatus(201);
        }

        $this->postJson('/api/rental/v1/kyc/submit')->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        // Admin (kyc_reviewer) approves
        $admin = AdminUser::create([
            'name' => 'Rev', 'email' => 'rev@zippi.in', 'password' => bcrypt('x'),
            'role' => 'kyc_reviewer', 'is_active' => true,
        ]);
        Sanctum::actingAs($admin);
        $this->postJson("/api/rental/v1/admin/kyc/{$rider->id}/approve")->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('rental_user_profiles', ['user_id' => $rider->id, 'kyc_status' => 'approved']);

        // Rider can now create a booking
        $bike = $this->aBike();
        Sanctum::actingAs($rider->fresh());
        $this->withHeaders(['Idempotency-Key' => 'kyc-book'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window()))->assertStatus(201);
    }

    public function test_submit_without_required_documents_fails(): void
    {
        $rider = User::factory()->create();
        Sanctum::actingAs($rider);
        $this->postJson('/api/rental/v1/kyc/submit')->assertStatus(422);
    }

    public function test_rider_cannot_access_admin_kyc_endpoints(): void
    {
        $rider = $this->approvedRider();
        Sanctum::actingAs($rider);
        // Rider has no admin role -> 403
        $this->getJson('/api/rental/v1/admin/kyc')->assertStatus(403);
    }
}
