<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_payments', function (Blueprint $table) {
            $table->decimal('carry_over_balance', 10, 2)->default(0)->after('share_amount')
                ->comment('Unpaid balance from this tenant\'s previous billing statements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_payments', function (Blueprint $table) {
            $table->dropColumn('carry_over_balance');
        });
    }
};
