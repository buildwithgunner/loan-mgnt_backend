<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->nullOnDelete()->after('user_id');
            $table->string('purpose')->nullable()->after('duration');
            $table->decimal('outstanding_balance', 10, 2)->default(0)->after('next_payment_date');
            $table->boolean('is_disbursed')->default(false)->after('outstanding_balance');
            $table->string('disbursement_method')->nullable()->after('is_disbursed');
            $table->string('transfer_reference')->nullable()->after('disbursement_method');
            $table->timestamp('disbursed_at')->nullable()->after('transfer_reference');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guarantor_id');
            $table->dropColumn(['purpose', 'outstanding_balance', 'is_disbursed', 'disbursement_method', 'transfer_reference', 'disbursed_at']);
        });
    }
};
