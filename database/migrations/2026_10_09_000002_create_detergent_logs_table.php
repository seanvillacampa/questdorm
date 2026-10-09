<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detergent_logs', function (Blueprint $table) {
            $table->id();

            // 'restock' = owner/staff added supply, 'deduct' = used by laundry order
            $table->enum('type', ['restock', 'deduct']);

            $table->unsignedInteger('amount_ml');

            // Snapshot of stock before and after this entry
            $table->unsignedInteger('stock_before_ml');
            $table->unsignedInteger('stock_after_ml');

            // Optional: link to the laundry order that triggered a deduction
            $table->foreignId('laundry_order_id')
                  ->nullable()
                  ->constrained('laundry_orders')
                  ->nullOnDelete();

            // Who performed the action
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detergent_logs');
    }
};
