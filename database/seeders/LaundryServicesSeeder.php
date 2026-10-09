<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class LaundryServicesSeeder extends Seeder
{
    /**
     * Seed laundry services and their prices.
     */
    public function run(): void
    {
        $services = [
            ['code' => 'WASH', 'name' => 'Wash Only', 'weight_limit_kg' => 8.00],
            ['code' => 'DRY', 'name' => 'Dry Only', 'weight_limit_kg' => 8.00],
            ['code' => 'WASH_DRY', 'name' => 'Wash & Dry', 'weight_limit_kg' => 8.00],
            ['code' => 'FOLD', 'name' => 'Wash, Dry & Fold', 'weight_limit_kg' => 8.00],
            ['code' => 'IRON', 'name' => 'Iron/Press', 'weight_limit_kg' => 5.00],
            ['code' => 'DRY_CLEAN', 'name' => 'Dry Cleaning', 'weight_limit_kg' => 3.00],
        ];

        foreach ($services as $serviceData) {
            $service = Service::firstOrCreate(
                ['code' => $serviceData['code']],
                [
                    'name' => $serviceData['name'],
                    'weight_limit_kg' => $serviceData['weight_limit_kg'],
                    'is_active' => true,
                ]
            );

            // Create initial prices for different customer types if not exists
            if ($service->wasRecentlyCreated || !$service->prices()->exists()) {
                $basePrice = match ($serviceData['code']) {
                    'WASH' => 60.00,
                    'DRY' => 50.00,
                    'WASH_DRY' => 100.00,
                    'FOLD' => 150.00,
                    'IRON' => 80.00,
                    'DRY_CLEAN' => 200.00,
                    default => 100.00,
                };

                // Create prices for each customer type
                $customerTypes = ['tenant', 'student', 'non_student'];
                foreach ($customerTypes as $index => $type) {
                    ServicePrice::create([
                        'service_id' => $service->id,
                        'customer_type' => $type,
                        'price' => $basePrice + ($index * 10), // tenant gets base price, others slightly higher
                    ]);
                }
            }
        }

        $this->command->info('Laundry services seeded successfully!');
    }
}
