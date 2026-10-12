<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the old 5-service list (WDF, WD, DRY, WO, COM) and keep only
     * the 3 correct services (full_service, bedsheet_only, wash_only).
     *
     * Old codes to remove: WDF, WD, DRY, WO, COM
     * Keep codes: full_service, bedsheet_only, wash_only
     */
    public function up(): void
    {
        $oldCodes = ['WDF', 'WD', 'DRY', 'WO', 'COM'];

        // Get IDs of old services
        $oldIds = DB::table('services')
            ->whereIn('code', $oldCodes)
            ->pluck('id');

        if ($oldIds->isEmpty()) {
            return; // Already cleaned up
        }

        // Delete their prices first (foreign key)
        DB::table('service_prices')->whereIn('service_id', $oldIds)->delete();

        // Delete old order_items that reference old services (orphaned records)
        DB::table('order_items')->whereIn('service_id', $oldIds)->delete();

        // Delete the old services
        DB::table('services')->whereIn('id', $oldIds)->delete();
    }

    public function down(): void
    {
        // Restore old services (for rollback only — prices are approximate)
        $old = [
            ['code' => 'WDF', 'name' => 'Wash-Dry-Fold',      'weight_limit_kg' => 8.00, 'is_active' => 1,
             'prices' => ['tenant' => 70, 'student' => 75, 'non_student' => 80]],
            ['code' => 'WD',  'name' => 'Wash-Dry',           'weight_limit_kg' => 8.00, 'is_active' => 1,
             'prices' => ['tenant' => 60, 'student' => 65, 'non_student' => 70]],
            ['code' => 'DRY', 'name' => 'Dry Only',           'weight_limit_kg' => 8.00, 'is_active' => 1,
             'prices' => ['tenant' => 40, 'student' => 45, 'non_student' => 50]],
            ['code' => 'WO',  'name' => 'Wash Only',          'weight_limit_kg' => 8.00, 'is_active' => 1,
             'prices' => ['tenant' => 35, 'student' => 40, 'non_student' => 45]],
            ['code' => 'COM', 'name' => 'Comforter/Blanket',  'weight_limit_kg' => 5.00, 'is_active' => 1,
             'prices' => ['tenant' => 100, 'student' => 110, 'non_student' => 120]],
        ];

        foreach ($old as $data) {
            $prices = $data['prices'];
            unset($data['prices']);
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $id = DB::table('services')->insertGetId($data);
            foreach ($prices as $type => $price) {
                DB::table('service_prices')->insert([
                    'service_id'    => $id,
                    'customer_type' => $type,
                    'price'         => $price,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
};
