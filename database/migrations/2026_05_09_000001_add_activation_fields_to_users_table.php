<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('role');
            $table->boolean('activation_requested')->default(false)->after('is_active');
            $table->timestamp('activation_requested_at')->nullable()->after('activation_requested');
            $table->timestamp('activated_at')->nullable()->after('activation_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'activation_requested', 'activation_requested_at', 'activated_at']);
        });
    }
};
