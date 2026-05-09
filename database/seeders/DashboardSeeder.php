<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Application;
use App\Models\Document;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DashboardSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or update a test user
        $user = User::updateOrCreate(
            ['email' => 'marcus@example.com'],
            [
                'name' => 'Marcus Johnson',
                'phone' => '+1 727-555-0192',
                'role' => 'user',
                'password' => Hash::make('password123'),
            ]
        );

        // Clean existing data for a fresh seed
        $user->applications()->delete();
        $user->documents()->delete();
        $user->notifications()->delete();

        // Sample Applications
        $user->applications()->createMany([
            ['type' => 'Fix & Flip', 'property' => '1824 Oak Ave, Tampa FL', 'amount' => '$285,000', 'status' => 'approved', 'ltv' => '70%'],
            ['type' => 'New Construction', 'property' => '390 Pine Rd, Clearwater FL', 'amount' => '$520,000', 'status' => 'under_review', 'ltv' => '65%'],
            ['type' => 'Cash-Out Refinance', 'property' => '742 Maple Dr, Orlando FL', 'amount' => '$180,000', 'status' => 'pending', 'ltv' => '72%'],
        ]);

        // Sample Documents
        $user->documents()->createMany([
            ['name' => 'Purchase Agreement – 1824 Oak Ave', 'size' => '2.4 MB', 'type' => 'pdf', 'category' => 'Underwriting', 'path' => 'docs/1.pdf'],
            ['name' => 'Bank Statements – Jan 2026', 'size' => '890 KB', 'type' => 'pdf', 'category' => 'Underwriting', 'path' => 'docs/2.pdf'],
            ['name' => 'ID Verification – Passport', 'size' => '3.2 MB', 'type' => 'img', 'category' => 'Personal', 'path' => 'docs/3.jpg'],
        ]);

        // Sample Notifications
        $user->notifications()->createMany([
            ['message' => 'Your Fix & Flip application has been approved!', 'type' => 'success', 'read_at' => null],
            ['message' => 'Document request: Please upload your Q1 bank statements.', 'type' => 'warning', 'read_at' => null],
            ['message' => 'Loan officer James assigned to your New Construction application.', 'type' => 'info', 'read_at' => now()],
        ]);
    }
}
