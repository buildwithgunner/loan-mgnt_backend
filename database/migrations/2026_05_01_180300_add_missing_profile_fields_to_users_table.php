<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->date('dob')->nullable()->after('zip_code');
            $table->string('marital_status')->nullable()->after('dob');
            $table->string('occupation')->nullable()->after('marital_status');
            $table->string('self_employed')->nullable()->after('occupation');
            $table->string('estimated_fico')->nullable()->after('self_employed');
            $table->string('estimated_net_worth')->nullable()->after('estimated_fico');
            $table->string('referral_source')->nullable()->after('estimated_net_worth');
            $table->string('working_with_consultant')->nullable()->after('referral_source');
            $table->text('loan_intent')->nullable()->after('working_with_consultant'); // "Why the loan is needed"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city', 'state', 'zip_code', 'dob', 'marital_status', 
                'occupation', 'self_employed', 'estimated_fico', 
                'estimated_net_worth', 'referral_source', 
                'working_with_consultant', 'loan_intent'
            ]);
        });
    }
};
