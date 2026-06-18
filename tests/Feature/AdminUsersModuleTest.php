<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Rental\AdminUser;
use App\Models\Rental\AuditLog;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(AdminRole $role = AdminRole::SuperAdmin): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function rider(string $name = 'Rider One'): User
    {
        $user = User::create([
            'name' => $name,
            'mobile' => '9' . str_pad((string) random_int(0, 99999999), 9, '0', STR_PAD_LEFT),
            'email' => strtolower(str_replace(' ', '', $name)) . '@demo.in',
            'password' => 'password',
        ]);
        UserProfile::create(['user_id' => $user->id, 'kyc_status' => 'approved']);

        return $user;
    }

    public function test_user_list_renders_and_searches(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $alice = $this->rider('Alice Kapoor');
        $bob = $this->rider('Bob Singh');

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$alice, $bob])
            ->searchTable('Alice')
            ->assertCanSeeTableRecords([$alice])
            ->assertCanNotSeeTableRecords([$bob]);
    }

    public function test_ops_can_block_a_rider_with_reason_and_it_is_audited(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $rider = $this->rider();

        Livewire::test(ViewUser::class, ['record' => $rider->getKey()])
            ->assertOk()
            ->callAction('toggleBlock', data: ['reason' => 'Fraudulent documents']);

        $this->assertTrue($rider->fresh()->isBlocked());
        $this->assertDatabaseHas('rental_audit_logs', [
            'action' => 'user.block',
            'entity_type' => 'user',
            'entity_id' => $rider->id,
        ]);
        $this->assertEquals(1, AuditLog::where('action', 'user.block')->count());
    }

    public function test_support_cannot_see_block_action(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $rider = $this->rider();

        Livewire::test(ViewUser::class, ['record' => $rider->getKey()])
            ->assertActionHidden('toggleBlock');
    }
}
