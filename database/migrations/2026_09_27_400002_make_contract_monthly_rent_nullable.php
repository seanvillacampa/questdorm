<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // monthly_rent is now derived from Room.monthly_rate at invoice time
            $table->decimal('monthly_rent', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('monthly_rent', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
