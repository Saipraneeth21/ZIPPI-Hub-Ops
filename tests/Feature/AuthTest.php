<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_then_verify_otp_issues_token(): void
    {
        $reg = $this->postJson('/api/rental/v1/auth/register', [
            'mobile' => '9876543210', 'name' => 'Karan Mehta',
        ])->assertStatus(200)->json();

        $this->assertTrue($reg['success']);
        $token = $reg['data']['otp_token'];
        $otp = $reg['data']['debug_otp'];

        $verify = $this->postJson('/api/rental/v1/auth/verify-otp', [
            'otp_token' => $token, 'otp' => $otp,
        ])->assertStatus(200)->json();

        $this->assertTrue($verify['data']['is_new_user']);
        $this->assertNotEmpty($verify['data']['token']);
        $this->assertDatabaseHas('users', ['mobile' => '9876543210']);
        $this->assertDatabaseHas('rental_user_profiles', ['kyc_status' => 'none']);
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        \App\Models\User::factory()->create(['mobile' => '9000000000']);
        $this->postJson('/api/rental/v1/auth/register', [
            'mobile' => '9000000000', 'name' => 'Dup',
        ])->assertStatus(409);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $reg = $this->postJson('/api/rental/v1/auth/register', [
            'mobile' => '9888888888', 'name' => 'Test User',
        ])->json();
        $this->postJson('/api/rental/v1/auth/verify-otp', [
            'otp_token' => $reg['data']['otp_token'], 'otp' => '000000',
        ])->assertStatus(422);
    }
}
