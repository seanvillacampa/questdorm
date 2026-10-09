<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->unsignedTinyInteger('due_day')->default(5); // day of month, 1-28
            $table->decimal('deposit_required', 10, 2)->default(0);
            $table->decimal('deposit_collected', 10, 2)->default(0);
            // active | ended | ending_soon
            $table->string('status', 20)->default('active');
            $table->string('contract_file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
