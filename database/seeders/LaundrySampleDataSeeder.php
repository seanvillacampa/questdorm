<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LaundryOrder;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic laundry orders for Aug–Oct 2026.
 *
 * Services (3 only):
 *   full_service  — Full Service (Wash, Dry & Fold)  tenant ₱140 | student ₱150 | non_student ₱165
 *   wash_only     — Wash Only                         all ₱80
 *   bedsheet_only — Bedsheet / Blanket                all ₱160
 *
 * Safe to re-run — customers use firstOrCreate, order numbers are unique.
 */
class LaundrySampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure correct services + prices exist
        $this->call(LaundryServiceSeeder::class);

        // Deactivate old services that no longer exist in the shop
        Service::whereNotIn('code', ['full_service', 'wash_only', 'bedsheet_only'])
            ->update(['is_active' => false]);

        $services = Service::with('prices')
            ->whereIn('code', ['full_service', 'wash_only', 'bedsheet_only'])
            ->get()
            ->keyBy('code');

        if ($services->isEmpty()) {
            $this->command->error('No services found. Aborting.');
            return;
        }

        $recorder = User::role('owner')->first() ?? User::first();

        // ── Customers ────────────────────────────────────────────────────
        $customerData = [
            // Tenants (room occupants)
            ['name' => 'Elena Maria Rivera',  'type' => 'tenant',      'room_no' => '101', 'contact_no' => '09171234001'],
            ['name' => 'Jose Miguel Santos',  'type' => 'tenant',      'room_no' => '102', 'contact_no' => '09171234002'],
            ['name' => 'Isabel Carmen Diaz',  'type' => 'tenant',      'room_no' => '103', 'contact_no' => '09171234003'],
            ['name' => 'Maria Luisa Santos',  'type' => 'tenant',      'room_no' => '104', 'contact_no' => '09171234004'],
            ['name' => 'Jorge Luis Lopez',    'type' => 'tenant',      'room_no' => '105', 'contact_no' => '09171234005'],
            ['name' => 'Ana Victoria Lopez',  'type' => 'tenant',      'room_no' => '107', 'contact_no' => '09171234007'],
            ['name' => 'Eduardo Soto',        'type' => 'tenant',      'room_no' => '201', 'contact_no' => '09181234001'],
            ['name' => 'Camila Benitez',      'type' => 'tenant',      'room_no' => '202', 'contact_no' => '09181234002'],
            // Students
            ['name' => 'Mark Anthony Reyes',  'type' => 'student',     'room_no' => null,  'contact_no' => '09201234001'],
            ['name' => 'Sophia Grace Tan',    'type' => 'student',     'room_no' => null,  'contact_no' => '09201234002'],
            ['name' => 'Carlos Ivan Morales', 'type' => 'student',     'room_no' => null,  'contact_no' => '09201234003'],
            ['name' => 'Angela Rose Cruz',    'type' => 'student',     'room_no' => null,  'contact_no' => '09201234004'],
            // Walk-in / non-student
            ['name' => 'Roberto Lim',         'type' => 'non_student', 'room_no' => null,  'contact_no' => '09301234001'],
            ['name' => 'Patricia Gomez',      'type' => 'non_student', 'room_no' => null,  'contact_no' => '09301234002'],
        ];

        $customers = [];
        foreach ($customerData as $c) {
            $customers[] = Customer::firstOrCreate(
                ['name' => $c['name'], 'type' => $c['type']],
                ['room_no' => $c['room_no'], 'contact_no' => $c['contact_no']]
            );
        }

        // ── Order templates ───────────────────────────────────────────────
        // [cust_index, service_code, weight_kg, payment_status, payment_method, paid_before, date]
        $orders = [
            // ── October 2026 ──────────────────────────────────────────────
            [0,  'full_service',  6.5,  'paid',   'cash',   false, '2026-10-01'],
            [1,  'wash_only',     4.0,  'paid',   'cash',   true,  '2026-10-01'],
            [8,  'full_service',  5.0,  'paid',   'cash',   false, '2026-10-02'],
            [9,  'bedsheet_only', 1.0,  'paid',   'online', true,  '2026-10-02'],
            [2,  'full_service',  7.0,  'paid',   'cash',   false, '2026-10-03'],
            [12, 'wash_only',     8.0,  'paid',   'cash',   false, '2026-10-03'],
            [3,  'full_service',  9.0,  'unpaid', 'none',   false, '2026-10-04'],
            [10, 'wash_only',     4.5,  'paid',   'cash',   true,  '2026-10-04'],
            [4,  'full_service',  6.0,  'paid',   'cash',   false, '2026-10-05'],
            [13, 'bedsheet_only', 1.0,  'paid',   'cash',   false, '2026-10-05'],
            [5,  'full_service',  8.0,  'unpaid', 'none',   false, '2026-10-06'],
            [11, 'full_service',  3.0,  'paid',   'online', true,  '2026-10-06'],
            [6,  'wash_only',     5.0,  'paid',   'cash',   false, '2026-10-07'],
            [0,  'bedsheet_only', 2.0,  'paid',   'cash',   false, '2026-10-07'],
            [7,  'full_service',  6.0,  'paid',   'cash',   true,  '2026-10-08'],
            [8,  'wash_only',     4.0,  'paid',   'cash',   false, '2026-10-08'],
            [1,  'full_service',  7.5,  'unpaid', 'none',   false, '2026-10-09'],
            [9,  'wash_only',     6.0,  'paid',   'online', false, '2026-10-09'],
            [2,  'bedsheet_only', 1.0,  'paid',   'cash',   true,  '2026-10-10'],
            [10, 'full_service',  5.0,  'paid',   'cash',   false, '2026-10-10'],

            // ── September 2026 ────────────────────────────────────────────
            [3,  'full_service',  8.0,  'paid',   'cash',   false, '2026-09-05'],
            [4,  'full_service',  6.5,  'paid',   'cash',   false, '2026-09-05'],
            [11, 'wash_only',     3.0,  'paid',   'cash',   true,  '2026-09-06'],
            [5,  'full_service',  5.0,  'paid',   'online', false, '2026-09-08'],
            [12, 'bedsheet_only', 1.0,  'paid',   'cash',   false, '2026-09-08'],
            [6,  'full_service',  7.0,  'paid',   'cash',   false, '2026-09-10'],
            [13, 'wash_only',     4.5,  'paid',   'cash',   true,  '2026-09-12'],
            [7,  'full_service',  9.0,  'paid',   'cash',   false, '2026-09-13'],
            [0,  'bedsheet_only', 2.0,  'paid',   'cash',   false, '2026-09-15'],
            [8,  'full_service',  6.0,  'paid',   'cash',   true,  '2026-09-17'],
            [1,  'full_service',  5.5,  'paid',   'online', false, '2026-09-19'],
            [9,  'wash_only',     7.0,  'paid',   'cash',   false, '2026-09-20'],
            [2,  'full_service',  8.0,  'paid',   'cash',   false, '2026-09-22'],
            [10, 'bedsheet_only', 1.0,  'paid',   'cash',   true,  '2026-09-24'],
            [3,  'wash_only',     6.0,  'paid',   'cash',   false, '2026-09-26'],
            [11, 'full_service',  4.0,  'paid',   'cash',   false, '2026-09-28'],

            // ── August 2026 ───────────────────────────────────────────────
            [4,  'full_service',  7.5,  'paid',   'cash',   false, '2026-08-04'],
            [5,  'full_service',  5.0,  'paid',   'cash',   true,  '2026-08-06'],
            [12, 'wash_only',     6.0,  'paid',   'cash',   false, '2026-08-08'],
            [6,  'full_service',  4.0,  'paid',   'cash',   false, '2026-08-10'],
            [13, 'bedsheet_only', 1.0,  'paid',   'cash',   false, '2026-08-12'],
            [7,  'full_service',  8.0,  'paid',   'online', true,  '2026-08-14'],
            [0,  'full_service',  6.5,  'paid',   'cash',   false, '2026-08-16'],
            [8,  'wash_only',     5.0,  'paid',   'cash',   false, '2026-08-18'],
            [1,  'bedsheet_only', 2.0,  'paid',   'cash',   true,  '2026-08-20'],
            [9,  'full_service',  7.0,  'paid',   'cash',   false, '2026-08-22'],
            [2,  'full_service',  4.5,  'paid',   'cash',   false, '2026-08-24'],
            [10, 'wash_only',     6.0,  'paid',   'online', false, '2026-08-26'],
        ];

        $count = 0;

        foreach ($orders as [$custIdx, $code, $weightKg, $payStatus, $payMethod, $paidBefore, $date]) {
            $customer = $customers[$custIdx];
            $service  = $services->get($code);

            if (!$service) {
                $this->command->warn("Service '{$code}' not found, skipping.");
                continue;
            }

            $loads     = $service->loadsFor($weightKg);
            $liquidMl  = $service->liquidMlFor($weightKg);
            $unitPrice = $service->priceFor($customer->type);
            $subtotal  = round($unitPrice * $loads, 2);

            $orderNo = LaundryOrder::generateOrderNo($date);

            $order = LaundryOrder::create([
                'order_no'            => $orderNo,
                'customer_id'         => $customer->id,
                'customer_type'       => $customer->type,
                'room_no'             => $customer->room_no,
                'date_received'       => $date,
                'payment_method'      => $payMethod,
                'payment_status'      => $payStatus,
                'paid_before_service' => $paidBefore,
                'total_amount'        => $subtotal,
                'total_weight_kg'     => $weightKg,
                'total_loads'         => $loads,
                'total_liquid_ml'     => $liquidMl,
                'remarks'             => null,
                'recorded_by'         => $recorder?->id,
            ]);

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

            $count++;
        }

        $this->command->info("✅ Created {$count} laundry orders.");
    }
}
