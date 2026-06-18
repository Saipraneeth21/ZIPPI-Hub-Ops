<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\AdminLoginActivities\Pages\ListAdminLoginActivities;
use App\Models\Rental\AdminLoginActivity;
use App\Models\Rental\AdminUser;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginActivityTest extends TestCase
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

    public function test_admin_login_event_is_recorded(): void
    {
        $admin = $this->admin();

        event(new Login('admin', $admin, false));

        $this->assertDatabaseHas('admin_login_activities', [
            'admin_user_id' => $admin->id,
            'email' => $admin->email,
            'event' => 'login',
        ]);
    }

    public function test_admin_logout_event_is_recorded(): void
    {
        $admin = $this->admin();

        event(new Logout('admin', $admin));

        $this->assertDatabaseHas('admin_login_activities', [
            'admin_user_id' => $admin->id,
            'event' => 'logout',
        ]);
    }

    public function test_failed_admin_login_is_recorded_with_email(): void
    {
        event(new Failed('admin', null, ['email' => 'intruder@zippi.in', 'password' => 'x']));

        $this->assertDatabaseHas('admin_login_activities', [
            'admin_user_id' => null,
            'email' => 'intruder@zippi.in',
            'event' => 'failed',
        ]);
    }

    public function test_events_for_other_guards_are_ignored(): void
    {
        $admin = $this->admin();

        event(new Login('web', $admin, false));

        $this->assertDatabaseCount('admin_login_activities', 0);
    }

    public function test_super_admin_can_view_login_activities(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        event(new Login('admin', $admin, false));

        Livewire::test(ListAdminLoginActivities::class)
            ->assertOk()
            ->assertCanSeeTableRecords(AdminLoginActivity::all());
    }

    public function test_ops_cannot_view_login_activities(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(ListAdminLoginActivities::class)->assertForbidden();
    }
}
