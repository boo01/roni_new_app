<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'b2b-customer']);
        Role::firstOrCreate(['name' => 'b2c-customer']);

        CustomerGroup::firstOrCreate(
            ['slug' => 'companies'],
            [
                'name' => 'კომპანიები',
                'discount_percent' => 0,
                'is_default_for_b2b' => true,
                'notes' => 'Default group for all B2B customers; per-product overrides set during import.',
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'z.gabisonia@oritech.io'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $admin->assignRole('admin');
    }
}
