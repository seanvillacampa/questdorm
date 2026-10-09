<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FreshDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds - clears all data then runs comprehensive seeder.
     */
    public function run(): void
    {
        $this->command->info('Clearing all existing data...');
        $this->call(ClearAllDataSeeder::class);
        
        $this->command->info('Seeding laundry services...');
        $this->call(LaundryServicesSeeder::class);
        
        $this->command->info('Seeding fresh dummy data...');
        $this->call(DatabaseSeeder::class);
        
        $this->command->info('Done! Database has been refreshed with dummy data.');
    }
}
