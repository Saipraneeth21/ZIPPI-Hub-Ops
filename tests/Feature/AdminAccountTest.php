<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\MyProfile;
use App\Models\Rental\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(AdminRole $role = AdminRole::Ops): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_their_profile(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(MyProfile::class)
            ->fillForm(['name' => 'Renamed Ops', 'email' => 'renamed@zippi.in'])
            ->callAction('save')
            ->assertHasNoFormErrors();

        $admin->refresh();
        $this->assertSame('Renamed Ops', $admin->name);
        $this->assertSame('renamed@zippi.in', $admin->email);
    }

    public function test_admin_can_change_their_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(AccountSettings::class)
            ->fillForm([
                'current_password' => 'password',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->callAction('save')
            ->assertHasNoFormErrors();

        $admin->refresh();
        $this->assertTrue(Hash::check('newsecret123', $admin->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(AccountSettings::class)
            ->fillForm([
                'current_password' => 'not-the-password',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->callAction('save')
            ->assertHasFormErrors(['current_password']);
    }

    public function test_account_pages_are_available_to_every_role(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');

        Livewire::test(MyProfile::class)->assertOk();
        Livewire::test(AccountSettings::class)->assertOk();
    }
}
