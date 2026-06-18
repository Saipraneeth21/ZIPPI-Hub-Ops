<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Rental\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['email' => 'admin@zippi.in'],
            [
                'name' => 'ZIPPI Super Admin',
                'password' => 'password',           // hashed by the model cast; change after first login
                'role' => AdminRole::SuperAdmin,
                'is_active' => true,
            ],
        );
    }
}
