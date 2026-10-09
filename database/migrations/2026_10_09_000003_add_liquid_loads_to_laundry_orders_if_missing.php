<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('laundry_orders', 'total_loads')) {
                $table->unsignedInteger('total_loads')->default(0)->after('total_weight_kg');
            }
            if (! Schema::hasColumn('laundry_orders', 'total_liquid_ml')) {
                $table->unsignedInteger('total_liquid_ml')->default(0)->after('total_loads');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            if (Schema::hasColumn('laundry_orders', 'total_liquid_ml')) {
                $table->dropColumn('total_liquid_ml');
            }
            if (Schema::hasColumn('laundry_orders', 'total_loads')) {
                $table->dropColumn('total_loads');
            }
        });
    }
};
