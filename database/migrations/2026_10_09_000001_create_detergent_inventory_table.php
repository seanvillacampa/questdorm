<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detergent_inventory', function (Blueprint $table) {
            $table->id();
            // Total stock in millilitres
            $table->unsignedInteger('stock_ml')->default(0);
            $table->timestamps();
        });

        // Seed one row so there's always a single inventory record
        DB::table('detergent_inventory')->insert([
            'stock_ml'   => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('detergent_inventory');
    }
};
