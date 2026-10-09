<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['tenant', 'student', 'non_student']);
            $table->string('room_no')->nullable();
            $table->string('contact_no')->nullable();
            $table->timestamps();

            $table->index(['name', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
