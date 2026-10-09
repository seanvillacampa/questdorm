<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Unpaid balance carried forward FROM the previous invoice
            // Adds to total_amount_due for this invoice
            $table->decimal('carry_over_balance', 10, 2)->default(0)->after('electricity_amount');

            // Overpayment credit carried forward FROM the previous invoice
            // Reduces total_amount_due for this invoice
            $table->decimal('credit_balance', 10, 2)->default(0)->after('carry_over_balance');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['carry_over_balance', 'credit_balance']);
        });
    }
};
