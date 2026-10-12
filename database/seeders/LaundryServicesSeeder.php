<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

/**
 * Seeds the three Quest Building laundry services with correct prices.
 * Delegates to LaundryServiceSeeder — kept for backward compatibility
 * since FreshDummyDataSeeder references this class name.
 */
class LaundryServicesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LaundryServiceSeeder::class);
    }
}
