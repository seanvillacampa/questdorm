<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->enum('customer_type', ['tenant', 'student', 'non_student']);
            $table->string('room_no')->nullable();
            $table->date('date_received');
            $table->enum('payment_method', ['none', 'cash', 'online'])->default('none');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->boolean('paid_before_service')->default(false);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('total_weight_kg', 8, 2)->default(0);
            $table->unsignedInteger('total_loads')->default(0);
            $table->unsignedInteger('total_liquid_ml')->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['date_received', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_orders');
    }
};
