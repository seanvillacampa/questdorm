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
        Schema::create('tenant_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_required', 10, 2); // Their share of room deposit
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_deducted', 10, 2)->default(0);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['contract_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_deposits');
    }
};
