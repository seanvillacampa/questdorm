<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure the 3 correct laundry services exist with the right prices.
     * Uses updateOrCreate logic so it's safe to run multiple times.
     */
    public function up(): void
    {
        $services = [
            [
                'code'            => 'full_service',
                'name'            => 'Full Service (Wash-Dry-Fold)',
                'weight_limit_kg' => 8.00,
                'is_active'       => 1,
                'prices' => [
                    'tenant'      => 140.00,
                    'student'     => 150.00,
                    'non_student' => 165.00,
                ],
            ],
            [
                'code'            => 'bedsheet_only',
                'name'            => 'Bedsheet Only',
                'weight_limit_kg' => 5.00,
                'is_active'       => 1,
                'prices' => [
                    'tenant'      => 160.00,
                    'student'     => 160.00,
                    'non_student' => 160.00,
                ],
            ],
            [
                'code'            => 'wash_only',
                'name'            => 'Wash Only',
                'weight_limit_kg' => 8.00,
                'is_active'       => 1,
                'prices' => [
                    'tenant'      => 80.00,
                    'student'     => 80.00,
                    'non_student' => 80.00,
                ],
            ],
        ];

        foreach ($services as $data) {
            $prices = $data['prices'];
            unset($data['prices']);

            // Upsert service by code
            $existing = DB::table('services')->where('code', $data['code'])->first();

            if ($existing) {
                DB::table('services')->where('code', $data['code'])->update(array_merge($data, ['updated_at' => now()]));
                $serviceId = $existing->id;
            } else {
                $serviceId = DB::table('services')->insertGetId(array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Upsert prices
            foreach ($prices as $type => $price) {
                $existingPrice = DB::table('service_prices')
                    ->where('service_id', $serviceId)
                    ->where('customer_type', $type)
                    ->first();

                if ($existingPrice) {
                    DB::table('service_prices')
                        ->where('service_id', $serviceId)
                        ->where('customer_type', $type)
                        ->update(['price' => $price, 'updated_at' => now()]);
                } else {
                    DB::table('service_prices')->insert([
                        'service_id'    => $serviceId,
                        'customer_type' => $type,
                        'price'         => $price,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $codes = ['full_service', 'bedsheet_only', 'wash_only'];
        $ids = DB::table('services')->whereIn('code', $codes)->pluck('id');
        DB::table('service_prices')->whereIn('service_id', $ids)->delete();
        DB::table('services')->whereIn('code', $codes)->delete();
    }
};
