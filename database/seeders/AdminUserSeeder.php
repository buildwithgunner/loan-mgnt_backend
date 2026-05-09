<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'danielmoses849@gmail.com'],
            [
                'name' => 'Admin Daniel',
                'role' => 'admin',
                'password' => Hash::make('blackwolf12345'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'Lukeaddyflooringcapital@gmail.com'],
            [
                'name' => 'Admin Luke',
                'role' => 'admin',
                'password' => Hash::make('123456AaMD'),
            ]
        );
    }
}
