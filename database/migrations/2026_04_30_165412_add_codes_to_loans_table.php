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
        Schema::table('loans', function (Blueprint $table) {
            $table->string('approval_code')->nullable()->after('status');
            $table->string('tracking_code')->nullable()->after('approval_code');
            $table->boolean('codes_requested')->default(false)->after('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['approval_code', 'tracking_code', 'codes_requested']);
        });
    }
};
