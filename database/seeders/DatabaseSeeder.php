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
