<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearAllDataSeeder extends Seeder
{
    /**
     * Clear all existing data from the database.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear all tables (in order to respect foreign key constraints)
        // Using DB::table()->exists() check or try-catch to handle non-existent tables
        $tablesToClear = [
            'audit_logs',
            'order_items',
            'laundry_orders',
            'tenant_payments',
            'payments',
            'meter_readings',
            'deposit_entries',
            'invoices',
            'contract_tenants',
            'contracts',
            'tenants',
            'rooms',
            'model_has_roles',
            'model_has_permissions',
            'users',
        ];

        foreach ($tablesToClear as $table) {
            try {
                DB::table($table)->truncate();
                $this->command->info("Cleared: {$table}");
            } catch (\Exception $e) {
                $this->command->warn("Skipped: {$table} (table doesn't exist or error)");
            }
        }
        
        // Keep service-related tables and settings
        // DB::table('services')->truncate(); // Keep services
        // DB::table('service_prices')->truncate(); // Keep service prices
        // DB::table('settings')->truncate(); // Keep settings - will be recreated

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('All data cleared successfully!');
    }
}
