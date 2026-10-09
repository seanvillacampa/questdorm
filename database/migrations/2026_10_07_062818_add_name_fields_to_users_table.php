<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            
            // Add unique constraint on first_name, middle_name, last_name combination
            $table->unique(['first_name', 'middle_name', 'last_name'], 'users_full_name_unique');
        });
        
        // Migrate existing data: split 'name' into parts
        DB::statement("
            UPDATE users 
            SET 
                first_name = SUBSTRING_INDEX(name, ' ', 1),
                last_name = SUBSTRING_INDEX(name, ' ', -1),
                middle_name = CASE 
                    WHEN LENGTH(name) - LENGTH(REPLACE(name, ' ', '')) >= 2 
                    THEN SUBSTRING_INDEX(SUBSTRING_INDEX(name, ' ', 2), ' ', -1)
                    ELSE NULL
                END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_full_name_unique');
            $table->dropColumn(['first_name', 'middle_name', 'last_name']);
        });
    }
};
