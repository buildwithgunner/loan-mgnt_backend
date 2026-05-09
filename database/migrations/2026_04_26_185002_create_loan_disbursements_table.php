<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            // foreignId creates an index for security/performance automatically
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_disbursements');
    }
};
