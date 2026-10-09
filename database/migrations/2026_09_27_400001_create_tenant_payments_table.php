<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant payment record for a room invoice.
 * One row per tenant per invoice — tracks their individual share.
 *
 * status values: pending | paid | overdue
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('share_amount', 10, 2);   // rent ÷ tenant_count
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending|paid|overdue
            $table->string('method', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('paymongo_link_id', 60)->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
