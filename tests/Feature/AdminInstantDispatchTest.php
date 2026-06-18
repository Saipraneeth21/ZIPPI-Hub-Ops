<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\InstantDispatches\Pages\CreateInstantDispatch;
use App\Filament\Resources\InstantDispatches\Pages\ListInstantDispatches;
use App\Models\Rental\AdminUser;
use App\Models\Rental\InstantDispatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminInstantDispatchTest extends TestCase
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

    public function test_ops_can_log_an_instant_dispatch(): void
    {
        $ops = $this->admin(AdminRole::Ops);
        $this->actingAs($ops, 'admin');

        Livewire::test(CreateInstantDispatch::class)
            ->fillForm([
                'name' => 'Ravi Kumar',
                'mobile' => '9876543210',
                'aadhar_number' => '1234 5678 9012',
                'driving_license' => 'KA0120230001234',
                'pickup_date' => now()->addDay()->toDateString(),
                'rental_type' => 'daily',
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $dispatch = InstantDispatch::where('name', 'Ravi Kumar')->first();
        $this->assertNotNull($dispatch);
        $this->assertSame('daily', $dispatch->rental_type->value);
        $this->assertSame($ops->id, $dispatch->created_by);
        // Aadhaar decrypts to the digits only (spaces stripped).
        $this->assertSame('123456789012', $dispatch->aadhar_number);
        $this->assertSame('XXXX XXXX 9012', $dispatch->aadhar_masked);
    }

    public function test_aadhaar_is_stored_encrypted_not_in_plaintext(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(CreateInstantDispatch::class)
            ->fillForm([
                'name' => 'Priya S',
                'mobile' => '9000000000',
                'aadhar_number' => '1111 2222 3333',
                'driving_license' => 'KA01X',
                'pickup_date' => now()->addDay()->toDateString(),
                'rental_type' => 'hourly',
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Raw DB value must not contain the plaintext Aadhaar.
        $raw = DB::table('rental_instant_dispatches')->where('name', 'Priya S')->value('aadhar_number');
        $this->assertNotSame('111122223333', $raw);
        $this->assertStringNotContainsString('1111', $raw);
    }

    public function test_list_renders_for_ops(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $d = InstantDispatch::create([
            'name' => 'Walk-in', 'mobile' => '9123456780', 'aadhar_number' => '999988887777',
            'driving_license' => 'DL1', 'pickup_date' => now()->addDay(), 'rental_type' => 'daily',
        ]);

        Livewire::test(ListInstantDispatches::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$d]);
    }

    public function test_support_cannot_access_instant_dispatch(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');

        Livewire::test(ListInstantDispatches::class)->assertForbidden();
        Livewire::test(CreateInstantDispatch::class)->assertForbidden();
    }
}
