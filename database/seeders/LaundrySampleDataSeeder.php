<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LaundryOrder;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds realistic laundry orders for the past 3 months.
 *
 * Safe to run on a live DB — uses firstOrCreate for customers
 * and generates unique order numbers. Run via:
 *   php artisan db:seed --class=LaundrySampleDataSeeder
 * or via /deploy/seed-laundry route.
 */
class LaundrySampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure services exist first
        $this->call(LaundryServicesSeeder::class);

        $services = Service::with('prices')->where('is_active', true)->get()->keyBy('code');

        if ($services->isEmpty()) {
            $this->command->error('No active services found. Aborting.');
            return;
        }

        $recorder = User::role('owner')->first() ?? User::first();

        // ── Customers ────────────────────────────────────────────────────
        $customers = [
            // Tenants
            ['name' => 'Elena Maria Rivera',   'type' => 'tenant',      'room_no' => '101', 'contact_no' => '09171234001'],
            ['name' => 'Jose Miguel Santos',   'type' => 'tenant',      'room_no' => '102', 'contact_no' => '09171234002'],
            ['name' => 'Isabel Carmen Diaz',   'type' => 'tenant',      'room_no' => '103', 'contact_no' => '09171234003'],
            ['name' => 'Maria Luisa Santos',   'type' => 'tenant',      'room_no' => '104', 'contact_no' => '09171234004'],
            ['name' => 'Jorge Luis Lopez',     'type' => 'tenant',      'room_no' => '105', 'contact_no' => '09171234005'],
            ['name' => 'Ana Victoria Lopez',   'type' => 'tenant',      'room_no' => '107', 'contact_no' => '09171234007'],
            ['name' => 'Eduardo Soto',         'type' => 'tenant',      'room_no' => '201', 'contact_no' => '09181234001'],
            ['name' => 'Camila Benitez',       'type' => 'tenant',      'room_no' => '202', 'contact_no' => '09181234002'],
            // Students
            ['name' => 'Mark Anthony Reyes',   'type' => 'student',     'room_no' => null,  'contact_no' => '09201234001'],
            ['name' => 'Sophia Grace Tan',     'type' => 'student',     'room_no' => null,  'contact_no' => '09201234002'],
            ['name' => 'Carlos Ivan Morales',  'type' => 'student',     'room_no' => null,  'contact_no' => '09201234003'],
            ['name' => 'Angela Rose Cruz',     'type' => 'student',     'room_no' => null,  'contact_no' => '09201234004'],
            // Non-students / walk-in
            ['name' => 'Roberto Lim',          'type' => 'non_student', 'room_no' => null,  'contact_no' => '09301234001'],
            ['name' => 'Patricia Gomez',       'type' => 'non_student', 'room_no' => null,  'contact_no' => '09301234002'],
        ];

        $createdCustomers = [];
        foreach ($customers as $c) {
            $createdCustomers[] = Customer::firstOrCreate(
                ['name' => $c['name'], 'type' => $c['type']],
                ['room_no' => $c['room_no'], 'contact_no' => $c['contact_no']]
            );
        }

        // ── Order templates: [customer_index, service_code, weight_kg, payment_status, payment_method, paid_before] ──
        $orderTemplates = [
            // October 2026
            [0, ['WASH_DRY'],         [6.5],  'paid',   'cash',   false, '2026-10-01'],
            [1, ['WASH', 'DRY'],      [4.0, 4.0], 'paid', 'cash', true,  '2026-10-01'],
            [8, ['FOLD'],             [5.0],  'paid',   'cash',   false, '2026-10-02'],
            [9, ['WASH_DRY'],         [3.5],  'paid',   'online', true,  '2026-10-02'],
            [2, ['WASH_DRY'],         [7.0],  'paid',   'cash',   false, '2026-10-03'],
            [12,['WASH', 'IRON'],     [8.0, 2.0], 'paid','cash',  false, '2026-10-03'],
            [3, ['FOLD'],             [9.0],  'unpaid', 'none',   false, '2026-10-04'],
            [10,['WASH_DRY'],         [4.5],  'paid',   'cash',   true,  '2026-10-04'],
            [4, ['WASH'],             [6.0],  'paid',   'cash',   false, '2026-10-05'],
            [13,['DRY'],              [5.5],  'paid',   'cash',   false, '2026-10-05'],
            [5, ['WASH_DRY'],         [8.0],  'unpaid', 'none',   false, '2026-10-06'],
            [11,['FOLD'],             [3.0],  'paid',   'online', true,  '2026-10-06'],
            [6, ['WASH', 'DRY'],      [5.0, 5.0], 'paid','cash',  false, '2026-10-07'],
            [0, ['IRON'],             [2.5],  'paid',   'cash',   false, '2026-10-07'],
            [7, ['WASH_DRY'],         [6.0],  'paid',   'cash',   true,  '2026-10-08'],
            [8, ['WASH_DRY'],         [4.0],  'paid',   'cash',   false, '2026-10-08'],
            [1, ['FOLD'],             [7.5],  'unpaid', 'none',   false, '2026-10-09'],
            [9, ['WASH'],             [6.0],  'paid',   'online', false, '2026-10-09'],
            [2, ['DRY_CLEAN'],        [1.5],  'paid',   'cash',   true,  '2026-10-10'],
            [10,['WASH_DRY'],         [5.0],  'paid',   'cash',   false, '2026-10-10'],

            // September 2026
            [3, ['WASH_DRY'],         [8.0],  'paid',   'cash',   false, '2026-09-05'],
            [4, ['FOLD'],             [6.5],  'paid',   'cash',   false, '2026-09-05'],
            [11,['WASH', 'DRY'],      [3.0, 3.0], 'paid','cash',  true,  '2026-09-06'],
            [5, ['WASH_DRY'],         [5.0],  'paid',   'online', false, '2026-09-08'],
            [12,['IRON'],             [3.0],  'paid',   'cash',   false, '2026-09-08'],
            [6, ['FOLD'],             [7.0],  'paid',   'cash',   false, '2026-09-10'],
            [13,['WASH_DRY'],         [4.5],  'paid',   'cash',   true,  '2026-09-12'],
            [7, ['WASH'],             [9.0],  'paid',   'cash',   false, '2026-09-13'],
            [0, ['DRY_CLEAN'],        [2.0],  'paid',   'cash',   false, '2026-09-15'],
            [8, ['WASH_DRY'],         [6.0],  'paid',   'cash',   true,  '2026-09-17'],
            [1, ['FOLD'],             [5.5],  'paid',   'online', false, '2026-09-19'],
            [9, ['WASH_DRY'],         [7.0],  'paid',   'cash',   false, '2026-09-20'],
            [2, ['WASH', 'IRON'],     [8.0, 1.5], 'paid','cash',  false, '2026-09-22'],
            [10,['WASH_DRY'],         [3.5],  'paid',   'cash',   true,  '2026-09-24'],
            [3, ['DRY'],              [6.0],  'paid',   'cash',   false, '2026-09-26'],
            [11,['FOLD'],             [4.0],  'paid',   'cash',   false, '2026-09-28'],

            // August 2026
            [4, ['WASH_DRY'],         [7.5],  'paid',   'cash',   false, '2026-08-04'],
            [5, ['FOLD'],             [5.0],  'paid',   'cash',   true,  '2026-08-06'],
            [12,['WASH_DRY'],         [6.0],  'paid',   'cash',   false, '2026-08-08'],
            [6, ['WASH', 'DRY'],      [4.0, 4.0], 'paid','cash',  false, '2026-08-10'],
            [13,['IRON'],             [2.5],  'paid',   'cash',   false, '2026-08-12'],
            [7, ['WASH_DRY'],         [8.0],  'paid',   'online', true,  '2026-08-14'],
            [0, ['FOLD'],             [6.5],  'paid',   'cash',   false, '2026-08-16'],
            [8, ['WASH'],             [5.0],  'paid',   'cash',   false, '2026-08-18'],
            [1, ['DRY_CLEAN'],        [1.0],  'paid',   'cash',   true,  '2026-08-20'],
            [9, ['WASH_DRY'],         [7.0],  'paid',   'cash',   false, '2026-08-22'],
            [2, ['FOLD'],             [4.5],  'paid',   'cash',   false, '2026-08-24'],
            [10,['WASH_DRY'],         [6.0],  'paid',   'online', false, '2026-08-26'],
        ];

        $count = 0;

        foreach ($orderTemplates as [$custIdx, $codes, $weights, $payStatus, $payMethod, $paidBefore, $date]) {
            $customer = $createdCustomers[$custIdx];

            $orderNo = LaundryOrder::generateOrderNo($date);

            $order = LaundryOrder::create([
                'order_no'           => $orderNo,
                'customer_id'        => $customer->id,
                'customer_type'      => $customer->type,
                'room_no'            => $customer->room_no,
                'date_received'      => $date,
                'payment_method'     => $payMethod,
                'payment_status'     => $payStatus,
                'paid_before_service'=> $paidBefore,
                'total_amount'       => 0,
                'total_weight_kg'    => 0,
                'total_loads'        => 0,
                'total_liquid_ml'    => 0,
                'remarks'            => null,
                'recorded_by'        => $recorder?->id,
            ]);

            foreach ($codes as $i => $code) {
                $service = $services->get($code);
                if (!$service) continue;

                $weightKg   = (float) $weights[$i];
                $loads      = $service->loadsFor($weightKg);
                $liquidMl   = $service->liquidMlFor($weightKg);
                $unitPrice  = $service->priceFor($customer->type);
                $subtotal   = round($unitPrice * $loads, 2);

                OrderItem::create([
                    'laundry_order_id' => $order->id,
                    'service_id'       => $service->id,
                    'weight_kg'        => $weightKg,
                    'weight_limit_kg'  => $service->weight_limit_kg,
                    'unit_price'       => $unitPrice,
                    'loads'            => $loads,
                    'liquid_ml'        => $liquidMl,
                    'subtotal'         => $subtotal,
                ]);
            }

            $order->recalculateTotals();
            $count++;
        }

        $this->command->info("✅ Created {$count} laundry orders with items.");
    }
}
