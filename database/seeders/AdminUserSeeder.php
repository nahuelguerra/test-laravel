<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@planes.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'phone' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $admin->hasRole(RoleEnum::SuperAdmin->value)) {
            $admin->assignRole(RoleEnum::SuperAdmin->value);
        }
    }
}
