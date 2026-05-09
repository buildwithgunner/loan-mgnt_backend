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
        Schema::table('applications', function (Blueprint $table) {
            $table->string('processing_stage')->default('Application Submitted')->after('status');
            $table->integer('processing_level')->default(20)->after('processing_stage'); // Percentage 0-100
            $table->string('approval_code')->nullable()->unique()->after('processing_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['processing_stage', 'processing_level', 'approval_code']);
        });
    }
};
