<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 10)->unique(); // e.g. "101", "204"
            $table->unsignedTinyInteger('floor');
            $table->string('capacity', 20)->default('1 bed'); // "1 bed", "2 beds", etc.
            $table->decimal('monthly_rate', 10, 2)->default(0);
            $table->string('meter_number', 30)->nullable();
            // vacant | occupied | maintenance
            $table->string('status', 20)->default('vacant');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
