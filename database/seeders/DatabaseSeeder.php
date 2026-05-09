<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 20 users, each with varied applications
        \App\Models\User::factory(20)->create()->each(function ($user) {
            \App\Models\Application::factory(rand(1, 3))->create([
                'user_id' => $user->id
            ]);
        });

        // Create specific rejected applications for testing the rejected filter
        \App\Models\User::factory(5)->create()->each(function ($user) {
            \App\Models\Application::factory()->create([
                'user_id' => $user->id,
                'status' => 'rejected'
            ]);
        });

        // Create a specific test user for the dashboard
        $testUser = \App\Models\User::factory()->create([
            'name' => 'Demo User',
            'email' => 'user@demo.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        // Pending application for demo user
        \App\Models\Application::factory()->create([
            'user_id' => $testUser->id,
            'type' => 'Fix & Flip',
            'amount' => 350000,
            'status' => 'pending',
            'processing_stage' => 'Pending Review',
            'processing_level' => 20,
        ]);

        // Another in-progress application for demo user
        \App\Models\Application::factory()->create([
            'user_id' => $testUser->id,
            'type' => 'Commercial',
            'amount' => 1250000,
            'status' => 'under_review',
            'processing_stage' => 'Underwriting',
            'processing_level' => 65,
        ]);

        // Create specified admins
        \App\Models\Admin::create([
            'name' => 'Daniel Moses',
            'email' => 'danielmoses849@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
        ]);

        \App\Models\Admin::create([
            'name' => 'Luke Addy',
            'email' => 'Lukeaddyflooringcapital@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('123456AaMD'),
        ]);

        // Initial Site Settings
        \Illuminate\Support\Facades\DB::table('site_settings')->insert([
            'support_phone' => '563-571-0448',
            'support_email' => 'support@blackwolvesacquisition.com',
            'address' => '123 Finance Way, Wall St, NY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
