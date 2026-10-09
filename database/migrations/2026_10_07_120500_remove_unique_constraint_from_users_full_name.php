<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove the unique constraint on first_name, middle_name, last_name
     * because it doesn't work well with nullable middle_name values.
     * Uniqueness is now enforced at the application level.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_full_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['first_name', 'middle_name', 'last_name'], 'users_full_name_unique');
        });
    }
};
