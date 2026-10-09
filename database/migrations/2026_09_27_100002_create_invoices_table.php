<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique(); // BILL-2609-101
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('billing_month', 7); // "2026-09"
            $table->date('due_date');
            $table->decimal('rent_amount', 10, 2);
            $table->decimal('electricity_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            // pending | paid | late | overdue | void
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
