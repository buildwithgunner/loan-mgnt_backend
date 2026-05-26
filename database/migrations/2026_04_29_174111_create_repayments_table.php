<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->decimal('amount_due', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('Unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
