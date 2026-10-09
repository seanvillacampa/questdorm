<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60);
            $table->decimal('value', 12, 4);
            $table->date('effective_from')->nullable(); // null = simple key/value setting
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['key', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
