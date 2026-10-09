<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedIfEmpty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-if-empty {--force : Force seeding even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed database only if it is empty (safe for production)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if database has data
        $userCount = DB::table('users')->count();
        
        if ($userCount > 0) {
            $this->error('❌ Database is not empty! Found ' . $userCount . ' users.');
            $this->warn('Seeding aborted to prevent data loss.');
            $this->info('💡 Use "php artisan migrate:fresh --seed" to wipe and reseed (DESTRUCTIVE!)');
            return 1;
        }

        $this->info('✓ Database is empty. Starting seeding...');
        $this->newLine();
        
        // Run the seeder
        $this->call('db:seed', [
            '--class' => 'DatabaseSeeder',
            '--force' => $this->option('force'),
        ]);
        
        $this->newLine();
        $this->info('✓ Seeding completed successfully!');
        
        // Show summary
        $this->newLine();
        $this->info('📊 Database Summary:');
        $this->table(
            ['Table', 'Count'],
            [
                ['Users', DB::table('users')->count()],
                ['Rooms', DB::table('rooms')->count()],
                ['Tenants', DB::table('tenants')->count()],
                ['Contracts', DB::table('contracts')->count()],
                ['Invoices', DB::table('invoices')->count()],
                ['Laundry Orders', DB::table('laundry_orders')->count()],
                ['Services', DB::table('services')->count()],
            ]
        );
        
        return 0;
    }
}
