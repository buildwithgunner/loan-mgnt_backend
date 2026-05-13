<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all user data (users, applications, documents, etc.) but keeps admin data and site settings.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('Are you sure you want to delete all users and their related data? Admins will be kept.')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->info('Truncating user tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'users',
            'applications',
            'documents',
            'leads',
            'loan_disbursements',
            'notifications',
            'referrals'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->line("Cleared table: $table");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Successfully cleared all user data! Your admin accounts are safe.');
    }
}
