<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@wms.com'],
            [
                'name'     => 'Super Admin',
                'role'     => 'super_admin',
                'locale'   => 'id',
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@wms.com'],
            [
                'name'     => 'Admin',
                'role'     => 'admin',
                'locale'   => 'id',
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'allocator@wms.com'],
            [
                'name'     => 'Allocator',
                'role'     => 'allocator',
                'locale'   => 'id',
                'password' => Hash::make('password'),
            ]
        );
    }
}
