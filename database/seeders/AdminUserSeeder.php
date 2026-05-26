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
                'name' => 'Daniel Moses',
                'password' => Hash::make('admin1234'),
                'is_admin' => true,
                'status' => 'Active',
            ]
        );
    }
}
