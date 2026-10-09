<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->enum('customer_type', ['tenant', 'student', 'non_student']);
            $table->decimal('price', 8, 2);
            $table->timestamps();

            $table->unique(['service_id', 'customer_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
