<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Loan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $usersData = [
            ['name' => 'System Admin', 'email' => 'danielmoses849@gmail.com', 'is_admin' => true, 'phone' => '+1 800-ADMIN-01', 'status' => 'Active', 'created_at' => '2026-01-01 00:00:00', 'password' => 'admin1234'],
            ['name' => 'John Doe', 'email' => 'john.doe@example.com', 'is_admin' => false, 'phone' => '+1 234-567-8901', 'status' => 'Active', 'created_at' => '2026-01-10 00:00:00', 'password' => 'password'],
            ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com', 'is_admin' => false, 'phone' => '+1 234-567-8902', 'status' => 'Active', 'created_at' => '2026-03-22 00:00:00', 'password' => 'password'],
            ['name' => 'Michael Johnson', 'email' => 'michael.j@example.com', 'is_admin' => false, 'phone' => '+1 234-567-8903', 'status' => 'Inactive', 'created_at' => '2025-06-15 00:00:00', 'password' => 'password'],
            ['name' => 'Emily Davis', 'email' => 'emily.d@example.com', 'is_admin' => false, 'phone' => '+1 234-567-8904', 'status' => 'Active', 'created_at' => '2025-10-05 00:00:00', 'password' => 'password'],
            ['name' => 'William Brown', 'email' => 'will.b@example.com', 'is_admin' => false, 'phone' => '+1 234-567-8905', 'status' => 'Suspended', 'created_at' => '2024-11-30 00:00:00', 'password' => 'password'],
        ];

        foreach ($usersData as $userData) {
            $password = $userData['password'];
            unset($userData['password']);
            
            User::factory()->create(array_merge($userData, [
                'password' => bcrypt($password),
            ]));
        }

        $loansData = [
            ['user_id' => 1, 'amount' => 5000, 'interest_rate' => '5%', 'duration' => '12 months', 'status' => 'Active', 'start_date' => '2026-01-15', 'next_payment_date' => '2026-05-15'],
            ['user_id' => 2, 'amount' => 12000, 'interest_rate' => '4.5%', 'duration' => '24 months', 'status' => 'Pending', 'start_date' => null, 'next_payment_date' => null],
            ['user_id' => 3, 'amount' => 3000, 'interest_rate' => '6%', 'duration' => '6 months', 'status' => 'Repaid', 'start_date' => '2025-08-01', 'next_payment_date' => null],
            ['user_id' => 3, 'amount' => 5000, 'interest_rate' => '6%', 'duration' => '12 months', 'status' => 'Repaid', 'start_date' => '2025-08-01', 'next_payment_date' => null],
            ['user_id' => 4, 'amount' => 15000, 'interest_rate' => '4%', 'duration' => '36 months', 'status' => 'Active', 'start_date' => '2025-11-20', 'next_payment_date' => '2026-05-20'],
            ['user_id' => 5, 'amount' => 8000, 'interest_rate' => '5.5%', 'duration' => '12 months', 'status' => 'Defaulted', 'start_date' => '2025-02-10', 'next_payment_date' => '2025-09-10'],
            ['user_id' => 5, 'amount' => 14000, 'interest_rate' => '5.5%', 'duration' => '24 months', 'status' => 'Defaulted', 'start_date' => '2025-02-10', 'next_payment_date' => '2025-09-10'],
        ];

        foreach ($loansData as $loanData) {
            Loan::create($loanData);
        }
    }
}
