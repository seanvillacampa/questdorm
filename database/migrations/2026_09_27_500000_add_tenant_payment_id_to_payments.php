<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('tenant_payment_id')
                  ->nullable()
                  ->after('invoice_id')
                  ->constrained('tenant_payments')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\TenantPayment::class, 'tenant_payment_id');
            $table->dropColumn('tenant_payment_id');
        });
    }
};
