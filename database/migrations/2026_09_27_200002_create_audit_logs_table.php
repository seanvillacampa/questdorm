<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_label', 100)->nullable(); // preserved name even if user deleted
            $table->string('action', 50);      // Created, Updated, Voided, Payment, etc.
            $table->string('record_type', 60); // Room, Invoice, Contract, etc.
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — insert-only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
