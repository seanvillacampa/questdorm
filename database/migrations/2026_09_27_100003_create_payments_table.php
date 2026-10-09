<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            // cash | bank_transfer | gcash | maya | card | qr_ph | paymongo
            $table->string('method', 30);
            $table->date('received_at');
            $table->string('reference', 100)->nullable();
            $table->string('notes')->nullable();
            // paymongo | staff
            $table->string('recorded_by_type', 20)->default('staff');
            $table->foreignId('recorded_by_user')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
