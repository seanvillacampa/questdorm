<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make first_name, middle_name, last_name NOT NULL with defaults
            $table->string('first_name', 100)->nullable(false)->default('')->change();
            $table->string('middle_name', 100)->nullable(false)->default('')->change();
            $table->string('last_name', 100)->nullable(false)->default('')->change();
            
            // Remove the name column
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add name column back
            $table->string('name')->after('last_name');
            
            // Make name fields nullable again
            $table->string('first_name', 100)->nullable()->change();
            $table->string('middle_name', 100)->nullable()->change();
            $table->string('last_name', 100)->nullable()->change();
        });
    }
};
