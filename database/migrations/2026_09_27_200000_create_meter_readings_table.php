<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('reading_month', 7); // YYYY-MM
            $table->decimal('previous_kwh', 10, 1)->default(0);
            $table->decimal('current_kwh', 10, 1);
            $table->decimal('kwh_used', 10, 1)->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['room_id', 'reading_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
