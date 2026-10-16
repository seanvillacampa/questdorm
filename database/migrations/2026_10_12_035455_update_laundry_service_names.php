<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update service names and prices to match the new price reference list
        DB::table('services')
            ->where('code', 'full_service')
            ->update(['name' => 'Full Service (Wash-Dry-Fold)']);

        DB::table('services')
            ->where('code', 'bedsheet_only')
            ->update(['name' => 'Bedsheet Only']);

        // Prices are unchanged — only names changed
    }

    public function down(): void
    {
        DB::table('services')
            ->where('code', 'full_service')
            ->update(['name' => 'Full Service (Wash, Dry & Fold)']);

        DB::table('services')
            ->where('code', 'bedsheet_only')
            ->update(['name' => 'Bedsheet / Blanket']);
    }
};
