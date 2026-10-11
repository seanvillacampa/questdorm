<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedQuestBuilding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:quest-building {--fresh : Drop all tables and migrate before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with Quest Building data from SQL dump';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Running fresh migration...');
            Artisan::call('migrate:fresh', [], $this->output);
            $this->newLine();
        }
        
        $this->info('Seeding Quest Building data...');
        $this->newLine();
        
        // Set environment variable to use Quest dump
        putenv('SEED_WITH_QUEST_DUMP=true');
        
        // Run the database seeder
        $exitCode = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder'], $this->output);
        
        // Reset environment variable
        putenv('SEED_WITH_QUEST_DUMP=false');
        
        if ($exitCode === 0) {
            $this->newLine();
            $this->info('✓ Quest Building data seeded successfully!');
            $this->info('  - 28 rooms created across 4 floors');
            $this->info('  - 45 tenants with real names and contact details');
            $this->info('  - 28 active contracts with varying due dates');
            $this->info('  - Invoices from January 2026 to October 2026');
            $this->info('  - Complete payment history with various statuses');
            $this->info('  - Monthly meter readings');
            $this->info('  - Laundry services and sample orders');
            $this->newLine();
        } else {
            $this->error('Seeding failed with exit code: ' . $exitCode);
        }
        
        return $exitCode;
    }
}
